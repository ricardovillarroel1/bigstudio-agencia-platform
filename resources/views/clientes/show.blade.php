<x-app-layout>

    <x-slot name="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detalle del Cliente') }}
            </h2>
            <a href="{{ route('clientes.index') }}" style="padding: 0.5rem 1rem; background: #f3f4f6; color: #374151; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                ← Volver
            </a>
        </div>
    </x-slot>

    <style>
        .detail-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .detail-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #FFC800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
        .info-item {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            border-left: 4px solid #FFC800;
            transition: all 0.2s;
        }
        .info-item:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }
        .info-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-activo {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        .status-inactivo {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        .header-card {
            background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -5px rgba(255, 202, 40, 0.4);
        }
        .client-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header Card -->
            <div class="header-card">
                <div style="display: flex; align-items: center; gap: 2rem;">
                    <div class="client-avatar">
                        {{ strtoupper(substr($cliente->user->name, 0, 1)) }}
                    </div>
                    <div style="flex: 1;">
                        <h1 style="font-size: 2rem; font-weight: 700; color: #1a1a1a; margin-bottom: 0.5rem;">
                            {{ $cliente->user->name }}
                        </h1>
                        <p style="font-size: 1.125rem; color: #1a1a1a; opacity: 0.8;">
                            {{ $cliente->user->email }}
                        </p>
                    </div>
                    <div>
                        <span class="status-badge status-{{ $cliente->estado }}">
                            <i class="fas fa-{{ $cliente->estado === 'activo' ? 'check-circle' : 'times-circle' }}"></i>
                            {{ $cliente->estado === 'activo' ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Información Personal -->
            <div class="detail-card">
                <h3 class="section-title">
                    <svg style="width: 1.5rem; height: 1.5rem; color: #FFC800;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Información Personal
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                        <div class="info-value">{{ $cliente->user->email }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-calendar-alt"></i> Fecha de Registro</div>
                        <div class="info-value">{{ $cliente->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Información de Empresa -->
            <div class="detail-card">
                <h3 class="section-title">
                    <svg style="width: 1.5rem; height: 1.5rem; color: #FFC800;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Información de Empresa
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-building"></i> Empresa</div>
                        <div class="info-value">{{ $cliente->empresa ?? 'No especificado' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-id-card"></i> RUT</div>
                        <div class="info-value">{{ $cliente->rut ?? 'No especificado' }}</div>
                    </div>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <div class="info-label"><i class="fas fa-briefcase"></i> Giro</div>
                        <div class="info-value">{{ $cliente->giro ?? 'No especificado' }}</div>
                    </div>
                </div>
            </div>

            <!-- Información de Contacto -->
            <div class="detail-card">
                <h3 class="section-title">
                    <svg style="width: 1.5rem; height: 1.5rem; color: #FFC800;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Información de Contacto
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-phone"></i> Teléfono Principal</div>
                        <div class="info-value">{{ $cliente->telefono ?? 'No especificado' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-mobile-alt"></i> Teléfono Secundario</div>
                        <div class="info-value">{{ $cliente->telefono_secundario ?? 'No especificado' }}</div>
                    </div>
                </div>
            </div>

            <!-- Información de Ubicación -->
            <div class="detail-card">
                <h3 class="section-title">
                    <svg style="width: 1.5rem; height: 1.5rem; color: #FFC800;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Ubicación
                </h3>
                <div class="info-grid">
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <div class="info-label"><i class="fas fa-map-marker-alt"></i> Dirección</div>
                        <div class="info-value">{{ $cliente->direccion ?? 'No especificado' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-city"></i> Ciudad</div>
                        <div class="info-value">{{ $cliente->ciudad ?? 'No especificado' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-map"></i> Región</div>
                        <div class="info-value">{{ $cliente->region ?? 'No especificado' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-mailbox"></i> Código Postal</div>
                        <div class="info-value">{{ $cliente->codigo_postal ?? 'No especificado' }}</div>
                    </div>
                </div>
            </div>

            <!-- Notas -->
            @if($cliente->notas)
            <div class="detail-card">
                <h3 class="section-title">
                    <svg style="width: 1.5rem; height: 1.5rem; color: #FFC800;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Notas Adicionales
                </h3>
                <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; border-left: 4px solid #FFC800;">
                    <p style="color: #374151; line-height: 1.6;">{{ $cliente->notas }}</p>
                </div>
            </div>
            @endif

            <!-- Botón de Acción -->
            <div style="display: flex; justify-content: center; margin-top: 2rem;">
                <a href="{{ route('clientes.index') }}" style="padding: 0.75rem 2rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(248, 184, 0, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
