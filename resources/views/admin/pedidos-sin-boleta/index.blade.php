<x-app-layout>

    <x-slot name="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-receipt" style="margin-right:6px;color:#FF8100;"></i> Pedidos sin boleta
            </h2>
            <a href="{{ route('admin.pedidos-sin-boleta.index') }}" style="background:#f3f4f6;color:#374151;padding:0.45rem 0.9rem;border-radius:0.5rem;font-size:0.8rem;font-weight:600;text-decoration:none;">
                <i class="fas fa-sync"></i> Actualizar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:0.5rem;margin-bottom:1.5rem;color:#065f46;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div style="background:#fee2e2;border:1px solid #fca5a5;padding:1rem;border-radius:0.5rem;margin-bottom:1.5rem;color:#991b1b;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
                <p style="margin:0;font-size:0.85rem;color:#6b7280;">
                    Pedidos <strong>pagados</strong> en Shopify (últimos 7 días) que aún <strong>no tienen boleta/factura</strong> emitida.
                    Se excluyen pedidos de $0 y los de las últimas 3 horas (pueden estar emitiéndose). El sistema también revisa esto automáticamente cada 6 horas y avisa por correo.
                </p>
            </div>

            @if($total === 0)
                <div style="background:#ecfdf5;border:1px solid #6ee7b7;padding:2rem;border-radius:0.75rem;text-align:center;color:#065f46;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">✅</div>
                    <p style="margin:0;font-size:1rem;font-weight:600;">Todo en orden</p>
                    <p style="margin:0.25rem 0 0;font-size:0.85rem;">Todos los pedidos pagados tienen su documento emitido.</p>
                </div>
            @else
                <div style="background:#fff7ec;border:1px solid #fcd9a8;padding:1rem 1.25rem;border-radius:0.75rem;margin-bottom:1.25rem;color:#9a3412;font-weight:600;">
                    ⚠️ {{ $total }} pedido(s) pagado(s) sin boleta
                </div>

                @foreach($reporte as $r)
                    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;overflow:hidden;margin-bottom:1.5rem;">
                        <div style="background:#f9fafb;padding:0.75rem 1.25rem;border-bottom:1px solid #e5e7eb;font-weight:700;font-size:0.9rem;color:#374151;">
                            <i class="fas fa-store" style="color:#FF8100;margin-right:6px;"></i> {{ $r['tienda'] }}
                            <span style="background:#fee2e2;color:#991b1b;border-radius:9999px;padding:0.1rem 0.55rem;font-size:0.7rem;margin-left:0.5rem;">{{ count($r['huerfanos']) }}</span>
                        </div>
                        <div style="overflow-x:auto;">
                            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                                <thead>
                                    <tr style="background:#fafafa;color:#6b7280;text-transform:uppercase;font-size:0.7rem;">
                                        <th style="padding:0.6rem 1.25rem;text-align:left;">Pedido</th>
                                        <th style="padding:0.6rem 1.25rem;text-align:left;">Cliente</th>
                                        <th style="padding:0.6rem 1.25rem;text-align:right;">Monto</th>
                                        <th style="padding:0.6rem 1.25rem;text-align:left;">Fecha</th>
                                        <th style="padding:0.6rem 1.25rem;text-align:center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($r['huerfanos'] as $h)
                                        <tr style="border-top:1px solid #f3f4f6;">
                                            <td style="padding:0.7rem 1.25rem;font-weight:700;color:#111827;">#{{ $h['number'] }}</td>
                                            <td style="padding:0.7rem 1.25rem;color:#374151;">{{ $h['cliente'] }}</td>
                                            <td style="padding:0.7rem 1.25rem;text-align:right;font-weight:600;color:#FF8100;">${{ number_format($h['total'], 0, ',', '.') }}</td>
                                            <td style="padding:0.7rem 1.25rem;color:#9ca3af;font-size:0.8rem;">{{ $h['fecha'] }}</td>
                                            <td style="padding:0.7rem 1.25rem;text-align:center;">
                                                <form method="POST" action="{{ route('admin.pedidos-sin-boleta.emitir') }}" onsubmit="return confirm('¿Emitir la boleta/factura de este pedido por su monto pagado?');" style="margin:0;">
                                                    @csrf
                                                    <input type="hidden" name="order_id" value="{{ $h['order_id'] }}">
                                                    <input type="hidden" name="user_id" value="{{ $r['user_id'] }}">
                                                    <button type="submit" style="background:#16a34a;color:#fff;border:none;padding:0.4rem 0.9rem;border-radius:0.4rem;font-size:0.78rem;font-weight:700;cursor:pointer;">
                                                        Emitir boleta
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
