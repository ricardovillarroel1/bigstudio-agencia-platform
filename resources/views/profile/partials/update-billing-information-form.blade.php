@php
    $cliente = \App\Models\Cliente::where('user_id', auth()->id())->first();
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Datos de Facturaci&oacute;n
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Estos datos se utilizan para emitir tus facturas mensuales de servicio.
        </p>
    </header>

    <form method="post" action="{{ route('profile.billing.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="razon_social" value="Raz&oacute;n Social" />
            <x-text-input id="razon_social" name="razon_social" type="text" class="mt-1 block w-full"
                :value="old('razon_social', $cliente->razon_social ?? $cliente->empresa ?? '')" required />
            <x-input-error class="mt-2" :messages="$errors->get('razon_social')" />
        </div>

        <div>
            <x-input-label for="rut" value="RUT" />
            <x-text-input id="rut" name="rut" type="text" class="mt-1 block w-full"
                :value="old('rut', $cliente->rut ?? '')" required placeholder="12.345.678-9" />
            <x-input-error class="mt-2" :messages="$errors->get('rut')" />
        </div>

        <div>
            <x-input-label for="giro" value="Giro" />
            <x-text-input id="giro" name="giro" type="text" class="mt-1 block w-full"
                :value="old('giro', $cliente->giro ?? '')" required />
            <x-input-error class="mt-2" :messages="$errors->get('giro')" />
        </div>

        <div>
            <x-input-label for="direccion" value="Direcci&oacute;n" />
            <x-text-input id="direccion" name="direccion" type="text" class="mt-1 block w-full"
                :value="old('direccion', $cliente->direccion ?? '')" required />
            <x-input-error class="mt-2" :messages="$errors->get('direccion')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'billing-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">Datos actualizados.</p>
            @endif
        </div>
    </form>
</section>
