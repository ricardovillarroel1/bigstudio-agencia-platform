<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Solicitudes Pendientes de Conexión') }}
        </h2>
    </x-slot>

    <!-- Loader CSS y JS -->
    <link rel="stylesheet" href="{{ asset('css/admin-connection-loader.css') }}">
    <script src="{{ asset('js/admin-connection-loader.js') }}"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-800">
                            🔌 Solicitudes Listas para Conectar
                        </h3>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                            {{ $solicitudes->total() }} pendientes
                        </span>
                    </div>

                    @forelse($solicitudes as $solicitud)
                        <div class="border rounded-lg p-6 mb-4 hover:shadow-md transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Cliente Info -->
                                    <div class="flex items-center mb-3">
                                        <div class="w-12 h-12 bg-brand-100 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-brand-600 font-bold text-lg">
                                                {{ substr($solicitud->cliente->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-bold text-gray-800">
                                                {{ $solicitud->cliente->name }}
                                            </h4>
                                            <p class="text-sm text-gray-600">
                                                {{ $solicitud->cliente->email }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Plan Info -->
                                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-xs text-gray-500 mb-1">Plan Contratado</p>
                                                <p class="font-semibold text-gray-800">
                                                    {{ $solicitud->plan->nombre }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    {{ $solicitud->plan->empresa->nombre }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 mb-1">Precio</p>
                                                <p class="font-semibold text-gray-800">
                                                    ${{ number_format($solicitud->plan->precio, 0, ',', '.') }} {{ $solicitud->plan->moneda }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Características del Plan -->
                                        <div class="mt-4 pt-4 border-t">
                                            <p class="text-xs text-gray-500 mb-2">Características Habilitadas:</p>
                                            <div class="flex flex-wrap gap-2">
                                                @if($solicitud->plan->facturacion_enabled)
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">
                                                        ✅ Facturación
                                                    </span>
                                                @endif
                                                @if($solicitud->plan->shopify_visibility_enabled)
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                                        👁️ Visibilidad Shopify
                                                    </span>
                                                @endif
                                                @if($solicitud->plan->notas_credito_enabled)
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">
                                                        🔄 Notas de Crédito
                                                    </span>
                                                @endif
                                                @if($solicitud->plan->order_limit_enabled)
                                                    <span class="px-2 py-1 bg-brand-100 text-brand-800 rounded text-xs">
                                                        📊 Límite: {{ $solicitud->plan->monthly_order_limit }} pedidos/mes
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 bg-brand-100 text-brand-800 rounded text-xs">
                                                        ♾️ Sin límite de pedidos
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Credenciales Info -->
                                    <div class="bg-blue-50 rounded-lg p-4 mb-4">
                                        <p class="text-sm font-semibold text-blue-900 mb-2">
                                            🔐 Credenciales Proporcionadas
                                        </p>
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div>
                                                <p class="text-gray-600">Tienda Shopify:</p>
                                                <p class="font-mono text-xs text-gray-800 break-all">
                                                    {{ $solicitud->tienda_shopify }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-gray-600">Access Token:</p>
                                                <p class="font-mono text-xs text-gray-800">
                                                    {{ substr($solicitud->access_token, 0, 20) }}...
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-gray-600">API Secret:</p>
                                                <p class="font-mono text-xs text-gray-800">
                                                    {{ substr($solicitud->api_secret, 0, 20) }}...
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-gray-600">Lioren API Key:</p>
                                                <p class="font-mono text-xs text-gray-800">
                                                    {{ substr($solicitud->api_key, 0, 20) }}...
                                                </p>
                                            </div>
                                        </div>
                                        @if($solicitud->telefono)
                                            <div class="mt-2 pt-2 border-t border-blue-200">
                                                <p class="text-gray-600">Teléfono:</p>
                                                <p class="text-gray-800">{{ $solicitud->telefono }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Payment Info -->
                                    @if($solicitud->payment)
                                        <div class="text-sm text-gray-600">
                                            <p>
                                                💳 Pago realizado el {{ $solicitud->payment->paid_at ? $solicitud->payment->paid_at->format('d/m/Y H:i') : 'N/A' }}
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Actions -->
                                <div class="ml-6 flex flex-col gap-2">
                                    <form action="{{ route('admin.solicitudes.conectar', $solicitud) }}" method="POST" onsubmit="return handleConectarSubmit(event)">
                                        @csrf
                                        <button 
                                            type="submit"
                                            style="background-color: #16a34a; color: white; padding: 0.75rem 1.5rem; font-weight: 600; border-radius: 0.5rem; border: none; cursor: pointer; white-space: nowrap;"
                                            onmouseover="this.style.backgroundColor='#15803d'"
                                            onmouseout="this.style.backgroundColor='#16a34a'"
                                        >
                                            🔌 Conectar
                                        </button>
                                    </form>

                                    <button 
                                        onclick="openRejectModal({{ $solicitud->id }})"
                                        style="background-color: #dc2626; color: white; padding: 0.75rem 1.5rem; font-weight: 600; border-radius: 0.5rem; border: none; cursor: pointer; white-space: nowrap;"
                                        onmouseover="this.style.backgroundColor='#b91c1c'"
                                        onmouseout="this.style.backgroundColor='#dc2626'"
                                    >
                                        ❌ Rechazar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <p class="text-gray-600 text-lg">No hay solicitudes pendientes de conexión</p>
                            <p class="text-gray-500 text-sm mt-2">Las solicitudes aparecerán aquí cuando los clientes hayan pagado e ingresado sus credenciales</p>
                        </div>
                    @endforelse

                    @if($solicitudes->hasPages())
                        <div class="mt-6">
                            {{ $solicitudes->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Rechazo -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Rechazar Solicitud</h3>
                <form id="rejectForm" method="POST">
                    @csrf
                    @method('POST')
                    <div class="mb-4">
                        <label for="notas_admin" class="block text-sm font-medium text-gray-700 mb-2">
                            Motivo del rechazo *
                        </label>
                        <textarea 
                            id="notas_admin"
                            name="notas_admin"
                            rows="4"
                            required
                            minlength="10"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="Explica el motivo del rechazo..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button 
                            type="button"
                            onclick="closeRejectModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        >
                            Rechazar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRejectModal(solicitudId) {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            form.action = `/admin/solicitudes/${solicitudId}/rechazar`;
            modal.classList.remove('hidden');
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('hidden');
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('rejectModal');
            if (event.target == modal) {
                closeRejectModal();
            }
        }

        // Manejar submit del formulario de conexión
        function handleConectarSubmit(event) {
            event.preventDefault();
            
            Swal.fire({
                title: '¿Conectar integración?',
                text: 'Se validarán las credenciales, crearán webhooks y sincronizarán productos.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, conectar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;
                
                const form = event.target;
                const formData = new FormData(form);
                
                // Mostrar el loader animado
                showConnectionLoader();
                
                // Enviar petición AJAX
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Ocultar loader
                    hideConnectionLoader();
                    
                    if (data.success) {
                        // Construir mensaje detallado de webhooks
                        let webhooksHTML = '';
                        
                        if (data.webhooks) {
                            const { exitosos, fallidos, creados, errores } = data.webhooks;
                            
                            webhooksHTML = `
                                <div style="text-align: left; margin-top: 15px;">
                                    <p style="font-weight: bold; margin-bottom: 10px;">📊 Resumen de Webhooks:</p>
                                    <div style="background: #f0fdf4; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                                        <p style="color: #16a34a; margin: 5px 0;">✅ Exitosos: ${exitosos}</p>
                                        ${fallidos > 0 ? `<p style="color: #dc2626; margin: 5px 0;">❌ Fallidos: ${fallidos}</p>` : ''}
                                    </div>
                            `;
                            
                            // Mostrar webhooks creados
                            if (creados && creados.length > 0) {
                                webhooksHTML += `
                                    <div style="margin-top: 10px;">
                                        <p style="font-size: 14px; font-weight: 600; color: #16a34a; margin-bottom: 5px;">Webhooks Creados:</p>
                                        <ul style="font-size: 13px; color: #374151; list-style: none; padding-left: 0;">
                                `;
                                creados.forEach(webhook => {
                                    webhooksHTML += `<li style="margin: 3px 0;">✓ ${webhook.nombre}</li>`;
                                });
                                webhooksHTML += `</ul></div>`;
                            }
                            
                            // Mostrar errores si los hay
                            if (errores && errores.length > 0) {
                                webhooksHTML += `
                                    <div style="margin-top: 10px; background: #fef2f2; padding: 10px; border-radius: 8px;">
                                        <p style="font-size: 14px; font-weight: 600; color: #dc2626; margin-bottom: 5px;">Errores:</p>
                                        <ul style="font-size: 12px; color: #991b1b; list-style: none; padding-left: 0;">
                                `;
                                errores.forEach(error => {
                                    webhooksHTML += `<li style="margin: 5px 0;">✗ ${error.nombre}: ${error.error}</li>`;
                                });
                                webhooksHTML += `</ul></div>`;
                            }
                            
                            webhooksHTML += `</div>`;
                        }
                        
                        // Determinar icono y color según resultado
                        const icon = data.webhooks && data.webhooks.fallidos > 0 ? 'warning' : 'success';
                        const confirmButtonColor = data.webhooks && data.webhooks.fallidos > 0 ? '#f59e0b' : '#16a34a';
                        
                        Swal.fire({
                            title: data.webhooks && data.webhooks.fallidos > 0 ? '⚠️ Conexión Parcial' : '✅ Conexión Exitosa',
                            html: `
                                <div style="text-align: center;">
                                    <p style="margin-bottom: 10px;">${data.message}</p>
                                    ${webhooksHTML}
                                    <p style="margin-top: 15px; font-size: 14px; color: #6b7280;">
                                        🔄 Sincronizando productos en segundo plano...
                                    </p>
                                </div>
                            `,
                            icon: icon,
                            confirmButtonColor: confirmButtonColor,
                            confirmButtonText: 'Entendido',
                            width: '600px'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        // Construir mensaje de error detallado
                        let errorHTML = `<p>${data.message}</p>`;
                        
                        // Agregar detalles de webhooks si existen
                        if (data.webhooks && data.webhooks.errores && data.webhooks.errores.length > 0) {
                            errorHTML += `
                                <div style="text-align: left; margin-top: 15px; background: #fef2f2; padding: 10px; border-radius: 8px;">
                                    <p style="font-weight: bold; color: #dc2626; margin-bottom: 10px;">Errores de Webhooks:</p>
                                    <ul style="font-size: 13px; color: #991b1b; list-style: none; padding-left: 0;">
                            `;
                            data.webhooks.errores.forEach(error => {
                                errorHTML += `<li style="margin: 5px 0;">✗ ${error.nombre}: ${error.error}</li>`;
                            });
                            errorHTML += `</ul></div>`;
                        }
                        
                        // Agregar errores detallados si existen
                        if (data.errores_detalle && data.errores_detalle.length > 0) {
                            errorHTML += `
                                <div style="text-align: left; margin-top: 10px; font-size: 13px; color: #991b1b;">
                                    <ul style="list-style: none; padding-left: 0;">
                            `;
                            data.errores_detalle.forEach(error => {
                                errorHTML += `<li style="margin: 5px 0;">• ${error}</li>`;
                            });
                            errorHTML += `</ul></div>`;
                        }
                        
                        Swal.fire({
                            title: '❌ Error de Conexión',
                            html: errorHTML,
                            icon: 'error',
                            confirmButtonColor: '#ef4444',
                            confirmButtonText: 'Entendido',
                            width: '600px'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideConnectionLoader();
                    
                    Swal.fire({
                        title: '❌ Error',
                        text: 'Error de conexión. Por favor intenta de nuevo.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'Entendido'
                    });
                });
            });
            
            return false;
        }

        // Si hay un mensaje de éxito o error, ocultar el loader
        @if(session('success') || session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                if (window.connectionLoader) {
                    hideConnectionLoader();
                }
            });
        @endif
    </script>
</x-app-layout>
