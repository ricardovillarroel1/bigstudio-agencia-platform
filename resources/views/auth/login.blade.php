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
        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.25rem 0 1.5rem 0;
        }
        .remember-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .remember-checkbox {
            margin-right: 0.5rem;
            cursor: pointer;
        }
        .forgot-link {
            font-size: 0.875rem;
            color: #6b7280;
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot-link:hover {
            color: #FFC800;
        }
        .btn-login {
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
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #ffca28 0%, #FFC800 100%);
            box-shadow: 0 8px 20px -4px rgba(248, 184, 0, 0.5);
            transform: scale(1.02);
        }
        .btn-login:hover::before {
            left: 100%;
        }
        .btn-login:active {
            transform: scale(0.98);
        }
        .register-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .register-link a {
            color: #FFC800;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .register-link a:hover {
            color: #FF9C00;
            text-decoration: underline;
        }
    </style>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-group">
            <label for="email" class="form-label">Correo electr&oacute;nico</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label">Contrase&ntilde;a</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="remember-row">
            <label for="remember_me" class="remember-label">
                <input id="remember_me" type="checkbox" class="remember-checkbox" name="remember">
                <span>Recordar</span>
            </label>

            @if (Route::has('password.request'))
                <a class="forgot-link" href="{{ route('password.request') }}">
                    Recuperar tu contrase&ntilde;a
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" class="btn-login">
                Acceder
            </button>
        </div>
    </form>

    <!-- Register Link -->
    <div class="register-link">
        &iquest;No tienes cuenta? <a href="{{ route('register') }}">Crear cuenta</a>
    </div>
</x-guest-layout>
