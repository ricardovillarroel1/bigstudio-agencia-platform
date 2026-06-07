<x-app-layout>

    <x-slot name="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Pagos por Transferencia
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div style="background: #d1fae5; border: 1px solid #6ee7b7; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; color: #065f46;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div style="background: #fee2e2; border: 1px solid #fca5a5; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; color: #991b1b;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            <!-- Estad&iacute;sticas r&aacute;pidas -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Pendientes</p>
                    <p style="font-size: 2rem; font-weight: 700; color: #f59e0b; margin: 0.25rem 0 0;">{{ $transferencias->where('status', 'pendiente')->count() }}</p>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Aprobados</p>
                    <p style="font-size: 2rem; font-weight: 700; color: #10b981; margin: 0.25rem 0 0;">{{ $transferencias->where('status', 'aprobado')->count() }}</p>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #ef4444;">
                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Rechazados</p>
                    <p style="font-size: 2rem; font-weight: 700; color: #ef4444; margin: 0.25rem 0 0;">{{ $transferencias->where('status', 'rechazado')->count() }}</p>
                </div>
            </div>

            <!-- Lista de transferencias -->
            @forelse($transferencias as $t)
                <div style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1rem; overflow: hidden; border-left: 4px solid {{ $t->status === 'pendiente' ? '#f59e0b' : ($t->status === 'aprobado' ? '#10b981' : '#ef4444') }};">
                    <div style="padding: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                            <!-- Info del cliente -->
                            <div style="flex: 1; min-width: 200px;">
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #FFD54F, #FFC800); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #1a1a1a;">
                                        {{ strtoupper(substr($t->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p style="font-weight: 700; color: #111827; margin: 0;">{{ $t->user->name ?? 'Usuario' }}</p>
                                        <p style="color: #6b7280; font-size: 0.85rem; margin: 0;">{{ $t->user->email ?? '' }}</p>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600;">{{ $t->plan->nombre ?? 'Plan' }}</span>
                                    <span style="background: #f3e8ff; color: #7c3aed; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600;">{{ ucfirst($t->periodo) }}</span>
                                    <span style="background: {{ $t->status === 'pendiente' ? '#fef3c7' : ($t->status === 'aprobado' ? '#d1fae5' : '#fee2e2') }}; color: {{ $t->status === 'pendiente' ? '#92400e' : ($t->status === 'aprobado' ? '#065f46' : '#991b1b') }}; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600;">
                                        {{ $t->status === 'pendiente' ? 'Pendiente' : ($t->status === 'aprobado' ? 'Aprobado' : 'Rechazado') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Monto -->
                            <div style="text-align: right; min-width: 150px;">
                                <p style="font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0;">${{ number_format($t->monto, 0, ',', '.') }} CLP</p>
                                <p style="color: #6b7280; font-size: 0.8rem; margin: 0.25rem 0 0;">{{ $t->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        <!-- Comprobante -->
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <p style="color: #6b7280; font-size: 0.85rem; margin: 0 0 0.25rem;">
                                        <i class="fas fa-paperclip"></i> Comprobante: <strong>{{ $t->comprobante_original_name ?? 'archivo' }}</strong>
                                    </p>
                                    <a href="{{ asset('storage/' . $t->comprobante_path) }}" target="_blank" style="color: #3b82f6; font-weight: 600; font-size: 0.85rem; text-decoration: none;">
                                        <i class="fas fa-eye"></i> Ver comprobante
                                    </a>
                                </div>

                                @if($t->status === 'pendiente')
                                    <div style="display: flex; gap: 0.5rem;">
                                        <!-- Bot&oacute;n Aprobar -->
                                        <form action="{{ route('admin.transferencias.aprobar', $t->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="notas_admin" value="Pago verificado y aprobado">
                                            <button type="submit" onclick="return confirm('&iquest;Est&aacute;s seguro de aprobar este pago? Se activar&aacute; el plan para el cliente.')" style="padding: 0.5rem 1.25rem; background: linear-gradient(135deg, #10b981, #059669); color: white; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.85rem;">
                                                <i class="fas fa-check"></i> Aprobar
                                            </button>
                                        </form>
                                        <!-- Bot&oacute;n Rechazar -->
                                        <button onclick="document.getElementById('rechazarForm{{ $t->id }}').style.display = document.getElementById('rechazarForm{{ $t->id }}').style.display === 'none' ? 'block' : 'none'" style="padding: 0.5rem 1.25rem; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.85rem;">
                                            <i class="fas fa-times"></i> Rechazar
                                        </button>
                                    </div>
                                @endif

                                @if($t->status !== 'pendiente' && $t->notas_admin)
                                    <div style="background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.85rem;">
                                        <p style="color: #6b7280; margin: 0;"><strong>Nota:</strong> {{ $t->notas_admin }}</p>
                                        @if($t->revisor)
                                            <p style="color: #9ca3af; margin: 0.25rem 0 0; font-size: 0.8rem;">Por: {{ $t->revisor->name }} - {{ $t->revisado_at->format('d/m/Y H:i') }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Formulario de rechazo (oculto) -->
                            @if($t->status === 'pendiente')
                                <div id="rechazarForm{{ $t->id }}" style="display: none; margin-top: 1rem; background: #fef2f2; padding: 1rem; border-radius: 0.5rem;">
                                    <form action="{{ route('admin.transferencias.rechazar', $t->id) }}" method="POST">
                                        @csrf
                                        <label style="display: block; font-weight: 600; color: #991b1b; margin-bottom: 0.5rem;">Motivo del rechazo:</label>
                                        <textarea name="notas_admin" required rows="2" style="width: 100%; padding: 0.5rem; border: 1px solid #fca5a5; border-radius: 0.375rem; margin-bottom: 0.5rem; font-size: 0.9rem;" placeholder="Ej: Comprobante ilegible, monto no coincide..."></textarea>
                                        <button type="submit" style="padding: 0.5rem 1rem; background: #ef4444; color: white; font-weight: 600; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.85rem;">
                                            Confirmar Rechazo
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="background: white; padding: 3rem; border-radius: 0.75rem; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                    <p style="color: #6b7280; font-size: 1.125rem;">No hay pagos por transferencia registrados.</p>
                </div>
            @endforelse

            <!-- Paginaci&oacute;n -->
            <div style="margin-top: 1.5rem;">
                {{ $transferencias->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
