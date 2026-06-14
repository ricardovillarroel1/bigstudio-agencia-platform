<x-app-layout>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('agencia.clientes') }}" class="text-brand-600 hover:text-brand-800 text-sm">&larr; Volver a Clientes</a>
                <h2 class="text-xl font-bold text-gray-800 mt-2">Nuevo Cliente de Agencia</h2>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('agencia.clientes.store') }}" class="bs-card p-6">
                @csrf

                <h3 class="font-semibold text-gray-700 mb-4 pb-2 border-b">Información General</h3>
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proyecto / Tienda</label>
                        <input type="text" name="proyecto" value="{{ old('proyecto') }}" placeholder="Ej: BOTAS MILITARES" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>

                <h3 class="font-semibold text-gray-700 mb-4 pb-2 border-b">Datos de Facturación</h3>
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">RUT</label>
                        <input type="text" name="rut" value="{{ old('rut') }}" placeholder="12.345.678-9" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Giro</label>
                        <input type="text" name="giro" value="{{ old('giro') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Fiscal</label>
                        <input type="text" name="direccion_fiscal" value="{{ old('direccion_fiscal') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" name="ciudad" value="{{ old('ciudad') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Comuna</label>
                        <input type="text" name="comuna" value="{{ old('comuna') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Región</label>
                        <input type="text" name="region" value="{{ old('region') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>

                <h3 class="font-semibold text-gray-700 mb-4 pb-2 border-b">Notas</h3>
                <div class="mb-6">
                    <textarea name="notas" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500" placeholder="Notas internas sobre este cliente...">{{ old('notas') }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('agencia.clientes') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancelar</a>
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-semibold text-sm transition">Crear Cliente</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
