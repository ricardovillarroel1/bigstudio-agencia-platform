<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Planes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: #111827;">Gestión de Planes</h2>
                        <button onclick="openModal()" style="display: inline-flex; align-items: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #000; text-transform: uppercase; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <i class="fas fa-plus"></i> Nuevo Plan
                        </button>
                    </div>

                    @if(session('success'))
                        <div style="background: #10b981; color: white; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                            {{ session('success') }}
                        </div>
                        <script>
                            // Forzar refresh completo si no tiene el parámetro refresh
                            if (!window.location.search.includes('refresh=')) {
                                const url = new URL(window.location);
                                url.searchParams.set('refresh', Date.now());
                                window.location.replace(url.toString());
                            }
                        </script>
                    @endif

                    <!-- Buscador -->
                    <form method="GET" action="{{ route('planes.index') }}" style="margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 0.75rem;">
                            <div style="flex: 1; position: relative;">
                                <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #6b7280;"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, descripción o empresa..." style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                            </div>
                            <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            @if(request('search'))
                                <a href="{{ route('planes.index') }}" style="padding: 0.75rem 1.5rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </form>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f3f4f6;">
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Nombre</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Empresa</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Precio</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Estado</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($planes as $plan)
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 0.75rem;">{{ $plan->nombre }}</td>
                                        <td style="padding: 0.75rem;">
                                            <span style="padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600; background: #dbeafe; color: #1e40af;">
                                                {{ $plan->empresa->nombre }}
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; font-weight: 600;">${{ number_format($plan->precio, 2) }} {{ $plan->moneda ?? 'CLP' }}</td>
                                        <td style="padding: 0.75rem;">
                                            <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; {{ $plan->activo ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;' }}">
                                                {{ $plan->activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <button onclick="viewPlan('{{ $plan->nombre }}', '{{ addslashes($plan->descripcion) }}', '{{ $plan->empresa->nombre }}', {{ $plan->precio }}, '{{ $plan->moneda ?? 'CLP' }}', {{ $plan->activo ? 'true' : 'false' }}, {{ json_encode($plan->caracteristicas) }})" style="color: #10b981; background: none; border: none; cursor: pointer; margin-right: 1rem; font-weight: 600;"><i class="fas fa-eye"></i> Ver</button>
                                            <button onclick="editPlan({{ $plan->id }}, '{{ $plan->nombre }}', '{{ addslashes($plan->descripcion) }}', {{ $plan->empresa_id }}, {{ $plan->precio }}, '{{ $plan->moneda ?? 'CLP' }}', {{ $plan->activo ? 'true' : 'false' }}, {{ json_encode($plan->caracteristicas) }}, {{ $plan->facturacion_enabled ? 'true' : 'false' }}, {{ $plan->shopify_visibility_enabled ? 'true' : 'false' }}, {{ $plan->notas_credito_enabled ? 'true' : 'false' }}, {{ $plan->order_limit_enabled ? 'true' : 'false' }}, {{ $plan->monthly_order_limit ?? 'null' }}, {{ $plan->sync_inventario_enabled ? 'true' : 'false' }}, {{ $plan->documentos_postventa_enabled ? 'true' : 'false' }}, {{ $plan->boletas_enabled ? 'true' : 'false' }}, {{ $plan->plan_anual_activo ? 'true' : 'false' }}, {{ $plan->descuento_anual ?? 'null' }}, {{ $plan->precio_anual ?? 'null' }})" style="color: #3b82f6; background: none; border: none; cursor: pointer; margin-right: 1rem; font-weight: 600;"><i class="fas fa-edit"></i> Editar</button>
                                            <form action="{{ route('planes.destroy', $plan) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('¿Estás seguro de eliminar este plan?')" style="color: #ef4444; background: none; border: none; cursor: pointer; font-weight: 600;"><i class="fas fa-trash"></i> Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="padding: 2rem; text-align: center; color: #6b7280;">
                                            @if(request('search'))
                                                <i class="fas fa-search" style="font-size: 2rem; color: #d1d5db; margin-bottom: 0.5rem;"></i>
                                                <p>No se encontraron resultados para "{{ request('search') }}"</p>
                                            @else
                                                <i class="fas fa-clipboard-list" style="font-size: 2rem; color: #d1d5db; margin-bottom: 0.5rem;"></i>
                                                <p>No hay planes registrados</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div style="margin-top: 1.5rem;">
                        {{ $planes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Plan -->
    <div id="planModal" style="display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); animation: fadeIn 0.3s ease;">
        <div style="background: white; margin: 2% auto; padding: 0; border-radius: 1rem; width: 90%; max-width: 800px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); animation: slideDown 0.3s ease;">
            <!-- Header del Modal -->
            <div style="background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%); padding: 2rem; border-radius: 1rem 1rem 0 0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: rgba(0,0,0,0.1); padding: 0.75rem; border-radius: 0.75rem;">
                        <i class="fas fa-clipboard-list" style="font-size: 2rem; color: #1a1a1a;"></i>
                    </div>
                    <h3 id="modalTitle" style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin: 0;">Nuevo Plan</h3>
                </div>
                <button onclick="closeModal()" style="color: #1a1a1a; background: rgba(0,0,0,0.1); border: none; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; font-size: 1.5rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.2)'" onmouseout="this.style.background='rgba(0,0,0,0.1)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Formulario -->
            <form id="planForm" method="POST" action="{{ route('planes.store') }}" style="padding: 2rem; max-height: 70vh; overflow-y: auto;">
                @csrf
                <input type="hidden" id="formMethod" name="_method" value="POST">
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;"><i class="fas fa-building"></i> Empresa *</label>
                    <select name="empresa_id" id="empresa_id" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        <option value="">Seleccione una empresa</option>
                        @foreach(\App\Models\Empresa::all() as $empresa)
                            <option value="{{ $empresa->id }}" {{ !$empresa->disponible ? 'disabled' : '' }}>
                                {{ $empresa->nombre }} {{ !$empresa->disponible ? '(Próximamente)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;"><i class="fas fa-tag"></i> Nombre del Plan *</label>
                    <input type="text" name="nombre" id="nombre" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;"><i class="fas fa-align-left"></i> Descripción *</label>
                    <textarea name="descripcion" id="descripcion" required rows="3" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; resize: vertical;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"></textarea>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;"><i class="fas fa-dollar-sign"></i> Precio *</label>
                    <input type="number" name="precio" id="precio" required step="0.01" min="0" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;"><i class="fas fa-coins"></i> Moneda *</label>
                    <select name="moneda" id="moneda" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        <option value="CLP">CLP (Peso Chileno)</option>
                        <option value="UF">UF (Unidad de Fomento)</option>
                    </select>
                    <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Selecciona la moneda en la que se cobrará el plan</p>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;"><i class="fas fa-list-check"></i> Características *</label>
                    
                    <!-- Características para Lioren (checkboxes) -->
                    <div id="caracteristicasLioren" style="display: none;">
                        <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0.5rem; border: 2px solid #FFC800;">
                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.75rem; background: white; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='white'">
                                    <input type="checkbox" name="facturacion_enabled" id="facturacion_enabled" value="1" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">✅ Habilitar emisión de facturas electrónicas</span>
                                </label>
                            </div>

                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.75rem; background: white; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='white'">
                                    <input type="checkbox" name="boletas_enabled" id="boletas_enabled" value="1" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">🧾 Habilitar emisión de boletas electrónicas</span>
                                </label>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.75rem; background: white; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='white'">
                                    <input type="checkbox" name="shopify_visibility_enabled" id="shopify_visibility_enabled" value="1" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">👁️ Visibilidad desde Shopify</span>
                                </label>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.75rem; background: white; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='white'">
                                    <input type="checkbox" name="notas_credito_enabled" id="notas_credito_enabled" value="1" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">🔄 Notas de Crédito Automáticas</span>
                                </label>
                            </div>

                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.75rem; background: white; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='white'">
                                    <input type="checkbox" name="sync_inventario_enabled" id="sync_inventario_enabled" value="1" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">📦 Sincronización de Inventario</span>
                                </label>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.75rem; background: white; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='white'">
                                    <input type="checkbox" name="documentos_postventa_enabled" id="documentos_postventa_enabled" value="1" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">📝 Documentos Postventa</span>
                                </label>
                            </div>

                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.75rem; background: white; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='white'">
                                    <input type="checkbox" name="order_limit_enabled" id="order_limit_enabled" value="1" onchange="toggleOrderLimit()" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">📊 Límite de Pedidos Mensuales</span>
                                </label>
                                
                                <div id="orderLimitInput" style="display: none; margin-top: 0.75rem; margin-left: 2rem;">
                                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.5rem;">Cantidad de pedidos por mes:</label>
                                    <input type="number" name="monthly_order_limit" id="monthly_order_limit" min="1" placeholder="Ej: 100" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
                                </div>
                            </div>
                            
                            <div>
                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.75rem; background: white; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='white'">
                                    <input type="checkbox" name="no_order_limit" id="no_order_limit" value="1" checked onchange="toggleNoLimit()" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">♾️ Sin límite de pedidos</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Características para otras empresas (texto libre) -->
                    <div id="caracteristicasOtras">
                        <div id="caracteristicasContainer"></div>
                        <button type="button" onclick="addCaracteristica()" style="margin-top: 0.5rem; padding: 0.5rem 1rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                            <i class="fas fa-plus"></i> Agregar Característica
                        </button>
                    </div>
                </div>

                <!-- Sección Plan Anual -->
                <div style="margin-bottom: 1.5rem; background: #FFF7ED; border: 2px solid #FB923C; border-radius: 0.75rem; padding: 1.25rem;">
                    <h4 style="font-size: 1rem; font-weight: 700; color: #9A3412; margin: 0 0 1rem 0;">📅 Opción de Pago Anual</h4>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="plan_anual_activo" id="plan_anual_activo" value="1" onchange="toggleAnualSection()" style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Habilitar opción de pago anual</span>
                        </label>
                    </div>
                    
                    <div id="anualFields" style="display: none;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.5rem;">Descuento Anual (%)</label>
                                <input type="number" name="descuento_anual" id="descuento_anual" min="0" max="100" placeholder="Ej: 20" onchange="calcularPrecioAnual()" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
                                <p style="font-size: 0.7rem; color: #9CA3AF; margin: 4px 0 0 0;">Se calcula automáticamente sobre el precio mensual x 12</p>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.5rem;">Precio Anual (UF) - Calculado</label>
                                <input type="number" name="precio_anual" id="precio_anual" step="0.01" min="0" placeholder="Se calcula automáticamente" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; background: #f9fafb;">
                                <p id="precioAnualInfo" style="font-size: 0.7rem; color: #10B981; margin: 4px 0 0 0; font-weight: 600;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="activo" id="activo" value="1" checked style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem; cursor: pointer;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Plan Activo</span>
                    </label>
                </div>

                <!-- Botones de Acción -->
                <div style="display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" onclick="closeModal()" style="padding: 0.75rem 1.5rem; background: #f3f4f6; color: #374151; border: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                        Cancelar
                    </button>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; font-size: 0.875rem; text-transform: uppercase; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(248, 184, 0, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'">
                        <i class="fas fa-save"></i> Guardar Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Plan -->
    <div id="viewModal" style="display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); animation: fadeIn 0.3s ease;">
        <div style="background: white; margin: 2% auto; padding: 0; border-radius: 1rem; width: 90%; max-width: 700px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); animation: slideDown 0.3s ease;">
            <!-- Header del Modal -->
            <div style="background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%); padding: 2rem; border-radius: 1rem 1rem 0 0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: rgba(0,0,0,0.1); padding: 0.75rem; border-radius: 0.75rem;">
                        <i class="fas fa-clipboard-list" style="font-size: 2rem; color: #1a1a1a;"></i>
                    </div>
                    <h3 id="viewModalTitle" style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin: 0;"></h3>
                </div>
                <button onclick="closeViewModal()" style="color: #1a1a1a; background: rgba(0,0,0,0.1); border: none; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; font-size: 1.5rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.2)'" onmouseout="this.style.background='rgba(0,0,0,0.1)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Contenido -->
            <div style="padding: 2rem;">
                <!-- Empresa y Precio -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; border-left: 4px solid #FFC800;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.5rem;">
                            <i class="fas fa-building"></i> Empresa
                        </div>
                        <div id="viewEmpresa" style="font-size: 1rem; font-weight: 600; color: #111827;"></div>
                    </div>
                    <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; border-left: 4px solid #10b981;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.5rem;">
                            <i class="fas fa-dollar-sign"></i> Precio
                        </div>
                        <div id="viewPrecio" style="font-size: 1.5rem; font-weight: 700; color: #10b981;"></div>
                    </div>
                </div>

                <!-- Descripción -->
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        <i class="fas fa-align-left"></i> Descripción
                    </h4>
                    <p id="viewDescripcion" style="color: #374151; line-height: 1.6;"></p>
                </div>

                <!-- Características -->
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        <i class="fas fa-list-check"></i> Características
                    </h4>
                    <ul id="viewCaracteristicas" style="list-style: none; padding: 0; margin: 0;"></ul>
                </div>

                <!-- Estado -->
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Estado:</span>
                    <span id="viewEstado"></span>
                </div>

                <!-- Botón Cerrar -->
                <div style="display: flex; justify-content: center; margin-top: 2rem;">
                    <button onclick="closeViewModal()" style="padding: 0.75rem 2rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(248, 184, 0, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'">
                        <i class="fas fa-check"></i> Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideDown {
            from { 
                opacity: 0;
                transform: translateY(-50px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        let caracteristicaCount = 0;
        const liorenEmpresaId = {{ \App\Models\Empresa::where('slug', 'lioren')->first()->id ?? 1 }};

        // Detectar cambio de empresa
        document.addEventListener('DOMContentLoaded', function() {
            const empresaSelect = document.getElementById('empresa_id');
            empresaSelect.addEventListener('change', toggleCaracteristicas);
        });

        function toggleCaracteristicas() {
            const empresaSelect = document.getElementById('empresa_id');
            if (!empresaSelect || !empresaSelect.value) {
                // Si no hay empresa seleccionada, mostrar características libres
                document.getElementById('caracteristicasLioren').style.display = 'none';
                document.getElementById('caracteristicasOtras').style.display = 'block';
                // Habilitar required en características libres
                document.querySelectorAll('#caracteristicasContainer input[name="caracteristicas[]"]').forEach(input => {
                    input.required = true;
                });
                return;
            }
            
            const empresaId = parseInt(empresaSelect.value);
            const isLioren = empresaId === liorenEmpresaId;
            
            console.log('Toggle características:', { empresaId, liorenEmpresaId, isLioren });
            
            document.getElementById('caracteristicasLioren').style.display = isLioren ? 'block' : 'none';
            document.getElementById('caracteristicasOtras').style.display = isLioren ? 'none' : 'block';
            
            // Manejar required según visibilidad
            if (isLioren) {
                // Deshabilitar required en características libres cuando están ocultas
                document.querySelectorAll('#caracteristicasContainer input[name="caracteristicas[]"]').forEach(input => {
                    input.required = false;
                });
            } else {
                // Habilitar required en características libres cuando están visibles
                document.querySelectorAll('#caracteristicasContainer input[name="caracteristicas[]"]').forEach(input => {
                    input.required = true;
                });
                // Si no hay características, agregar una
                if (document.getElementById('caracteristicasContainer').children.length === 0) {
                    addCaracteristica();
                }
            }
        }

        function toggleOrderLimit() {
            const checked = document.getElementById('order_limit_enabled',
                    'sync_inventario_enabled').checked;
            const noLimitCheckbox = document.getElementById('no_order_limit');
            const orderLimitInput = document.getElementById('orderLimitInput');
            
            if (checked) {
                noLimitCheckbox.checked = false;
                orderLimitInput.style.display = 'block';
                document.getElementById('monthly_order_limit').required = true;
            } else {
                orderLimitInput.style.display = 'none';
                document.getElementById('monthly_order_limit').required = false;
                document.getElementById('monthly_order_limit').value = '';
            }
        }

        function toggleNoLimit() {
            const checked = document.getElementById('no_order_limit').checked;
            const orderLimitCheckbox = document.getElementById('order_limit_enabled',
                    'sync_inventario_enabled');
            
            if (checked) {
                orderLimitCheckbox.checked = false;
                document.getElementById('orderLimitInput').style.display = 'none';
                document.getElementById('monthly_order_limit').required = false;
                document.getElementById('monthly_order_limit').value = '';
            }
        }

        function openModal() {
            document.getElementById('planModal').style.display = 'block';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Nuevo Plan';
            document.getElementById('planForm').action = '{{ route("planes.store") }}';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('planForm').reset();
            document.getElementById('caracteristicasContainer').innerHTML = '';
            caracteristicaCount = 0;
            
            // Reset checkboxes de Lioren
            document.getElementById('facturacion_enabled').checked = false;
            document.getElementById('boletas_enabled').checked = false;
            document.getElementById('shopify_visibility_enabled').checked = false;
            document.getElementById('notas_credito_enabled').checked = false;
            document.getElementById('documentos_postventa_enabled').checked = false;
            document.getElementById('order_limit_enabled',
                    'sync_inventario_enabled').checked = false;
            document.getElementById('no_order_limit').checked = true;
            document.getElementById('orderLimitInput').style.display = 'none';
            
            // Reset campos de plan anual
            document.getElementById('plan_anual_activo').checked = false;
            document.getElementById('descuento_anual').value = '';
            document.getElementById('precio_anual').value = '';
            document.getElementById('anualFields').style.display = 'none';
            document.getElementById('precioAnualInfo').textContent = '';
            
            // Mostrar características de otras empresas por defecto
            document.getElementById('caracteristicasLioren').style.display = 'none';
            document.getElementById('caracteristicasOtras').style.display = 'block';
            addCaracteristica();
            
            // Esperar un momento y verificar la empresa seleccionada
            setTimeout(toggleCaracteristicas, 100);
        }

        function closeModal() {
            document.getElementById('planModal').style.display = 'none';
        }

        function addCaracteristica(value = '') {
            const container = document.getElementById('caracteristicasContainer');
            const div = document.createElement('div');
            div.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem;';
            div.innerHTML = `
                <input type="text" name="caracteristicas[]" value="${value}" required placeholder="Ej: Sincronización en tiempo real" style="flex: 1; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                <button type="button" onclick="this.parentElement.remove()" style="padding: 0.75rem; background: #fee2e2; color: #991b1b; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
            caracteristicaCount++;
        }

        function editPlan(id, nombre, descripcion, empresa_id, precio, moneda, activo, caracteristicas, facturacion_enabled, shopify_visibility_enabled, notas_credito_enabled, order_limit_enabled, monthly_order_limit, sync_inventario_enabled, documentos_postventa_enabled, boletas_enabled, plan_anual_activo, descuento_anual, precio_anual) {
            document.getElementById('planModal').style.display = 'block';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Plan';
            document.getElementById('planForm').action = '/planes/' + id;
            document.getElementById('formMethod').value = 'PUT';
            
            document.getElementById('nombre').value = nombre;
            document.getElementById('descripcion').value = descripcion;
            document.getElementById('empresa_id').value = empresa_id;
            document.getElementById('precio').value = precio;
            document.getElementById('moneda').value = moneda;
            document.getElementById('activo').checked = activo;
            
            // Cargar campos de plan anual
            document.getElementById('plan_anual_activo').checked = plan_anual_activo || false;
            document.getElementById('descuento_anual').value = descuento_anual || '';
            document.getElementById('precio_anual').value = precio_anual || '';
            toggleAnualSection();
            
            const isLioren = parseInt(empresa_id) === liorenEmpresaId;
            
            if (isLioren) {
                // Mostrar checkboxes de Lioren
                document.getElementById('caracteristicasLioren').style.display = 'block';
                document.getElementById('caracteristicasOtras').style.display = 'none';
                
                // Cargar valores de los checkboxes
                document.getElementById('facturacion_enabled').checked = facturacion_enabled;
                document.getElementById('boletas_enabled').checked = boletas_enabled || false;
                document.getElementById('shopify_visibility_enabled').checked = shopify_visibility_enabled;
                document.getElementById('notas_credito_enabled').checked = notas_credito_enabled;
                document.getElementById('sync_inventario_enabled').checked = sync_inventario_enabled || false;
                document.getElementById('documentos_postventa_enabled').checked = documentos_postventa_enabled || false;
                document.getElementById('order_limit_enabled',
                    'sync_inventario_enabled').checked = order_limit_enabled;
                
                // Manejar límite de pedidos
                if (order_limit_enabled && monthly_order_limit) {
                    document.getElementById('no_order_limit').checked = false;
                    document.getElementById('orderLimitInput').style.display = 'block';
                    document.getElementById('monthly_order_limit').value = monthly_order_limit;
                } else {
                    document.getElementById('no_order_limit').checked = !order_limit_enabled;
                    document.getElementById('orderLimitInput').style.display = 'none';
                    document.getElementById('monthly_order_limit').value = '';
                }
            } else {
                // Mostrar características libres
                document.getElementById('caracteristicasLioren').style.display = 'none';
                document.getElementById('caracteristicasOtras').style.display = 'block';
                
                document.getElementById('caracteristicasContainer').innerHTML = '';
                caracteristicaCount = 0;
                caracteristicas.forEach(car => addCaracteristica(car));
            }
        }

        function viewPlan(nombre, descripcion, empresa, precio, moneda, activo, caracteristicas) {
            document.getElementById('viewModal').style.display = 'block';
            document.getElementById('viewModalTitle').textContent = nombre;
            document.getElementById('viewEmpresa').textContent = empresa;
            document.getElementById('viewPrecio').textContent = '$' + parseFloat(precio).toFixed(2) + ' ' + moneda;
            document.getElementById('viewDescripcion').textContent = descripcion;
            
            // Características
            const caracteristicasList = document.getElementById('viewCaracteristicas');
            caracteristicasList.innerHTML = '';
            caracteristicas.forEach(car => {
                const li = document.createElement('li');
                li.style.cssText = 'padding: 0.75rem; background: #f9fafb; border-radius: 0.5rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem;';
                li.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #10b981; font-size: 1.25rem;"></i>
                    <span style="color: #374151;">${car}</span>
                `;
                caracteristicasList.appendChild(li);
            });
            
            // Estado
            const estadoSpan = document.getElementById('viewEstado');
            if (activo) {
                estadoSpan.innerHTML = '<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46;"><i class="fas fa-check-circle"></i> Activo</span>';
            } else {
                estadoSpan.innerHTML = '<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #991b1b;"><i class="fas fa-times-circle"></i> Inactivo</span>';
            }
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('planModal');
            const viewModal = document.getElementById('viewModal');
            if (event.target == modal) {
                closeModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
        }

        // Manejar el envío del formulario para asegurar que los checkboxes desmarcados se envíen como 0
        document.getElementById('planForm').addEventListener('submit', function(e) {
            const empresaId = parseInt(document.getElementById('empresa_id').value);
            
            // Solo para Lioren
            if (empresaId === liorenEmpresaId) {
                const checkboxes = [
                    'facturacion_enabled',
                    'boletas_enabled',
                    'shopify_visibility_enabled',
                    'notas_credito_enabled',
                    'order_limit_enabled',
                    'sync_inventario_enabled',
                    'documentos_postventa_enabled',
                    'plan_anual_activo'
                ];
                
                checkboxes.forEach(function(checkboxName) {
                    const checkbox = document.getElementById(checkboxName);
                    
                    // Remover cualquier hidden field previo
                    const existingHidden = document.querySelector('input[name="' + checkboxName + '"][type="hidden"]');
                    if (existingHidden) {
                        existingHidden.remove();
                    }
                    
                    // Si el checkbox NO está marcado, agregar un campo hidden con valor 0
                    if (!checkbox.checked) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = checkboxName;
                        hiddenInput.value = '0';
                        checkbox.parentNode.appendChild(hiddenInput);
                    }
                });
            }
        });
        function toggleAnualSection() {
            var checked = document.getElementById('plan_anual_activo').checked;
            document.getElementById('anualFields').style.display = checked ? 'block' : 'none';
            if (checked) {
                calcularPrecioAnual();
            }
        }

        function calcularPrecioAnual() {
            var precioMensual = parseFloat(document.getElementById('precio').value) || 0;
            var descuento = parseInt(document.getElementById('descuento_anual').value) || 0;
            var infoEl = document.getElementById('precioAnualInfo');
            
            if (precioMensual > 0 && descuento > 0) {
                var precioAnualSinDescuento = precioMensual * 12;
                var precioAnualConDescuento = precioAnualSinDescuento * (1 - descuento / 100);
                document.getElementById('precio_anual').value = precioAnualConDescuento.toFixed(2);
                var ahorro = precioAnualSinDescuento - precioAnualConDescuento;
                infoEl.textContent = 'Ahorro: ' + ahorro.toFixed(2) + ' UF (' + descuento + '% descuento)';
            } else if (precioMensual > 0) {
                document.getElementById('precio_anual').value = (precioMensual * 12).toFixed(2);
                infoEl.textContent = 'Sin descuento aplicado';
            } else {
                infoEl.textContent = '';
            }
        }
    </script>
</x-app-layout>
