<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Clientes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: #111827;">Gestión de Clientes</h2>
                        <button onclick="openModal()" style="display: inline-flex; align-items: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #000; text-transform: uppercase; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <i class="fas fa-plus"></i> Nuevo Cliente
                        </button>
                    </div>

                    <!-- Buscador -->
                    <form method="GET" action="{{ route('clientes.index') }}" style="margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 0.75rem;">
                            <div style="flex: 1; position: relative;">
                                <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #6b7280;"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, email, empresa, RUT o teléfono..." style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                            </div>
                            <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            @if(request('search'))
                                <a href="{{ route('clientes.index') }}" style="padding: 0.75rem 1.5rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </form>

                    @if(session('success'))
                        <div style="background: #10b981; color: white; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div style="background: #ef4444; color: white; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f3f4f6;">
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Nombre</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Email</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Empresa</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Teléfono</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Estado</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clientes as $cliente)
                                    @if($cliente->user)
                                        <tr style="border-bottom: 1px solid #e5e7eb;">
                                            <td style="padding: 0.75rem;">{{ $cliente->user->name }}</td>
                                            <td style="padding: 0.75rem;">{{ $cliente->user->email }}</td>
                                            <td style="padding: 0.75rem;">{{ $cliente->empresa ?? '-' }}</td>
                                            <td style="padding: 0.75rem;">{{ $cliente->telefono ?? '-' }}</td>
                                            <td style="padding: 0.75rem;">
                                                <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; {{ $cliente->estado === 'activo' ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;' }}">
                                                    {{ ucfirst($cliente->estado) }}
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <a href="{{ route('clientes.show', $cliente) }}" style="color: #10b981; background: none; border: none; cursor: pointer; margin-right: 1rem; text-decoration: none; font-weight: 600;"><i class="fas fa-eye"></i> Ver</a>
                                                <button onclick="editCliente({{ $cliente->id }}, '{{ $cliente->user->name }}', '{{ $cliente->user->email }}', '{{ $cliente->empresa }}', '{{ $cliente->rut }}', '{{ $cliente->telefono }}', '{{ $cliente->telefono_secundario }}', '{{ $cliente->direccion }}', '{{ $cliente->ciudad }}', '{{ $cliente->region }}', '{{ $cliente->codigo_postal }}', '{{ $cliente->giro }}', '{{ addslashes($cliente->notas) }}', '{{ $cliente->estado }}')" style="color: #3b82f6; background: none; border: none; cursor: pointer; margin-right: 1rem; font-weight: 600;"><i class="fas fa-edit"></i> Editar</button>
                                                <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" onclick="return confirm('¿Estás seguro de eliminar este cliente?')" style="color: #ef4444; background: none; border: none; cursor: pointer; font-weight: 600;"><i class="fas fa-trash"></i> Eliminar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 2rem; text-align: center; color: #6b7280;">
                                            @if(request('search'))
                                                <i class="fas fa-search" style="font-size: 2rem; color: #d1d5db; margin-bottom: 0.5rem;"></i>
                                                <p>No se encontraron resultados para "{{ request('search') }}"</p>
                                            @else
                                                <i class="fas fa-users" style="font-size: 2rem; color: #d1d5db; margin-bottom: 0.5rem;"></i>
                                                <p>No hay clientes registrados</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div style="margin-top: 1.5rem;">
                        {{ $clientes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Cliente -->
    <div id="clienteModal" style="display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); animation: fadeIn 0.3s ease;">
        <div style="background: white; margin: 2% auto; padding: 0; border-radius: 1rem; width: 90%; max-width: 900px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); animation: slideDown 0.3s ease;">
            <!-- Header del Modal -->
            <div style="background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%); padding: 2rem; border-radius: 1rem 1rem 0 0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: rgba(0,0,0,0.1); padding: 0.75rem; border-radius: 0.75rem;">
                        <svg style="width: 2rem; height: 2rem; color: #1a1a1a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 id="modalTitle" style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin: 0;">Nuevo Cliente</h3>
                </div>
                <button onclick="closeModal()" style="color: #1a1a1a; background: rgba(0,0,0,0.1); border: none; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; font-size: 1.5rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.2)'" onmouseout="this.style.background='rgba(0,0,0,0.1)'">
                    <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Formulario -->
            <form id="clienteForm" method="POST" action="{{ route('clientes.store') }}" style="padding: 2rem; max-height: 70vh; overflow-y: auto;">
                @csrf
                <input type="hidden" id="formMethod" name="_method" value="POST">
                
                <!-- Sección: Información Personal -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        👤 Información Personal
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Nombre Completo *</label>
                            <input type="text" name="name" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Email *</label>
                            <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                    </div>

                    <div style="margin-top: 1rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Contraseña *</label>
                        <input type="password" name="password" id="passwordField" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem; font-style: italic;">💡 Dejar en blanco para mantener la contraseña actual (solo al editar)</p>
                    </div>
                </div>

                <!-- Sección: Información de Empresa -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        🏢 Información de Empresa
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Empresa</label>
                            <input type="text" name="empresa" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">RUT</label>
                            <input type="text" name="rut" placeholder="12.345.678-9" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                    </div>

                    <div style="margin-top: 1rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Giro</label>
                        <input type="text" name="giro" placeholder="Ej: Comercio al por menor" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                    </div>
                </div>

                <!-- Sección: Contacto -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        📞 Información de Contacto
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Teléfono Principal</label>
                            <input type="text" name="telefono" placeholder="+56 9 1234 5678" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Teléfono Secundario</label>
                            <input type="text" name="telefono_secundario" placeholder="+56 9 8765 4321" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                    </div>
                </div>

                <!-- Sección: Ubicación -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        📍 Ubicación
                    </h4>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Dirección</label>
                        <input type="text" name="direccion" placeholder="Calle, número, depto/oficina" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Ciudad</label>
                            <input type="text" name="ciudad" placeholder="Santiago" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Región</label>
                            <input type="text" name="region" placeholder="Metropolitana" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Código Postal</label>
                            <input type="text" name="codigo_postal" placeholder="8320000" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        </div>
                    </div>
                </div>

                <!-- Sección: Notas -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        📝 Notas Adicionales
                    </h4>
                    <textarea name="notas" rows="3" placeholder="Información adicional sobre el cliente..." style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; resize: vertical;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"></textarea>
                </div>

                <!-- Campo Estado (oculto por defecto) -->
                <div id="estadoField" style="display: none; margin-bottom: 2rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Estado</label>
                    <select name="estado" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div style="display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" onclick="closeModal()" style="padding: 0.75rem 1.5rem; background: #f3f4f6; color: #374151; border: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                        Cancelar
                    </button>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; font-size: 0.875rem; text-transform: uppercase; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(248, 184, 0, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'">
                        💾 Guardar Cliente
                    </button>
                </div>
            </form>
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
        function openModal() {
            document.getElementById('clienteModal').style.display = 'block';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Cliente';
            document.getElementById('clienteForm').action = '{{ route("clientes.store") }}';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('clienteForm').reset();
            document.getElementById('passwordField').required = true;
            document.getElementById('estadoField').style.display = 'none';
            
            // Limpiar todos los campos
            document.querySelectorAll('#clienteForm input, #clienteForm textarea, #clienteForm select').forEach(field => {
                if (field.name !== '_method' && field.name !== '_token') {
                    field.value = '';
                }
            });
        }

        function closeModal() {
            document.getElementById('clienteModal').style.display = 'none';
        }

        function editCliente(id, name, email, empresa, rut, telefono, telefono_secundario, direccion, ciudad, region, codigo_postal, giro, notas, estado) {
            document.getElementById('clienteModal').style.display = 'block';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Editar Cliente';
            document.getElementById('clienteForm').action = '{{ route("clientes.index") }}/' + id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('passwordField').required = false;
            document.getElementById('estadoField').style.display = 'block';
            
            // Llenar los campos con los datos del cliente
            document.querySelector('input[name="name"]').value = name;
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="empresa"]').value = empresa || '';
            document.querySelector('input[name="rut"]').value = rut || '';
            document.querySelector('input[name="telefono"]').value = telefono || '';
            document.querySelector('input[name="telefono_secundario"]').value = telefono_secundario || '';
            document.querySelector('input[name="direccion"]').value = direccion || '';
            document.querySelector('input[name="ciudad"]').value = ciudad || '';
            document.querySelector('input[name="region"]').value = region || '';
            document.querySelector('input[name="codigo_postal"]').value = codigo_postal || '';
            document.querySelector('input[name="giro"]').value = giro || '';
            document.querySelector('textarea[name="notas"]').value = notas || '';
            document.querySelector('select[name="estado"]').value = estado;
            document.querySelector('input[name="password"]').value = '';
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('clienteModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</x-app-layout>
