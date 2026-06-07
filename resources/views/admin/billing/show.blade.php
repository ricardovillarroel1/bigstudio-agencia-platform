<x-app-layout>

    {{-- BigStudio brand fonts (Mostin) --}}
    <style>
        @font-face {
            font-family: 'Mostin';
            src: url('{{ asset('fonts/mostin/MostinRegular-3z6wy.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Mostin';
            src: url('{{ asset('fonts/mostin/MostinMedium-p75Rv.ttf') }}') format('truetype');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Mostin';
            src: url('{{ asset('fonts/mostin/MostinBold-OV5Oo.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Mostin';
            src: url('{{ asset('fonts/mostin/MostinBlack-ZV5Gl.ttf') }}') format('truetype');
            font-weight: 900;
            font-style: normal;
            font-display: swap;
        }
        .bs-mostin { font-family: 'Mostin', system-ui, -apple-system, sans-serif; }
        .bs-progress-fill {
            background: linear-gradient(90deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);
            transition: width 0.5s ease;
        }
    </style>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Facturaci&oacute;n - {{ $cliente->name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Botón volver --}}
            <div style="margin-bottom: 16px;">
                <a href="{{ route('admin.billing.index') }}" style="color: #6B7280; text-decoration: none; font-size: 0.875rem;">&larr; Volver a lista de clientes</a>
            </div>

            {{-- Info del cliente y datos de facturación --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="font-weight: 700; color: #374151; margin: 0 0 12px 0;">Datos del Cliente</h3>
                    <p style="margin: 4px 0;"><strong>Nombre:</strong> {{ $cliente->name }}</p>
                    <p style="margin: 4px 0;"><strong>Email:</strong> {{ $cliente->email }}</p>
                    @if($suscripcion)
                    <p style="margin: 4px 0;"><strong>Plan:</strong> {{ $suscripcion->plan->nombre ?? 'N/A' }}</p>
                    <p style="margin: 4px 0;"><strong>Ciclo:</strong> {{ $suscripcion->fecha_inicio->format('d/m/Y') }} - {{ $suscripcion->fecha_fin->format('d/m/Y') }}</p>
                    <p style="margin: 4px 0;"><strong>Estado:</strong>
                        @if($suscripcion->pausada)
                            <span style="color: #DC2626; font-weight: 700;">PAUSADO</span>
                        @else
                            <span style="color: #065F46; font-weight: 700;">ACTIVO</span>
                        @endif
                    </p>
                    @endif
                </div>
                <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="font-weight: 700; color: #374151; margin: 0 0 12px 0;">Datos de Facturaci&oacute;n</h3>
                    <p style="margin: 4px 0;"><strong>Raz&oacute;n Social:</strong> {{ $datosFacturacion['razon_social'] }}</p>
                    <p style="margin: 4px 0;"><strong>RUT:</strong> {{ $datosFacturacion['rut'] }}</p>
                    <p style="margin: 4px 0;"><strong>Giro:</strong> {{ $datosFacturacion['giro'] }}</p>
                    <p style="margin: 4px 0;"><strong>Direcci&oacute;n:</strong> {{ $datosFacturacion['direccion'] }}</p>
                </div>
            </div>

            {{-- Uso del ciclo actual --}}
            @if($usoCiclo)
            <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="font-weight: 700; color: #374151; margin: 0;">Uso del Ciclo Actual</h3>
                    @if($suscripcion)
                    <div id="bs-actions-wrapper" style="position: relative;">
                        <button type="button" onclick="bsToggleMenu(event)"
                                style="background: white; color: #374151; border: 1px solid #E5E7EB; border-radius: 8px; padding: 8px 14px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.15s;"
                                onmouseover="this.style.borderColor='#FF9C00'; this.style.background='#FFFBEB';"
                                onmouseout="this.style.borderColor='#E5E7EB'; this.style.background='white';">
                            <span style="font-size: 1rem;">&#9881;</span>
                            Acciones de suscripci&oacute;n
                            <span style="font-size: 0.65rem; color: #9CA3AF;">&#9660;</span>
                        </button>
                        <div id="bs-menu-dropdown" style="display: none; position: absolute; right: 0; top: calc(100% + 6px); background: white; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 12px 28px rgba(0,0,0,0.12); min-width: 240px; z-index: 50; overflow: hidden;">
                            @if($suscripcion->pausada)
                                <button type="button" onclick="bsOpenConfirm('reanudar')"
                                        style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 12px 16px; background: white; border: none; text-align: left; font-size: 0.85rem; color: #065F46; cursor: pointer; font-weight: 500;"
                                        onmouseover="this.style.background='#ECFDF5';" onmouseout="this.style.background='white';">
                                    <span>&#9654;</span> Reanudar servicio
                                </button>
                            @else
                                <button type="button" onclick="bsOpenConfirm('pausar')"
                                        style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 12px 16px; background: white; border: none; text-align: left; font-size: 0.85rem; color: #92400E; cursor: pointer; font-weight: 500;"
                                        onmouseover="this.style.background='#FEF3C7';" onmouseout="this.style.background='white';">
                                    <span>&#9208;</span> Pausar servicio
                                </button>
                            @endif
                            <div style="height: 1px; background: #F3F4F6;"></div>
                            <button type="button" onclick="bsOpenConfirm('reiniciar')"
                                    style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 12px 16px; background: white; border: none; text-align: left; font-size: 0.85rem; color: #DC2626; cursor: pointer; font-weight: 500;"
                                    onmouseover="this.style.background='#FEF2F2';" onmouseout="this.style.background='white';">
                                <span>&#8635;</span> Reiniciar ciclo
                                <span style="margin-left: auto; font-size: 0.65rem; background: #FEE2E2; color: #991B1B; padding: 2px 6px; border-radius: 4px; font-weight: 700;">IRREVERSIBLE</span>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
                @php
                    $docsEmitidos = (int) ($usoCiclo['docs_emitidos'] ?? 0);
                    $limite       = (int) ($usoCiclo['limite_incluido'] ?? 0);
                    $docsExtra    = (int) ($usoCiclo['docs_extra'] ?? 0);
                    $montoExtra   = (int) ($usoCiclo['monto_extra_clp'] ?? 0);
                    $precioExtraUF= (float) ($usoCiclo['precio_extra_uf'] ?? 0);
                    $valorUF      = (float) ($usoCiclo['valor_uf'] ?? 0);

                    $pctDocs = $limite > 0 ? min(100, round(($docsEmitidos / $limite) * 100, 1)) : 0;

                    // Ciclo actual basado en la ultima factura (refleja el periodo real de facturacion).
                    // Fallback a suscripcion.fecha_inicio/fin si no hay facturas todavia.
                    $facturaCiclo = $facturas->sortByDesc('id')->first(function ($f) {
                        return $f->periodo_inicio && $f->periodo_fin;
                    });

                    if ($facturaCiclo) {
                        $cicloInicio = $facturaCiclo->periodo_inicio;
                        $cicloFin    = $facturaCiclo->periodo_fin;
                    } elseif ($suscripcion && $suscripcion->fecha_inicio && $suscripcion->fecha_fin) {
                        $cicloInicio = $suscripcion->fecha_inicio;
                        $cicloFin    = $suscripcion->fecha_fin;
                    } else {
                        $cicloInicio = null; $cicloFin = null;
                    }

                    if ($cicloInicio && $cicloFin) {
                        $diasTotales = $cicloInicio->diffInDays($cicloFin) + 1;
                        $diaActual   = max(1, min($diasTotales, $cicloInicio->diffInDays(now()) + 1));
                    } else {
                        $diasTotales = 30; $diaActual = 1;
                    }
                    $pctTiempo = $diasTotales > 0 ? round(($diaActual / $diasTotales) * 100) : 0;

                    $tieneExtra = $docsExtra > 0 || $montoExtra > 0;
                    $colorPct = $pctDocs >= 90 ? '#DC2626' : ($pctDocs >= 75 ? '#FF8100' : '#059669');
                @endphp

                {{-- Bloque 1: Documentos del ciclo (numero grande + barra naranja BigStudio) --}}
                <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 240px;">
                        <p style="font-size: 0.7rem; color: #6B7280; margin: 0; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;">Documentos del ciclo</p>
                        <p class="bs-mostin" style="font-size: 2.75rem; font-weight: 900; color: #111827; margin: 6px 0 0 0; line-height: 1;">
                            {{ number_format($docsEmitidos, 0, ',', '.') }}<span style="color: #9CA3AF; font-weight: 500;"> / {{ $limite > 0 ? number_format($limite, 0, ',', '.') : '∞' }}</span>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <p style="font-size: 0.7rem; color: #6B7280; margin: 0; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;">Uso del plan</p>
                        <p class="bs-mostin" style="font-size: 2.25rem; font-weight: 900; color: {{ $colorPct }}; margin: 6px 0 0 0; line-height: 1;">
                            {{ number_format($pctDocs, 1, ',', '.') }}<span style="font-size: 1.25rem;">%</span>
                        </p>
                    </div>
                </div>

                <div style="background: #F3F4F6; border-radius: 999px; height: 12px; overflow: hidden; margin-top: 14px;">
                    <div class="bs-progress-fill" style="height: 100%; width: {{ $pctDocs }}%; border-radius: 999px;"></div>
                </div>

                {{-- Bloque 2: barra de tiempo del ciclo --}}
                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed #E5E7EB;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: #6B7280; margin-bottom: 6px;">
                        <span style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Tiempo del ciclo</span>
                        <span><strong style="color: #374151;">D&iacute;a {{ $diaActual }}</strong> de {{ $diasTotales }} &middot; {{ $pctTiempo }}%</span>
                    </div>
                    <div style="background: #F3F4F6; border-radius: 999px; height: 4px; overflow: hidden;">
                        <div style="background: #9CA3AF; height: 100%; width: {{ $pctTiempo }}%; border-radius: 999px;"></div>
                    </div>
                </div>

                {{-- Bloque 3: metricas secundarias compactas --}}
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #F3F4F6;">
                    <div>
                        <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Docs Extra</p>
                        <p class="bs-mostin" style="font-size: 1.375rem; font-weight: 700; color: {{ $docsExtra > 0 ? '#FF8100' : '#374151' }}; margin: 4px 0 0 0;">
                            {{ number_format($docsExtra, 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Monto Extra</p>
                        <p class="bs-mostin" style="font-size: 1.375rem; font-weight: 700; color: {{ $montoExtra > 0 ? '#FF8100' : '#374151' }}; margin: 4px 0 0 0;">
                            ${{ number_format($montoExtra, 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Precio extra (UF)</p>
                        <p class="bs-mostin" style="font-size: 1.375rem; font-weight: 700; color: #374151; margin: 4px 0 0 0;">
                            {{ number_format($precioExtraUF, 4, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Valor UF hoy</p>
                        <p class="bs-mostin" style="font-size: 1.375rem; font-weight: 700; color: #374151; margin: 4px 0 0 0;">
                            ${{ number_format($valorUF, 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                @if($suscripcion)
                {{-- Formularios ocultos para acciones de suscripción --}}
                <form id="bs-form-pausar"    action="{{ route('admin.billing.toggle-pausa', $suscripcion->id) }}" method="POST" style="display:none;">@csrf</form>
                <form id="bs-form-reanudar"  action="{{ route('admin.billing.toggle-pausa', $suscripcion->id) }}" method="POST" style="display:none;">@csrf</form>
                <form id="bs-form-reiniciar" action="{{ route('admin.billing.reiniciar-ciclo', $suscripcion->id) }}" method="POST" style="display:none;">@csrf</form>

                {{-- Modal de confirmación con palabra clave --}}
                <div id="bs-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(17,24,39,0.55); z-index:1000; align-items:center; justify-content:center; backdrop-filter: blur(2px);">
                    <div style="background:white; border-radius:16px; padding:28px; max-width:480px; width:90%; box-shadow:0 25px 60px rgba(0,0,0,0.3); animation: bsModalIn 0.18s ease-out;">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:6px;">
                            <span id="bs-modal-icon" style="font-size: 1.75rem;"></span>
                            <h3 id="bs-modal-title" class="bs-mostin" style="font-size:1.5rem; font-weight:900; color:#111827; margin:0;"></h3>
                        </div>
                        <p id="bs-modal-desc" style="color:#4B5563; font-size:0.875rem; margin:0 0 22px 0; line-height:1.55;"></p>

                        <label style="display:block; font-size:0.7rem; color:#374151; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">
                            Escribe <span id="bs-modal-keyword" style="color:#FF8100; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-weight:700;"></span> para confirmar
                        </label>
                        <input type="text" id="bs-modal-input" oninput="bsCheckInput()" autocomplete="off" spellcheck="false"
                               style="width:100%; padding:11px 14px; border:1.5px solid #E5E7EB; border-radius:8px; font-size:1rem; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; box-sizing:border-box; outline:none; transition:border-color 0.15s;"
                               onfocus="this.style.borderColor='#FF8100';" onblur="this.style.borderColor='#E5E7EB';">

                        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:22px;">
                            <button type="button" onclick="bsCloseModal()"
                                    style="background:#F3F4F6; color:#374151; border:none; padding:10px 20px; border-radius:8px; font-weight:600; font-size:0.85rem; cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="button" id="bs-modal-confirm" disabled onclick="bsSubmitAction()"
                                    style="background:#D1D5DB; color:white; border:none; padding:10px 22px; border-radius:8px; font-weight:700; font-size:0.85rem; cursor:not-allowed; transition:all 0.15s;">
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>

                <style>
                    @keyframes bsModalIn {
                        from { opacity: 0; transform: scale(0.96) translateY(8px); }
                        to   { opacity: 1; transform: scale(1) translateY(0); }
                    }
                </style>

                <script>
                (function() {
                    const ACTIONS = {
                        pausar:    { icon:'&#9208;',  title:'Pausar servicio',    desc:'El cliente no podra emitir documentos hasta que reanudes el servicio. Esta accion es reversible.', keyword:'PAUSAR',    formId:'bs-form-pausar',    color:'#F59E0B' },
                        reanudar:  { icon:'&#9654;',  title:'Reanudar servicio',  desc:'El cliente podra volver a emitir documentos inmediatamente.',                                          keyword:'REANUDAR',  formId:'bs-form-reanudar',  color:'#059669' },
                        reiniciar: { icon:'&#8635;',  title:'Reiniciar ciclo',    desc:'IRREVERSIBLE. Pondra fecha_inicio=hoy y fecha_fin=hoy+30 dias. Los documentos emitidos volveran a 0. La factura del ciclo anterior se conserva en el historial.', keyword:'REINICIAR', formId:'bs-form-reiniciar', color:'#DC2626' }
                    };
                    let currentAction = null;

                    window.bsToggleMenu = function(e) {
                        if (e) e.stopPropagation();
                        const m = document.getElementById('bs-menu-dropdown');
                        m.style.display = m.style.display === 'none' ? 'block' : 'none';
                    };

                    window.bsOpenConfirm = function(key) {
                        currentAction = ACTIONS[key];
                        if (!currentAction) return;
                        document.getElementById('bs-menu-dropdown').style.display = 'none';
                        document.getElementById('bs-modal-icon').innerHTML  = currentAction.icon;
                        document.getElementById('bs-modal-title').textContent = currentAction.title;
                        document.getElementById('bs-modal-desc').textContent  = currentAction.desc;
                        document.getElementById('bs-modal-keyword').textContent = currentAction.keyword;
                        const input = document.getElementById('bs-modal-input');
                        input.value = '';
                        input.placeholder = currentAction.keyword;
                        document.getElementById('bs-modal-backdrop').style.display = 'flex';
                        const btn = document.getElementById('bs-modal-confirm');
                        btn.disabled = true;
                        btn.style.background = '#D1D5DB';
                        btn.style.cursor = 'not-allowed';
                        setTimeout(function(){ input.focus(); }, 50);
                    };

                    window.bsCloseModal = function() {
                        document.getElementById('bs-modal-backdrop').style.display = 'none';
                        currentAction = null;
                    };

                    window.bsCheckInput = function() {
                        const input = document.getElementById('bs-modal-input');
                        const btn   = document.getElementById('bs-modal-confirm');
                        if (currentAction && input.value.trim().toUpperCase() === currentAction.keyword) {
                            btn.disabled = false;
                            btn.style.background = currentAction.color;
                            btn.style.cursor = 'pointer';
                        } else {
                            btn.disabled = true;
                            btn.style.background = '#D1D5DB';
                            btn.style.cursor = 'not-allowed';
                        }
                    };

                    window.bsSubmitAction = function() {
                        if (!currentAction) return;
                        const form = document.getElementById(currentAction.formId);
                        if (form) form.submit();
                    };

                    document.addEventListener('click', function(e) {
                        const wrapper = document.getElementById('bs-actions-wrapper');
                        if (wrapper && !wrapper.contains(e.target)) {
                            const m = document.getElementById('bs-menu-dropdown');
                            if (m) m.style.display = 'none';
                        }
                        if (e.target && e.target.id === 'bs-modal-backdrop') window.bsCloseModal();
                    });

                    document.addEventListener('keydown', function(e) {
                        const modal = document.getElementById('bs-modal-backdrop');
                        if (!modal) return;
                        if (e.key === 'Escape') window.bsCloseModal();
                        if (e.key === 'Enter' && modal.style.display === 'flex') {
                            const btn = document.getElementById('bs-modal-confirm');
                            if (btn && !btn.disabled) { e.preventDefault(); btn.click(); }
                        }
                    });
                })();
                </script>
                @endif
            </div>
            @endif

            {{-- Historial de facturas --}}
            <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #E5E7EB;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <h3 style="font-weight: 700; color: #374151; margin: 0;">Historial de Facturas de Servicio</h3>
                        <div style="font-size: 0.8rem; color: #6B7280;">
                            @if($hayFiltros)
                                Mostrando <strong style="color: #FF8100;">{{ $facturas->count() }}</strong> de {{ $totalSinFiltros }} facturas
                                <a href="{{ route('admin.billing.show', $cliente->id) }}" style="margin-left: 12px; color: #6B7280; text-decoration: underline; font-size: 0.75rem;">Limpiar filtros</a>
                            @else
                                {{ $totalSinFiltros }} {{ $totalSinFiltros === 1 ? 'factura' : 'facturas' }}
                            @endif
                        </div>
                    </div>

                    {{-- Filtros --}}
                    <form method="GET" action="{{ route('admin.billing.show', $cliente->id) }}"
                          style="display: grid; grid-template-columns: 1fr 1fr 1fr 2fr auto auto; gap: 10px; margin-top: 16px; align-items: end;">
                        <div>
                            <label style="display: block; font-size: 0.65rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Estado</label>
                            <select name="estado" style="width: 100%; padding: 8px 10px; border: 1px solid #E5E7EB; border-radius: 7px; font-size: 0.85rem; background: white; cursor: pointer;">
                                <option value="">Todas</option>
                                <option value="pagada"    @if($filtros['estado']==='pagada') selected @endif>Pagada</option>
                                <option value="pendiente" @if($filtros['estado']==='pendiente') selected @endif>Pendiente</option>
                                <option value="anulada"   @if($filtros['estado']==='anulada') selected @endif>Anulada</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.65rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Desde</label>
                            <input type="date" name="desde" value="{{ $filtros['desde'] }}" style="width: 100%; padding: 7px 10px; border: 1px solid #E5E7EB; border-radius: 7px; font-size: 0.85rem; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.65rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Hasta</label>
                            <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" style="width: 100%; padding: 7px 10px; border: 1px solid #E5E7EB; border-radius: 7px; font-size: 0.85rem; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.65rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Buscar (N&deg; o folio)</label>
                            <input type="text" name="q" value="{{ $filtros['q'] }}" placeholder="FS-000056 o 56" style="width: 100%; padding: 8px 10px; border: 1px solid #E5E7EB; border-radius: 7px; font-size: 0.85rem; box-sizing: border-box;">
                        </div>
                        <button type="submit" style="background: #FF8100; color: white; border: none; padding: 9px 18px; border-radius: 7px; font-weight: 700; font-size: 0.8rem; cursor: pointer; height: 36px; white-space: nowrap;"
                                onmouseover="this.style.background='#E67400';" onmouseout="this.style.background='#FF8100';">
                            Filtrar
                        </button>
                        @if($hayFiltros)
                            <a href="{{ route('admin.billing.show', $cliente->id) }}"
                               style="background: #F3F4F6; color: #374151; border: none; padding: 9px 14px; border-radius: 7px; font-weight: 600; font-size: 0.8rem; text-decoration: none; height: 36px; box-sizing: border-box; display: inline-flex; align-items: center; white-space: nowrap;"
                               onmouseover="this.style.background='#E5E7EB';" onmouseout="this.style.background='#F3F4F6';">
                                Limpiar
                            </a>
                        @else
                            <span></span>
                        @endif
                    </form>
                </div>

                @if($facturas->count() > 0)
                @php
                    // Colapsar columnas que en TODAS las facturas mostradas estén en 0 o vacías.
                    $hasDocsExtra = $facturas->contains(function ($f) { return (int) ($f->documentos_extra ?? 0) > 0; });
                    $hasExtraCLP  = $facturas->contains(function ($f) { return (int) ($f->monto_extra_clp ?? 0)  > 0; });
                    $colsOcultas = [];
                    if (!$hasDocsExtra) $colsOcultas[] = 'Docs Extra';
                    if (!$hasExtraCLP)  $colsOcultas[] = 'Extra CLP';
                @endphp
                @if(count($colsOcultas) > 0)
                <div style="padding: 8px 24px 0; font-size: 0.7rem; color: #9CA3AF;">
                    <span style="background: #F9FAFB; border: 1px dashed #E5E7EB; padding: 3px 8px; border-radius: 999px;">
                        Columnas ocultas (todas en 0): <strong style="color: #6B7280;">{{ implode(', ', $colsOcultas) }}</strong>
                    </span>
                </div>
                @endif
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #F9FAFB;">
                                <th style="padding: 10px 14px; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">N&deg; Factura</th>
                                <th style="padding: 10px 14px; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Concepto</th>
                                <th style="padding: 10px 14px; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Per&iacute;odo</th>
                                <th style="padding: 10px 14px; text-align: center; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Docs Inc.</th>
                                <th style="padding: 10px 14px; text-align: center; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Docs Emit.</th>
                                @if($hasDocsExtra)
                                <th style="padding: 10px 14px; text-align: center; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Docs Extra</th>
                                @endif
                                <th style="padding: 10px 14px; text-align: right; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Plan CLP</th>
                                @if($hasExtraCLP)
                                <th style="padding: 10px 14px; text-align: right; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Extra CLP</th>
                                @endif
                                <th style="padding: 10px 14px; text-align: right; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Total</th>
                                <th style="padding: 10px 14px; text-align: center; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">Estado</th>
                                <th style="padding: 10px 14px; text-align: center; font-size: 0.7rem; font-weight: 600; color: #6B7280; text-transform: uppercase;">PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facturas as $factura)
                            <tr style="border-bottom: 1px solid #F3F4F6;">
                                <td style="padding: 10px 14px; font-weight: 600; color: #1F2937; font-size: 0.875rem;">
                                    @if($factura->numero_factura)
                                        {{ $factura->numero_factura }}
                                        @if($factura->folio)
                                            <div style="font-size: 0.65rem; color: #9CA3AF; font-weight: 400; margin-top: 2px;">Folio SII: {{ $factura->folio }}</div>
                                        @endif
                                    @elseif($factura->folio)
                                        <span style="color: #059669;">FS-{{ str_pad($factura->folio, 6, '0', STR_PAD_LEFT) }}</span>
                                        <div style="font-size: 0.65rem; color: #9CA3AF; font-weight: 400; margin-top: 2px;">Folio SII: {{ $factura->folio }}</div>
                                    @else
                                        <span style="color: #9CA3AF;">&mdash;</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 14px; color: #4B5563; font-size: 0.8rem;">{{ $factura->concepto }}</td>
                                <td style="padding: 10px 14px; color: #6B7280; font-size: 0.8rem;">
                                    {{ $factura->periodo_inicio ? $factura->periodo_inicio->format('d/m/Y') : '-' }} -
                                    {{ $factura->periodo_fin ? $factura->periodo_fin->format('d/m/Y') : '-' }}
                                </td>
                                <td style="padding: 10px 14px; text-align: center; font-size: 0.875rem;">{{ $factura->documentos_incluidos ?? '-' }}</td>
                                <td style="padding: 10px 14px; text-align: center; font-size: 0.875rem;">{{ $factura->documentos_emitidos ?? '-' }}</td>
                                @if($hasDocsExtra)
                                <td style="padding: 10px 14px; text-align: center; font-weight: 700; color: {{ ($factura->documentos_extra ?? 0) > 0 ? '#FF8100' : '#6B7280' }}; font-size: 0.875rem;">
                                    {{ ($factura->documentos_extra ?? 0) > 0 ? $factura->documentos_extra : '-' }}
                                </td>
                                @endif
                                <td style="padding: 10px 14px; text-align: right; font-size: 0.875rem;">${{ number_format($factura->monto_plan_clp ?? 0, 0, ',', '.') }}</td>
                                @if($hasExtraCLP)
                                <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: {{ ($factura->monto_extra_clp ?? 0) > 0 ? '#FF8100' : '#6B7280' }}; font-size: 0.875rem;">
                                    ${{ number_format($factura->monto_extra_clp ?? 0, 0, ',', '.') }}
                                </td>
                                @endif
                                <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: #1F2937; font-size: 0.875rem;">${{ number_format($factura->monto, 0, ',', '.') }}</td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    @if($factura->estado === 'pagada')
                                        <span style="background: #D1FAE5; color: #065F46; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">Pagada</span>
                                    @elseif($factura->estado === 'pendiente')
                                        <span style="background: #FEF3C7; color: #92400E; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">Pendiente</span>
                                        @if($factura->monto > 0)
                                            <form action="{{ route('admin.billing.marcar-pagada', $factura->id) }}" method="POST" style="display:block; margin-top:6px;">
                                                @csrf
                                                <button type="submit"
                                                        onclick="return confirm('¿Marcar esta factura como PAGADA manualmente?');"
                                                        style="background:#059669; color:white; padding:4px 10px; border:none; border-radius:6px; font-size:.7rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                                                    ✅ Marcar Pagada
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span style="background: #F3F4F6; color: #6B7280; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">{{ ucfirst($factura->estado) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    @if($factura->pdf_base64 || $factura->estado === 'pagada')
                                        <a href="{{ route('admin.billing.factura-pdf', $factura->id) }}" target="_blank"
                                           style="display: inline-flex; align-items: center; gap: 5px; background: #F8FAFC; color: #475569; border: 1px solid #E2E8F0; padding: 5px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-decoration: none; transition: all 0.15s;"
                                           onmouseover="this.style.background='#475569'; this.style.color='white'; this.style.borderColor='#475569';"
                                           onmouseout="this.style.background='#F8FAFC'; this.style.color='#475569'; this.style.borderColor='#E2E8F0';">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                            Ver PDF
                                        </a>
                                    @elseif($factura->monto == 0)
                                        <span style="color: #9CA3AF; font-size: 0.75rem;">Gratis</span>
                                    @else
                                        <span style="color: #D97706; font-size: 0.75rem;">Sin documento</span>
                                    @endif

                                    @if(!$factura->folio && in_array($factura->estado, ['pagada','pendiente']) && $factura->monto > 0)
                                        <form action="{{ route('admin.billing.reemitir-dte', $factura->id) }}" method="POST" style="display:block; margin-top:6px;">
                                            @csrf
                                            <button type="submit"
                                                    onclick="return confirm('¿Emitir DTE en Lioren para esta factura?\n\nEsto generará una factura electrónica real ante el SII.');"
                                                    style="background:#10B981; color:white; padding:4px 10px; border:none; border-radius:6px; font-size:.7rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                                                ⚡ Emitir DTE
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div style="padding: 48px 24px; text-align: center;">
                    @if($hayFiltros)
                        <p style="color: #6B7280; margin: 0 0 12px 0;">No hay facturas que coincidan con los filtros aplicados.</p>
                        <a href="{{ route('admin.billing.show', $cliente->id) }}" style="color: #FF8100; font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Limpiar filtros</a>
                    @else
                        <p style="color: #6B7280; margin: 0;">No hay facturas de servicio para este cliente.</p>
                    @endif
                </div>
                @endif
            </div>

            {{-- Auditoría: últimas acciones admin sobre este cliente --}}
            <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 24px; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-weight: 700; color: #374151; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 1rem;">&#128274;</span> Historial de acciones admin
                    </h3>
                    <span style="font-size: 0.75rem; color: #6B7280;">
                        @if($adminLogs->count() > 0)
                            Mostrando {{ $adminLogs->count() }} m&aacute;s reciente{{ $adminLogs->count() === 1 ? '' : 's' }}
                        @else
                            Sin actividad registrada
                        @endif
                    </span>
                </div>

                @if($adminLogs->count() > 0)
                <div style="padding: 8px 0;">
                    @foreach($adminLogs as $log)
                    <div style="display: flex; align-items: flex-start; gap: 14px; padding: 12px 24px; border-bottom: 1px solid #F9FAFB;">
                        <div style="flex-shrink: 0; width: 32px; height: 32px; border-radius: 8px; background: {{ $log->action_color }}1A; color: {{ $log->action_color }}; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                            {{ $log->action_icon }}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 12px;">
                                <p style="margin: 0; font-size: 0.875rem; color: #1F2937;">
                                    <strong style="color: {{ $log->action_color }};">{{ $log->action_label }}</strong>
                                    @if($log->target_type === 'factura_servicio' && $log->target_id)
                                        <span style="color: #6B7280;">&middot; Factura #{{ $log->target_id }}</span>
                                        @if($log->metadata['folio'] ?? null)
                                            <span style="color: #9CA3AF; font-size: 0.75rem;">(folio {{ $log->metadata['folio'] }})</span>
                                        @endif
                                    @elseif($log->target_type === 'suscripcion' && $log->target_id)
                                        <span style="color: #6B7280;">&middot; Susc. #{{ $log->target_id }}</span>
                                    @endif
                                </p>
                                <span style="font-size: 0.7rem; color: #9CA3AF; white-space: nowrap;" title="{{ $log->created_at->format('d/m/Y H:i:s') }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p style="margin: 4px 0 0 0; font-size: 0.75rem; color: #6B7280;">
                                Por <strong style="color: #374151;">{{ $log->admin->name ?? $log->admin_email ?? 'Sistema' }}</strong>
                                @if($log->admin_email && ($log->admin->name ?? null))
                                    <span style="color: #9CA3AF;">&lt;{{ $log->admin_email }}&gt;</span>
                                @endif
                                @if($log->ip_address)
                                    <span style="color: #9CA3AF;">&middot; {{ $log->ip_address }}</span>
                                @endif
                            </p>
                            @php
                                $meta = $log->metadata ?? [];
                                $metaItems = [];
                                if ($log->action === 'marcar_pagada') {
                                    if (isset($meta['monto_clp'])) $metaItems[] = 'Monto: $' . number_format($meta['monto_clp'], 0, ',', '.');
                                    if (isset($meta['numero_factura'])) $metaItems[] = $meta['numero_factura'];
                                } elseif ($log->action === 'emitir_dte') {
                                    if (!empty($meta['folio'])) $metaItems[] = 'Folio: ' . $meta['folio'];
                                    if (!empty($meta['error'])) $metaItems[] = 'Error: ' . $meta['error'];
                                } elseif ($log->action === 'reiniciar_ciclo') {
                                    if (!empty($meta['fecha_inicio_anterior']) && !empty($meta['fecha_inicio_nueva'])) {
                                        $metaItems[] = $meta['fecha_inicio_anterior'] . ' &rarr; ' . $meta['fecha_inicio_nueva'];
                                    }
                                }
                            @endphp
                            @if(!empty($metaItems))
                                <p style="margin: 4px 0 0 0; font-size: 0.7rem; color: #9CA3AF; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                                    {!! implode(' &middot; ', $metaItems) !!}
                                </p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="padding: 32px 24px; text-align: center;">
                    <p style="color: #9CA3AF; margin: 0; font-size: 0.85rem;">
                        A&uacute;n no se han registrado acciones admin sobre este cliente.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
