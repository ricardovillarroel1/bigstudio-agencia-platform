<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Renovar Suscripción
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-6">Confirmar Renovación</h3>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Plan</p>
                                <p class="font-semibold text-lg">{{ $plan->nombre }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Precio Mensual</p>
                                <p class="font-semibold text-lg">${{ number_format($plan->precio, 0, ',', '.') }} {{ $plan->moneda }}</p>
                            </div>
                            @if($plan->plan_anual_activo && $plan->precio_anual > 0)
                            <div>
                                <p class="text-sm text-gray-600">Precio Anual</p>
                                <p class="font-semibold text-lg">${{ number_format($plan->precio_anual, 0, ',', '.') }} {{ $plan->moneda }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-sm text-gray-600">Vencimiento Actual</p>
                                <p class="font-semibold">{{ $suscripcion->fecha_fin->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Selector de período -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Período de renovación</label>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition" id="label-mensual">
                                <input type="radio" name="periodo" value="mensual" checked class="text-blue-600" onchange="actualizarPeriodo()">
                                <span>Mensual - ${{ number_format($plan->precio, 0, ',', '.') }}</span>
                            </label>
                            @if($plan->plan_anual_activo && $plan->precio_anual > 0)
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition" id="label-anual">
                                <input type="radio" name="periodo" value="anual" class="text-blue-600" onchange="actualizarPeriodo()">
                                <span>Anual - ${{ number_format($plan->precio_anual, 0, ',', '.') }}</span>
                            </label>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" id="btn-pagar" onclick="procesarPago()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-credit-card mr-2"></i> Pagar con Flow
                        </button>
                        
                        <a href="{{ route('suscripciones.index') }}" 
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                            Cancelar
                        </a>
                    </div>

                    <div id="error-message" class="mt-4 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let periodoSeleccionado = 'mensual';

        function actualizarPeriodo() {
            periodoSeleccionado = document.querySelector('input[name="periodo"]:checked').value;
        }

        function procesarPago() {
            const btn = document.getElementById('btn-pagar');
            const errorDiv = document.getElementById('error-message');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...';
            errorDiv.classList.add('hidden');

            fetch('{{ route("flow.create-plan-payment") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    plan_id: {{ $plan->id }},
                    periodo: periodoSeleccionado
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    throw new Error(data.message || 'No se pudo procesar el pago');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorDiv.textContent = error.message || 'Error al procesar el pago. Intenta nuevamente.';
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-credit-card mr-2"></i> Pagar con Flow';
            });
        }
    </script>
</x-app-layout>
