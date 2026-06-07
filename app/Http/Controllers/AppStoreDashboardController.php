<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\IntegracionConfig;

class AppStoreDashboardController extends Controller
{
    /**
     * Dashboard simplificado para merchants instalados desde Shopify App Store.
     * NO muestra planes, precios, ni opciones de billing externo.
     * Solo: estado de integración, contadores, configuración.
     */
    public function index()
    {
        $user = Auth::user();
        $config = IntegracionConfig::where('user_id', $user->id)->first();

        // Si todavía no completó onboarding (sin API key Lioren), llevarlo allá
        if (!$config || !$config->lioren_api_key) {
            return redirect()->route('appstore.onboarding');
        }

        // Email de contacto real (ingresado por el merchant en onboarding) — está en la Solicitud
        $solicitud = \App\Models\Solicitud::where('cliente_id', $user->id)
            ->whereIn('estado', ['activa_appstore', 'pendiente_onboarding'])
            ->latest()
            ->first();
        $emailContacto = $solicitud->email ?? $user->email;
        $nombreContacto = $user->name;

        // Métricas básicas (sin info de billing/planes)
        $documentosEmitidosUltimos30 = 0;
        $pedidosSincronizadosTotal = 0;

        try {
            $documentosEmitidosUltimos30 = DB::table('boletas')
                ->where('user_id', $user->id)
                ->where('status', 'emitida')
                ->where('created_at', '>=', now()->subDays(30))
                ->count()
                + DB::table('facturas_emitidas')
                ->where('user_id', $user->id)
                ->where('status', 'emitida')
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        } catch (\Throwable $e) {
            // Silencioso: si la tabla no existe en este tenant, mostramos 0
        }

        try {
            $pedidosSincronizadosTotal = DB::table('boletas')
                ->where('user_id', $user->id)
                ->count()
                + DB::table('facturas_emitidas')
                ->where('user_id', $user->id)
                ->count();
        } catch (\Throwable $e) {
            // Silencioso
        }

        return view('appstore.dashboard', [
            'config' => $config,
            'documentosEmitidosUltimos30' => $documentosEmitidosUltimos30,
            'pedidosSincronizadosTotal' => $pedidosSincronizadosTotal,
            'emailContacto' => $emailContacto,
            'nombreContacto' => $nombreContacto,
        ]);
    }
}
