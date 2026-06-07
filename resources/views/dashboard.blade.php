<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Panel</span> Principal
        </h2>
    </x-slot>

    <style>
        .dashboard-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            transition: all 0.3s ease;
            overflow: visible;
        }

        .dashboard-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .welcome-card {
            background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);
            color: #1a1a1a;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 10px 30px -5px rgba(255, 129, 0, 0.45);
            margin-bottom: 2rem;
        }

        .welcome-card h3 {
            color: #1a1a1a;
            font-family: 'Mostin', system-ui, sans-serif;
            font-weight: 900;
        }

        .welcome-emoji {
            filter: grayscale(0) brightness(1.2) contrast(1.1);
            display: inline-block;
            font-size: 1.5em;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #FFC800;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateX(4px);
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 1024px) {
            .main-grid {
                grid-template-columns: 350px 1fr;
            }
        }

        .stats-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
    </style>

    <div class="py-6">
        <div style="max-width: 100%; padding: 0 1.5rem;">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h3 class="text-2xl font-bold mb-2">Bienvenido, {{ auth()->user()->name }}! <span class="welcome-emoji"></span></h3>
                <p class="text-lg opacity-80">Panel de administración - Sistema de Integración Shopify</p>
            </div>

            @if(auth()->user()->isAdmin())
            <!-- Main Content Grid -->
            <div class="main-grid">
                <!-- Left Column - Stats -->
                <div class="stats-column">
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-semibold">Total Clientes</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">{{ \App\Models\User::role('cliente')->count() }}</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-semibold">Integraciones Activas</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">3</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-semibold">Estado Sistema</p>
                                <p class="text-3xl font-bold text-green-600 mt-2">Online</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Revenue -->
                <div>
                    <!-- Revenue Section -->
                    <div class="dashboard-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <h3 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">Ingresos por Planes</h3>
                                <p style="font-size: 0.75rem; color: #6B7280; margin-top: 0.25rem;">Planes en UF convertidos a CLP (1 UF = ${{ number_format($ufValue, 0, ',', '.') }})</p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="{{ route('dashboard', ['revenue_filter' => 'day']) }}" style="padding: 0.4rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; text-decoration: none; transition: all 0.3s; {{ $revenueFilter === 'day' ? 'background: #FFC800; color: #000; box-shadow: 0 2px 8px rgba(255,200,0,0.4);' : 'background: #F3F4F6; color: #4B5563;' }}">Dia</a>
                                <a href="{{ route('dashboard', ['revenue_filter' => 'month']) }}" style="padding: 0.4rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; text-decoration: none; transition: all 0.3s; {{ $revenueFilter === 'month' ? 'background: #FFC800; color: #000; box-shadow: 0 2px 8px rgba(255,200,0,0.4);' : 'background: #F3F4F6; color: #4B5563;' }}">Mes</a>
                                <a href="{{ route('dashboard', ['revenue_filter' => 'year']) }}" style="padding: 0.4rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; text-decoration: none; transition: all 0.3s; {{ $revenueFilter === 'year' ? 'background: #FFC800; color: #000; box-shadow: 0 2px 8px rgba(255,200,0,0.4);' : 'background: #F3F4F6; color: #4B5563;' }}">Año</a>
                                <a href="{{ route('dashboard', ['revenue_filter' => 'all']) }}" style="padding: 0.4rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; text-decoration: none; transition: all 0.3s; {{ $revenueFilter === 'all' ? 'background: #FFC800; color: #000; box-shadow: 0 2px 8px rgba(255,200,0,0.4);' : 'background: #F3F4F6; color: #4B5563;' }}">Total</a>
                            </div>
                        </div>

                        <!-- Sub-filters for Day/Month/Year -->
                        @if($revenueFilter === 'day')
                        <form method="GET" action="{{ route('dashboard') }}" style="display: flex; gap: 0.5rem; margin-bottom: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="revenue_filter" value="day">
                            <input type="date" name="filter_date" value="{{ $filterDate ?? date('Y-m-d') }}" style="padding: 0.4rem 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.5rem; font-size: 0.85rem; color: #374151;">
                            <button type="submit" style="padding: 0.4rem 1rem; background: #FFC800; color: #000; border: none; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Filtrar</button>
                        </form>
                        @elseif($revenueFilter === 'month')
                        <form method="GET" action="{{ route('dashboard') }}" style="display: flex; gap: 0.5rem; margin-bottom: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="revenue_filter" value="month">
                            <select name="filter_month" style="padding: 0.4rem 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.5rem; font-size: 0.85rem; color: #374151;">
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" {{ ($filterMonth ?? now()->month) == $num ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <select name="filter_year" style="padding: 0.4rem 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.5rem; font-size: 0.85rem; color: #374151;">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ ($filterYear ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                            <button type="submit" style="padding: 0.4rem 1rem; background: #FFC800; color: #000; border: none; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Filtrar</button>
                        </form>
                        @elseif($revenueFilter === 'year')
                        <form method="GET" action="{{ route('dashboard') }}" style="display: flex; gap: 0.5rem; margin-bottom: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="revenue_filter" value="year">
                            <select name="filter_year" style="padding: 0.4rem 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.5rem; font-size: 0.85rem; color: #374151;">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ ($filterYear ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                            <button type="submit" style="padding: 0.4rem 1rem; background: #FFC800; color: #000; border: none; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Filtrar</button>
                        </form>
                        @endif

                        <!-- Green Revenue Box -->
                        <div style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); border-radius: 1rem; padding: 1.5rem; color: white; margin-bottom: 1rem;">
                            <p style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.8; margin: 0 0 0.25rem 0;">
                                @if($revenueFilter === 'day') Ingresos del Dia @elseif($revenueFilter === 'month') Ingresos del Mes @elseif($revenueFilter === 'year') Ingresos del Año @else Ingresos Totales @endif
                            </p>
                            <p style="font-size: 2.5rem; font-weight: 800; margin: 0;">${{ number_format($totalRevenue, 0, ',', '.') }} <span style="font-size: 1rem; font-weight: 400; opacity: 0.8;">CLP</span></p>
                            <p style="font-size: 0.8rem; opacity: 0.7; margin: 0.5rem 0 0 0;">
                                {{ $totalPlanCount }} {{ $totalPlanCount === 1 ? 'plan contratado' : 'planes contratados' }}
                                @if($totalPayments > 0)
                                    + {{ $totalPayments }} {{ $totalPayments === 1 ? 'pago' : 'pagos' }} Flow
                                @endif
                            </p>
                        </div>

                        <!-- Breakdown -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
                            <div style="background: #F9FAFB; border-radius: 0.75rem; padding: 1rem; border: 1px solid #E5E7EB;">
                                <p style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6B7280; margin: 0;">Planes (UF a CLP)</p>
                                <p style="font-size: 1.25rem; font-weight: 800; color: #111827; margin: 0.25rem 0 0 0;">${{ number_format($totalPlanRevenue, 0, ',', '.') }}</p>
                                <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0;">CLP</p>
                            </div>
                            <div style="background: #F9FAFB; border-radius: 0.75rem; padding: 1rem; border: 1px solid #E5E7EB;">
                                <p style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6B7280; margin: 0;">Hoy</p>
                                <p style="font-size: 1.25rem; font-weight: 800; color: #111827; margin: 0.25rem 0 0 0;">${{ number_format($revenueToday, 0, ',', '.') }}</p>
                                <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0;">CLP</p>
                            </div>
                            <div style="background: #F9FAFB; border-radius: 0.75rem; padding: 1rem; border: 1px solid #E5E7EB;">
                                <p style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6B7280; margin: 0;">Este Mes</p>
                                <p style="font-size: 1.25rem; font-weight: 800; color: #111827; margin: 0.25rem 0 0 0;">${{ number_format($revenueMonth, 0, ',', '.') }}</p>
                                <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0;">CLP</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="background: white; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 1.5rem;">
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem;">Acciones R&aacute;pidas</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; width: 100%; box-sizing: border-box;">
                    <a href="{{ route('usuarios.index') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #000; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
                        <i class="fas fa-users" style="margin-right: 0.5rem;"></i> Usuarios
                    </a>
                    <a href="{{ route('integracion.index') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #000; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
                        <i class="fas fa-plug" style="margin-right: 0.5rem;"></i> Integracion
                    </a>
                    <a href="{{ route('warehouse.config') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #000; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
                        <i class="fas fa-warehouse" style="margin-right: 0.5rem;"></i> Bodegas
                    </a>
                    <a href="{{ route('boletas.index') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #000; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
                        <i class="fas fa-file-invoice" style="margin-right: 0.5rem;"></i> Documentos
                    </a>
                    <a href="{{ route('admin.trazabilidad-sku') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1rem; background: linear-gradient(135deg, #FF9C00 0%, #FF8100 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
                        <i class="fas fa-route" style="margin-right: 0.5rem;"></i> Trazabilidad
                    </a>
                    <a href="{{ route('admin.chats') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1rem; background: linear-gradient(135deg, #FF9C00 0%, #FF8100 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
                        <i class="fas fa-comments" style="margin-right: 0.5rem;"></i> Chats
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
