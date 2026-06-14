<x-guest-layout>
    <div class="auth-card">
        <h1 class="auth-title">Bienvenido de vuelta</h1>
        <p class="auth-subtitle">Ingresa a tu panel de Integraciones Big Studio</p>

        @if (session('status'))
            <div class="auth-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="auth-field">
                <label class="auth-label" for="email">Correo electrónico</label>
                <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="tucorreo@empresa.cl">
                <x-input-error :messages="$errors->get('email')" class="auth-error" />
            </div>

            <div class="auth-field">
                <label class="auth-label" for="password">Contraseña</label>
                <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                <x-input-error :messages="$errors->get('password')" class="auth-error" />
            </div>

            <div class="auth-row">
                <label class="auth-check" for="remember_me">
                    <input id="remember_me" type="checkbox" name="remember">
                    <span>Recordarme</span>
                </label>
                @if (Route::has('password.request'))
                    <a class="auth-link" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                @endif
            </div>

            <button type="submit" class="auth-btn">
                Iniciar sesión <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
            </button>
        </form>

        <div class="auth-alt">
            ¿No tienes cuenta? <a class="auth-link" href="{{ route('register') }}">Crear cuenta</a>
        </div>
    </div>
</x-guest-layout>
