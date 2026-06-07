<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\IntegracionConfig;
use App\Models\Solicitud;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;

class AppStoreOnboardingController extends Controller
{
    /**
     * Pantalla de bienvenida tras instalación desde Shopify App Store.
     * El merchant ya completó OAuth. Aquí ingresa su email + BigStudio Connector Key.
     */
    public function show(Request $request)
    {
        // Magic token fallback: si la cookie de sesión se perdió en el OAuth cross-site,
        // el callback adjuntó un token de un solo uso en la URL para re-autenticar.
        $token = $request->query('t');
        if ($token && !Auth::check()) {
            $tokenUser = User::where('appstore_login_token', $token)
                ->where('appstore_login_expires_at', '>', now())
                ->first();
            if ($tokenUser) {
                Auth::login($tokenUser);
                $tokenUser->update([
                    'appstore_login_token' => null,
                    'appstore_login_expires_at' => null,
                ]);
                // Redirigir a la misma URL sin el token para limpiar la URL del browser
                return redirect()->route('appstore.onboarding');
            }
        }

        $user = Auth::user();
        if (!$user) {
            return redirect('/login');
        }

        $config = IntegracionConfig::where('user_id', $user->id)->first();
        $solicitud = Solicitud::where('cliente_id', $user->id)
            ->where('estado', 'pendiente_onboarding')
            ->latest()
            ->first();

        // Si ya está vinculado y activo, llevarlo al dashboard
        if ($config && $config->activo && $config->lioren_api_key) {
            return redirect()->route('appstore.dashboard');
        }

        return view('onboarding', [
            'user' => $user,
            'config' => $config,
            'solicitud' => $solicitud,
        ]);
    }

    /**
     * Valida el email + Connector Key del cliente contra la BD BigStudio.
     * Si tiene cuenta y plan activo, vincula la integración al user real y elimina el user temporal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bigstudio_email' => ['required', 'email'],
            'connector_key' => ['required', 'string', 'starts_with:bsk_'],
        ]);

        $tempUser = Auth::user();

        // 1. Buscar el user real de BigStudio
        $realUser = User::where('email', $validated['bigstudio_email'])
            ->where('connector_key', $validated['connector_key'])
            ->first();

        if (!$realUser) {
            Log::warning('Connector Key inválida en onboarding App Store', [
                'shop' => optional(IntegracionConfig::where('user_id', $tempUser->id)->first())->shopify_tienda,
                'bigstudio_email' => $validated['bigstudio_email'],
                'temp_user_id' => $tempUser->id,
            ]);
            throw ValidationException::withMessages([
                'connector_key' => 'Email o Connector Key inválidos. Verifica los datos en tu perfil BigStudio.',
            ]);
        }

        // 2. Verificar que tenga Suscripción activa
        $suscripcion = Suscripcion::where('user_id', $realUser->id)
            ->where('estado', 'activa')
            ->where('pausada', false)
            ->first();

        if (!$suscripcion) {
            Log::warning('Cliente sin Suscripción activa intentó conectar app Shopify', [
                'real_user_id' => $realUser->id,
                'email' => $realUser->email,
            ]);
            throw ValidationException::withMessages([
                'connector_key' => 'Tu cuenta BigStudio no tiene un plan activo. Activa o renueva tu plan para usar la integración.',
            ]);
        }

        // 3. Obtener la API Key Lioren del cliente real (del IntegracionConfig legacy o de la Suscripción)
        $realConfig = IntegracionConfig::where('user_id', $realUser->id)->first();
        $liorenApiKey = $realConfig->lioren_api_key ?? null;

        if (!$liorenApiKey) {
            Log::warning('Cliente sin API Key Lioren configurada', [
                'real_user_id' => $realUser->id,
            ]);
            throw ValidationException::withMessages([
                'connector_key' => 'Tu cuenta BigStudio no tiene una API Key Lioren configurada. Complétala en tu perfil antes de conectar la app.',
            ]);
        }

        // 4. Migrar la IntegracionConfig del user temporal al user real
        $tempConfig = IntegracionConfig::where('user_id', $tempUser->id)->first();
        if ($tempConfig) {
            DB::transaction(function () use ($tempConfig, $tempUser, $realUser, $liorenApiKey) {
                // Si ya existe IntegracionConfig para el user real, actualizar con datos del shop
                $existingConfig = IntegracionConfig::where('user_id', $realUser->id)->first();
                if ($existingConfig) {
                    // Mantener su lioren_api_key y demás. Solo agregar/actualizar Shopify
                    $existingConfig->update([
                        'shopify_tienda' => $tempConfig->shopify_tienda,
                        'shopify_token' => $tempConfig->shopify_token,
                        'shopify_secret' => $tempConfig->shopify_secret,
                        'shopify_client_id' => $tempConfig->shopify_client_id,
                        'shopify_client_secret' => $tempConfig->shopify_client_secret,
                        'shop_domain' => $tempConfig->shop_domain,
                        'auth_method' => 'oauth',
                        'oauth_installed_at' => now(),
                        'activo' => true,
                    ]);
                    // Eliminar el temporal
                    $tempConfig->delete();
                } else {
                    // Reasignar el temporal al user real
                    $tempConfig->update([
                        'user_id' => $realUser->id,
                        'lioren_api_key' => $liorenApiKey,
                        'activo' => true,
                    ]);
                }

                // Reasignar Solicitud (si existe) y usar el email del cliente real
                Solicitud::where('cliente_id', $tempUser->id)
                    ->update([
                        'cliente_id' => $realUser->id,
                        'email' => $realUser->email,
                        'estado' => 'activa_appstore',
                        'integracion_conectada' => true,
                        'fecha_conexion' => now(),
                    ]);

                // Eliminar el user temporal (auto-creado en el callback OAuth)
                User::where('id', $tempUser->id)->delete();
            });

            // 5. Login del user real
            Auth::login($realUser);

            Log::info('Onboarding App Store completado — vinculado a user real', [
                'real_user_id' => $realUser->id,
                'shop' => $tempConfig->shopify_tienda,
                'suscripcion_id' => $suscripcion->id,
                'plan_id' => $suscripcion->plan_id,
            ]);

            return redirect()->route('appstore.dashboard')
                ->with('success', '¡Integración activada! Tu cuenta BigStudio está conectada con tu tienda Shopify.');
        }

        // Caso raro: no debería pasar (el callback siempre crea IntegracionConfig)
        Log::error('Onboarding App Store: no se encontró IntegracionConfig temporal', [
            'temp_user_id' => $tempUser->id,
        ]);
        throw ValidationException::withMessages([
            'connector_key' => 'Hubo un problema técnico. Por favor desinstala la app y vuelve a instalarla.',
        ]);
    }
}
