<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📄 Emitir Boleta Electrónica
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('integracion.boletas') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Ver Boletas
                </a>
                <a href="{{ route('integracion.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    ← Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if(!$api_key)
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    <strong>⚠️ Advertencia:</strong> No hay API Key de Lioren configurada. 
                    <a href="{{ route('integracion.index') }}" class="underline">Configura la integración primero</a>
                </div>
            @endif

            <form action="{{ route('integracion.boletas-emitir') }}" method="POST" id="boletaForm">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-bold mb-4">👤 Datos del Receptor (Opcional)</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">RUT (opcional)</label>
                                <input type="text" name="receptor_rut" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    placeholder="12345678-9">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre (opcional)</label>
                                <input type="text" name="receptor_nombre" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    placeholder="Juan Pérez">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email (opcional)</label>
                                <input type="email" name="receptor_email" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    placeholder="cliente@example.com">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold">📦 Productos / Servicios</h3>
                            <button type="button" onclick="agregarLinea()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                + Agregar Línea
                            </button>
                        </div>

                        <div id="detalles-container">
                            <!-- Las líneas se agregarán aquí dinámicamente -->
                        </div>

                        <div class="mt-4 text-right">
                            <div class="text-2xl font-bold text-gray-800">
                                Total: $<span id="total-display">0</span>
                            </div>
                            <p class="text-sm text-gray-500">Precios con IVA incluido</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-bold mb-4">📝 Observaciones</h3>
                        <textarea name="observaciones" rows="3" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Observaciones adicionales (máx. 250 caracteres)" maxlength="250"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <a href="{{ route('integracion.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded">
                        🚀 Emitir Boleta
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        let lineaIndex = 0;

        function agregarLinea() {
            const container = document.getElementById('detalles-container');
            const div = document.createElement('div');
            div.className = 'border border-gray-200 rounded-lg p-4 mb-4';
            div.id = `linea-${lineaIndex}`;
            
            div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h4 class="font-semibold">Línea ${lineaIndex + 1}</h4>
                    <button type="button" onclick="eliminarLinea(${lineaIndex})" class="text-red-500 hover:text-red-700">
                        ✕ Eliminar
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código/SKU *</label>
                        <input type="text" name="detalles[${lineaIndex}][codigo]" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="PROD-001">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="detalles[${lineaIndex}][nombre]" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Producto o servicio">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad *</label>
                        <input type="number" name="detalles[${lineaIndex}][cantidad]" required min="0.000001" step="0.01" value="1"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 cantidad-input"
                            onchange="calcularTotal()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio c/IVA *</label>
                        <input type="number" name="detalles[${lineaIndex}][precio]" required min="0" step="1" value="0"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 precio-input"
                            onchange="calcularTotal()">
                    </div>
                </div>
                <input type="hidden" name="detalles[${lineaIndex}][unidad]" value="UN">
            `;
            
            container.appendChild(div);
            lineaIndex++;
            calcularTotal();
        }

        function eliminarLinea(index) {
            const linea = document.getElementById(`linea-${index}`);
            if (linea) {
                linea.remove();
                calcularTotal();
            }
        }

        function calcularTotal() {
            let total = 0;
            document.querySelectorAll('.cantidad-input').forEach((cantidadInput, index) => {
                const precioInput = document.querySelectorAll('.precio-input')[index];
                if (cantidadInput && precioInput) {
                    const cantidad = parseFloat(cantidadInput.value) || 0;
                    const precio = parseFloat(precioInput.value) || 0;
                    total += cantidad * precio;
                }
            });
            document.getElementById('total-display').textContent = Math.round(total).toLocaleString('es-CL');
        }

        // Agregar primera línea al cargar
        document.addEventListener('DOMContentLoaded', function() {
            agregarLinea();
        });
    </script>
</x-app-layout>
