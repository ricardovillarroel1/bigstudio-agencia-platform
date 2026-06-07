<x-guest-layout>
    <style>
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
            margin-bottom: 0.4rem;
        }
        .form-input {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 2px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .form-input:hover {
            border-color: #9ca3af;
        }
        .form-input:focus {
            outline: none;
            border-color: #FFC800;
            box-shadow: 0 0 0 3px rgba(248, 184, 0, 0.1);
        }
        .section-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #FFC800;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .btn-register {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%);
            border: none;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 0.875rem;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #ffca28 0%, #FFC800 100%);
            box-shadow: 0 8px 20px -4px rgba(248, 184, 0, 0.5);
            transform: scale(1.02);
        }
        .btn-register:hover::before {
            left: 100%;
        }
        .btn-register:active {
            transform: scale(0.98);
        }
        .login-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .login-link a {
            color: #FFC800;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .login-link a:hover {
            color: #FF9C00;
            text-decoration: underline;
        }
        .form-hint {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }
    </style>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Account Data Section -->
        <p class="section-title">Datos de cuenta</p>

        <!-- Name -->
        <div class="form-group">
            <label for="name" class="form-label">Nombre completo</label>
            <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="form-group">
            <label for="email" class="form-label">Correo electr&oacute;nico</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label">Contrase&ntilde;a</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirmar contrase&ntilde;a</label>
            <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Billing Data Section -->
        <p class="section-title" style="margin-top: 1.5rem;">Datos de facturaci&oacute;n</p>

        <!-- Razon Social -->
        <div class="form-group">
            <label for="razon_social" class="form-label">Raz&oacute;n Social</label>
            <input id="razon_social" class="form-input" type="text" name="razon_social" value="{{ old('razon_social') }}" required placeholder="Ej: Mi Empresa SpA">
            <x-input-error :messages="$errors->get('razon_social')" class="mt-2" />
        </div>

        <!-- RUT -->
        <div class="form-row">
            <div class="form-group">
                <label for="rut" class="form-label">RUT</label>
                <input id="rut" class="form-input" type="text" name="rut" value="{{ old('rut') }}" required placeholder="12.345.678-9">
                <p class="form-hint">Formato: 12.345.678-9</p>
                <x-input-error :messages="$errors->get('rut')" class="mt-2" />
            </div>

            <!-- Giro -->
            <div class="form-group">
                <label for="giro" class="form-label">Giro</label>
                <input id="giro" class="form-input" type="text" name="giro" value="{{ old('giro') }}" required placeholder="Ej: Venta al por menor">
                <x-input-error :messages="$errors->get('giro')" class="mt-2" />
            </div>
        </div>

        <!-- Direccion -->
        <div class="form-group">
            <label for="direccion" class="form-label">Direcci&oacute;n</label>
            <input id="direccion" class="form-input" type="text" name="direccion" value="{{ old('direccion') }}" required placeholder="Ej: Av. Providencia 1234, Santiago">
            <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn-register">
                Crear cuenta
            </button>
        </div>
    </form>

    <!-- Login Link -->
    <div class="login-link">
        &iquest;Ya tienes cuenta? <a href="{{ route('login') }}">Acceder</a>
    </div>
</x-guest-layout>
