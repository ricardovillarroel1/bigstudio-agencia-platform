<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Nuevo Usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    <form method="POST" action="{{ route('usuarios.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Columna izquierda: Datos de cuenta -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b border-gray-200">
                                    <svg class="inline w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Datos de Cuenta
                                </h3>

                                <!-- Name -->
                                <div>
                                    <x-input-label for="name" :value="__('Nombre')" />
                                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <!-- Email -->
                                <div class="mt-4">
                                    <x-input-label for="email" :value="__('Correo Electrónico')" />
                                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <!-- Password -->
                                <div class="mt-4">
                                    <x-input-label for="password" :value="__('Contraseña')" />
                                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                <!-- Role -->
                                <div class="mt-4">
                                    <x-input-label for="role" :value="__('Rol')" />
                                    <select id="role" name="role" class="block mt-1 w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm" required>
                                        <option value="">Seleccionar rol</option>
                                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador</option>
                                        <option value="cliente" {{ old('role') == 'cliente' ? 'selected' : '' }}>Cliente</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Columna derecha: Datos de facturación -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 mb-4 pb-2 border-b border-gray-200">
                                    <svg class="inline w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Datos de Facturación
                                </h3>
                                <p class="text-sm text-gray-500 mb-4">Estos datos se usarán para la emisión de facturas al cliente.</p>

                                <!-- Razón Social -->
                                <div>
                                    <x-input-label for="razon_social" :value="__('Razón Social')" />
                                    <x-text-input id="razon_social" class="block mt-1 w-full" type="text" name="razon_social" :value="old('razon_social')" placeholder="Nombre legal de la empresa" />
                                    <x-input-error :messages="$errors->get('razon_social')" class="mt-2" />
                                </div>

                                <!-- RUT -->
                                <div class="mt-4">
                                    <x-input-label for="rut" :value="__('RUT')" />
                                    <x-text-input id="rut" class="block mt-1 w-full" type="text" name="rut" :value="old('rut')" placeholder="Ej: 76.123.456-7" />
                                    <x-input-error :messages="$errors->get('rut')" class="mt-2" />
                                </div>

                                <!-- Giro -->
                                <div class="mt-4">
                                    <x-input-label for="giro" :value="__('Giro')" />
                                    <x-text-input id="giro" class="block mt-1 w-full" type="text" name="giro" :value="old('giro')" placeholder="Actividad económica" />
                                    <x-input-error :messages="$errors->get('giro')" class="mt-2" />
                                </div>

                                <!-- Dirección -->
                                <div class="mt-4">
                                    <x-input-label for="direccion" :value="__('Dirección')" />
                                    <x-text-input id="direccion" class="block mt-1 w-full" type="text" name="direccion" :value="old('direccion')" placeholder="Dirección fiscal" />
                                    <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 pt-4 border-t border-gray-200">
                            <a href="{{ route('usuarios.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">
                                Cancelar
                            </a>
                            <x-primary-button>
                                {{ __('Crear Usuario') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
