<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cliente;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Notifications\NewClientRegistered;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'razon_social' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20'],
            'giro' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:500'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'datos_facturacion_completos' => true,
        ]);

        // Create or update cliente record with billing data
        Cliente::updateOrCreate(
            ['user_id' => $user->id],
            [
                'razon_social' => $request->razon_social,
                'empresa' => $request->razon_social,
                'rut' => $request->rut,
                'giro' => $request->giro,
                'direccion' => $request->direccion,
                'estado' => 'activo',
            ]
        );

        event(new Registered($user));

        // Assign cliente role automatically
        $user->assignRole("cliente");

        // Notificar a los administradores del nuevo registro
        try {
            $admins = User::role('admin')->get();
            $clienteData = [
                'razon_social' => $request->razon_social,
                'rut' => $request->rut,
                'giro' => $request->giro,
                'direccion' => $request->direccion,
            ];
            foreach ($admins as $admin) {
                $admin->notify(new NewClientRegistered($user, $clienteData));
            }
            Log::channel('single')->info("Notificación de nuevo registro enviada a " . $admins->count() . " administrador(es)", [
                'new_user' => $user->email,
            ]);
        } catch (\Exception $e) {
            // No bloquear el registro si falla el envío de notificación
            Log::channel('single')->error("Error enviando notificación de nuevo registro: " . $e->getMessage());
        }
        Auth::login($user);

        return redirect('/cliente/dashboard');
    }
}
