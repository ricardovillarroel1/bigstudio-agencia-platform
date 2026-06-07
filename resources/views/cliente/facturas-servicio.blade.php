<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Facturas</span> de Servicio
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- HEADER EXPLICATIVO BigStudio --}}
            <div class="rounded-2xl p-6 flex items-start gap-4"
                 style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border: 1px solid #FFD89C;">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #FFC800 0%, #FF8100 100%); box-shadow: 0 4px 12px rgba(255, 129, 0, 0.3);">
                    <i class="fas fa-file-invoice-dollar text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="bs-display text-xl m-0" style="color: #8A4400;">Tu plan Big Studio</h3>
                    <p class="text-sm mt-1.5 mb-0" style="color: #5C2D00; line-height: 1.5;">
                        Aqu&iacute; ves las facturas que <strong>Big Studio te emite a ti</strong> por usar la plataforma de integraci&oacute;n.
                        Si pagas por Flow se marcan como pagadas autom&aacute;ticamente. Si pagas por transferencia, env&iacute;a el comprobante por chat.
                    </p>
                </div>
            </div>

            {{-- Alerta de suscripci&oacute;n pausada --}}
            @if($suscripcion && $suscripcion->pausada)
            <div class="rounded-xl p-5 flex items-center gap-4" style="background: #FEF2F2; border: 2px solid #EF4444;">
                <span class="text-3xl">&#9888;&#65039;</span>
                <div>
                    <h3 class="text-base font-bold text-red-700 m-0">Servicio Pausado</h3>
                    <p class="text-sm text-red-900 mt-1 mb-0">Tu servicio est&aacute; pausado por una factura pendiente de pago. La emisi&oacute;n de documentos electr&oacute;nicos se reanudar&aacute; autom&aacute;ticamente al pagar la factura pendiente.</p>
                </div>
            </div>
            @endif

            {{-- Uso del ciclo actual (look BigStudio) --}}
            @if($usoCiclo)
            @php
                $docsEmitidos = (int) ($usoCiclo['docs_emitidos'] ?? 0);
                $limite       = (int) ($usoCiclo['limite_incluido'] ?? 0);
                $docsExtra    = (int) ($usoCiclo['docs_extra'] ?? 0);
                $montoExtra   = (int) ($usoCiclo['monto_extra_clp'] ?? 0);
                $pctDocs      = $limite > 0 ? min(100, round(($docsEmitidos / $limite) * 100, 1)) : 0;
                $colorPct     = $pctDocs >= 90 ? '#DC2626' : ($pctDocs >= 75 ? '#FF8100' : '#059669');
            @endphp
            <div class="bs-card bs-card-body">
                <div class="flex justify-between items-end gap-4 flex-wrap">
                    <div class="flex-1 min-w-[200px]">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide m-0">Documentos del ciclo actual</p>
                        <p class="bs-display text-4xl text-gray-900 mt-1 mb-0 leading-none">
                            {{ number_format($docsEmitidos, 0, ',', '.') }}<span class="text-gray-400 text-2xl font-medium"> / {{ $limite > 0 ? number_format($limite, 0, ',', '.') : '∞' }}</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide m-0">Uso del plan</p>
                        <p class="bs-display text-3xl mt-1 mb-0 leading-none" style="color: {{ $colorPct }};">
                            {{ number_format($pctDocs, 1, ',', '.') }}<span class="text-base">%</span>
                        </p>
                    </div>
                </div>
                <div class="bs-progress mt-3"><div class="bs-progress-fill" style="width: {{ $pctDocs }}%;"></div></div>

                <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mt-5 pt-4 border-t border-gray-100">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide m-0">Documentos extra</p>
                        <p class="bs-display text-xl mt-1 mb-0 {{ $docsExtra > 0 ? 'text-brand-600' : 'text-gray-700' }}">
                            {{ number_format($docsExtra, 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide m-0">Cobro extra estimado</p>
                        <p class="bs-display text-xl mt-1 mb-0 {{ $montoExtra > 0 ? 'text-brand-600' : 'text-gray-700' }}">
                            ${{ number_format($montoExtra, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Tabla de facturas --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header">
                    <h3 class="font-bold text-gray-800 m-0">Historial de facturas</h3>
                    <span class="text-xs text-gray-500">{{ $facturas->count() }} {{ $facturas->count() === 1 ? 'factura' : 'facturas' }}</span>
                </div>

                @if($facturas->count() > 0)
                <div class="overflow-x-auto">
                    <table class="bs-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>N&deg; Factura</th>
                                <th>Concepto</th>
                                <th>Per&iacute;odo</th>
                                <th class="text-center">Docs Extra</th>
                                <th class="text-right">Neto</th>
                                <th class="text-right">IVA</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facturas as $factura)
                            <tr>
                                <td>
                                    @if($factura->estado === 'pendiente')
                                    <input type="checkbox" class="factura-checkbox cursor-pointer" value="{{ $factura->id }}" data-monto="{{ (int)$factura->monto }}" style="width: 18px; height: 18px;">
                                    @endif
                                </td>
                                <td class="font-semibold text-gray-900">
                                    @if($factura->numero_factura)
                                        {{ $factura->numero_factura }}
                                    @elseif($factura->folio)
                                        <span class="text-brand-600">FS-{{ str_pad($factura->folio, 6, '0', STR_PAD_LEFT) }}</span>
                                    @else
                                        <span class="text-gray-400">&mdash;</span>
                                    @endif
                                    @if($factura->folio)
                                        <div class="text-xs text-gray-400 font-normal mt-0.5">Folio SII: {{ $factura->folio }}</div>
                                    @endif
                                </td>
                                <td class="text-gray-600 text-xs">{{ $factura->concepto }}</td>
                                <td class="text-gray-500 text-xs">
                                    {{ $factura->periodo_inicio ? $factura->periodo_inicio->format('d/m/y') : '-' }} &mdash;
                                    {{ $factura->periodo_fin ? $factura->periodo_fin->format('d/m/y') : '-' }}
                                </td>
                                <td class="text-center font-bold {{ $factura->documentos_extra > 0 ? 'text-brand-600' : 'text-gray-400' }}">
                                    {{ $factura->documentos_extra > 0 ? $factura->documentos_extra : '—' }}
                                </td>
                                <td class="text-right text-gray-600">${{ number_format($factura->neto_clp ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right text-gray-600">${{ number_format($factura->iva_clp ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right font-bold text-gray-900">${{ number_format($factura->total_clp ?? 0, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($factura->estado === 'pagada')
                                        <span class="bs-badge-success">Pagada</span>
                                    @elseif($factura->estado === 'pendiente')
                                        <span class="bs-badge-warning">Pendiente</span>
                                    @else
                                        <span class="bs-badge-neutral">{{ ucfirst($factura->estado) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($factura->pdf_base64)
                                        <a href="{{ route('factura-servicio.pdf', $factura->id) }}"
                                           class="bs-btn-ghost bs-btn-sm"
                                           title="Descargar PDF">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                    @elseif($factura->monto == 0)
                                        <span class="text-xs text-gray-400">Plan Gratis</span>
                                    @else
                                        <span class="text-xs text-amber-600"><i class="fas fa-clock"></i> Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Barra de pago BigStudio --}}
                <div id="payment-bar" class="px-6 py-5 hidden"
                     style="background: linear-gradient(90deg, #FFF7EC 0%, #FFEDD0 100%); border-top: 2px solid #FF8100;">
                    <div class="flex justify-between items-center flex-wrap gap-3">
                        <div>
                            <p class="font-bold m-0" style="color: #8A4400;">Facturas seleccionadas: <span id="selected-count">0</span></p>
                            <p class="text-sm mt-1 mb-0" style="color: #5C2D00;">Total a pagar: <strong id="selected-total">$0</strong></p>
                        </div>
                        <button onclick="pagarFacturasSeleccionadas()" class="bs-btn-primary bs-btn-lg">
                            <i class="fas fa-credit-card mr-2"></i> Pagar con Flow
                        </button>
                    </div>
                </div>
                @else
                {{-- Empty state BigStudio --}}
                <div class="p-12 text-center">
                    <div class="inline-block w-20 h-20 rounded-2xl mb-4 flex items-center justify-center"
                         style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                        <i class="fas fa-file-invoice-dollar text-3xl text-brand-600"></i>
                    </div>
                    <h4 class="bs-display text-xl text-gray-700 m-0">A&uacute;n no tienes facturas de servicio</h4>
                    <p class="text-sm text-gray-500 mt-2 mb-0">Cuando se genere tu primera factura aparecer&aacute; aqu&iacute;.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.factura-checkbox');
        const paymentBar = document.getElementById('payment-bar');
        const selectedCount = document.getElementById('selected-count');
        const selectedTotal = document.getElementById('selected-total');

        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', updateSelection);
        });

        function updateSelection() {
            let count = 0;
            let total = 0;
            checkboxes.forEach(function(cb) {
                if (cb.checked) {
                    count++;
                    total += parseInt(cb.dataset.monto);
                }
            });

            selectedCount.textContent = count;
            selectedTotal.textContent = '$' + total.toLocaleString('es-CL');
            if (count > 0) {
                paymentBar.classList.remove('hidden');
            } else {
                paymentBar.classList.add('hidden');
            }
        }
    });

    function pagarFacturasSeleccionadas() {
        const checkboxes = document.querySelectorAll('.factura-checkbox:checked');
        if (checkboxes.length === 0) return;

        const facturaId = checkboxes[0].value;

        fetch('{{ route("factura-servicio.pagar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ factura_id: facturaId }),
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                alert(data.message || 'Error al procesar el pago');
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('Error de conexión');
        });
    }
    </script>
</x-app-layout>
