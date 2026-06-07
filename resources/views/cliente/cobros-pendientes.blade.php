<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cobros Pendientes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Cobros Pendientes --}}
            @php
                $cobrosPendientes = $cobros->where('estado', 'pendiente');
                $cobrosHistorial = $cobros->where('estado', '!=', 'pendiente');
            @endphp

            @if($cobrosPendientes->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: #dc2626;">
                            <svg style="width: 1.25rem; height: 1.25rem; display: inline; margin-right: 0.5rem; vertical-align: text-bottom;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Pagos Pendientes ({{ $cobrosPendientes->count() }})
                        </h3>

                        <div style="display: grid; gap: 1rem;">
                            @foreach($cobrosPendientes as $cobro)
                                <div style="border: 2px solid #fbbf24; border-radius: 0.75rem; padding: 1.5rem; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                        <div>
                                            <p style="font-weight: 700; font-size: 1rem; color: #1f2937;">{{ $cobro->concepto }}</p>
                                            <p style="font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem;">Asignado el {{ $cobro->created_at->format('d/m/Y') }}</p>
                                        </div>
                                        <div style="text-align: right;">
                                            <p style="font-size: 1.5rem; font-weight: 800; color: #059669;">${{ number_format($cobro->monto, 0, ',', '.') }} CLP</p>
                                        </div>
                                    </div>
                                    <div style="margin-top: 1rem; text-align: right;">
                                        <form action="{{ route('flow.create-payment') }}" method="POST" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="subject" value="Cobro: {{ $cobro->concepto }}">
                                            <input type="hidden" name="amount" value="{{ $cobro->monto }}">
                                            <input type="hidden" name="cobro_id" value="{{ $cobro->id }}">
                                            <button type="submit" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; padding: 0.75rem 2rem; border-radius: 0.5rem; border: none; cursor: pointer; font-size: 0.9rem; text-transform: uppercase; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.3s;"
                                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(248, 184, 0, 0.5)'"
                                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'">
                                                <svg style="width: 1rem; height: 1rem; display: inline; margin-right: 0.5rem; vertical-align: text-bottom;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                                Pagar con Flow
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: #059669;">No tienes cobros pendientes</h3>
                        <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem;">Todos tus pagos están al día.</p>
                    </div>
                </div>
            @endif

            {{-- Historial de cobros pagados/anulados --}}
            @if($cobrosHistorial->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: #1f2937;">
                            Historial de Cobros
                        </h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                    <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Fecha</th>
                                    <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Concepto</th>
                                    <th style="padding: 0.75rem; text-align: right; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Monto</th>
                                    <th style="padding: 0.75rem; text-align: center; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cobrosHistorial as $cobro)
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 0.75rem; font-size: 0.875rem;">{{ $cobro->created_at->format('d/m/Y') }}</td>
                                        <td style="padding: 0.75rem; font-size: 0.875rem;">{{ $cobro->concepto }}</td>
                                        <td style="padding: 0.75rem; font-size: 0.875rem; text-align: right; font-weight: 700;">${{ number_format($cobro->monto, 0, ',', '.') }} CLP</td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            @if($cobro->estado === 'pagado')
                                                <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Pagado</span>
                                            @else
                                                <span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Anulado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
