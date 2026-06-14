<x-guest-layout>
    <div class="auth-card wide">
        <h1 class="auth-title">Crea tu cuenta</h1>
        <p class="auth-subtitle">Empieza a emitir tus documentos automáticamente con Big Studio</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <p class="auth-section">Datos de la cuenta</p>

            <div class="auth-grid-2">
                <div class="auth-field">
                    <label class="auth-label" for="name">Nombre</label>
                    <input id="name" class="auth-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Tu nombre">
                    <x-input-error :messages="$errors->get('name')" class="auth-error" />
                </div>
                <div class="auth-field">
                    <label class="auth-label" for="email">Correo electrónico</label>
                    <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="tucorreo@empresa.cl">
                    <x-input-error :messages="$errors->get('email')" class="auth-error" />
                </div>
                <div class="auth-field">
                    <label class="auth-label" for="password">Contraseña</label>
                    <input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                    <x-input-error :messages="$errors->get('password')" class="auth-error" />
                </div>
                <div class="auth-field">
                    <label class="auth-label" for="password_confirmation">Confirmar contraseña</label>
                    <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repite tu contraseña">
                    <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" />
                </div>
            </div>

            <p class="auth-section">Datos de facturación</p>

            <div class="auth-field">
                <label class="auth-label" for="razon_social">Razón social</label>
                <input id="razon_social" class="auth-input" type="text" name="razon_social" value="{{ old('razon_social') }}" required placeholder="Ej: Mi Empresa SpA">
                <x-input-error :messages="$errors->get('razon_social')" class="auth-error" />
            </div>

            <div class="auth-grid-2">
                <div class="auth-field">
                    <label class="auth-label" for="rut">RUT</label>
                    <input id="rut" class="auth-input" type="text" name="rut" value="{{ old('rut') }}" required placeholder="12.345.678-9">
                    <p class="auth-hint">Formato: 12.345.678-9</p>
                    <x-input-error :messages="$errors->get('rut')" class="auth-error" />
                </div>
                <div class="auth-field">
                    <label class="auth-label" for="giro">Giro</label>
                    <input id="giro" class="auth-input" type="text" name="giro" value="{{ old('giro') }}" required placeholder="Ej: Venta al por menor">
                    <x-input-error :messages="$errors->get('giro')" class="auth-error" />
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="direccion">Dirección</label>
                <input id="direccion" class="auth-input" type="text" name="direccion" value="{{ old('direccion') }}" required placeholder="Ej: Av. Providencia 1234, Santiago">
                <x-input-error :messages="$errors->get('direccion')" class="auth-error" />
            </div>

            <button type="submit" class="auth-btn">
                Crear cuenta <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
            </button>
        </form>

        <div class="auth-alt">
            ¿Ya tienes cuenta? <a class="auth-link" href="{{ route('login') }}">Iniciar sesión</a>
        </div>
    </div>
</x-guest-layout>
