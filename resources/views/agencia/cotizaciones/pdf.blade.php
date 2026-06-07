@php
    // Helpers para colores (no usamos Tailwind aqui porque es PDF via wkhtmltopdf)
    $brand600  = '#FF8100';
    $brand500  = '#FF9C00';
    $brand50   = '#FFF7EC';
    $brand100  = '#FFEDD0';
    $accent500 = '#FFC800';
    $dark      = '#111827';
    $gray      = '#6B7280';
    $light     = '#E5E7EB';

    $logoBase64 = base64_encode(file_get_contents(public_path('images/bigstudio-logo-gradient.png')));

    // Totales: la cotizacion ya tiene total_neto (con descuento aplicado) y total (con IVA).
    // Subtotal sin descuento = total_neto + descuento_monto.
    $descuento     = (int) ($cotizacion->descuento_monto ?? 0);
    $netoFinal     = (int) ($cotizacion->total_neto ?? 0);
    $subtotalNeto  = $netoFinal + $descuento;
    $total         = (int) ($cotizacion->total ?? round($netoFinal * 1.19));
    $iva           = $total - $netoFinal;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización #{{ $cotizacion->numero }}</title>
    <style>
        @page { size: Letter; margin: 0; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: {{ $dark }};
            font-size: 12px;
            background: #ffffff;
        }
        .page { padding: 32px 36px 24px; }

        /* HEADER */
        .header {
            display: table;
            width: 100%;
            border-bottom: 4px solid {{ $brand600 }};
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header .logo-cell {
            display: table-cell;
            vertical-align: middle;
            width: 110px;
        }
        .header .logo-cell img { width: 90px; height: auto; }
        .header .brand-cell {
            display: table-cell;
            vertical-align: middle;
            padding-left: 16px;
        }
        .header .brand-cell h1 {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 900;
            color: {{ $dark }};
            letter-spacing: -0.5px;
        }
        .header .brand-cell .tagline {
            margin: 0;
            font-size: 10px;
            color: {{ $gray }};
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .header .meta-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .header .meta-cell .doc-type {
            background: linear-gradient(135deg, {{ $accent500 }} 0%, {{ $brand500 }} 50%, {{ $brand600 }} 100%);
            color: #ffffff;
            display: inline-block;
            padding: 6px 16px;
            border-radius: 6px;
            font-weight: 900;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .header .meta-cell .number {
            font-size: 24px;
            font-weight: 900;
            color: {{ $brand600 }};
            margin: 0;
            letter-spacing: -0.5px;
        }

        /* INFO BLOCKS */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 24px;
        }
        .info-block {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding: 14px 18px;
            background: {{ $brand50 }};
            border-radius: 8px;
        }
        .info-block.right { margin-left: 12px; }
        .info-block .label {
            font-size: 9px;
            font-weight: 700;
            color: {{ $brand600 }};
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 8px;
        }
        .info-block .name {
            font-size: 14px;
            font-weight: 700;
            color: {{ $dark }};
            margin: 0 0 4px;
        }
        .info-block p {
            margin: 2px 0;
            font-size: 11px;
            color: #374151;
        }
        .info-block p strong { color: {{ $dark }}; font-weight: 600; }
        .spacer { display: table-cell; width: 12px; }

        /* ITEMS TABLE */
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .items thead th {
            background: {{ $dark }};
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 12px;
            text-align: left;
        }
        .items thead th.center { text-align: center; }
        .items thead th.right { text-align: right; }
        .items tbody td {
            padding: 12px;
            border-bottom: 1px solid {{ $light }};
            font-size: 12px;
            color: {{ $dark }};
        }
        .items tbody td.center { text-align: center; }
        .items tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
        .items tbody tr:nth-child(even) td { background: #FAFAFA; }
        .items tbody .code {
            font-family: 'Courier New', monospace;
            color: {{ $gray }};
            font-size: 10px;
        }

        /* TOTALES */
        .totals-wrap {
            width: 320px;
            margin-left: auto;
            margin-bottom: 24px;
        }
        .totals { width: 100%; border-collapse: collapse; }
        .totals td {
            padding: 7px 14px;
            font-size: 12px;
            border-bottom: 1px solid {{ $light }};
        }
        .totals td.label {
            color: {{ $gray }};
            text-align: right;
        }
        .totals td.value {
            text-align: right;
            font-weight: 600;
            color: {{ $dark }};
            font-variant-numeric: tabular-nums;
        }
        .totals .descuento .label { color: #DC2626; }
        .totals .descuento .value { color: #DC2626; }
        .totals .total-row td {
            background: linear-gradient(135deg, {{ $accent500 }} 0%, {{ $brand500 }} 50%, {{ $brand600 }} 100%);
            color: #ffffff;
            font-weight: 900;
            font-size: 14px;
            border: none;
            padding: 12px 14px;
        }
        .totals .total-row td.label { color: rgba(255,255,255,0.9); letter-spacing: 0.5px; }

        /* NOTAS */
        .notas {
            padding: 14px 18px;
            background: {{ $brand50 }};
            border-left: 4px solid {{ $brand600 }};
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .notas .label {
            font-size: 9px;
            font-weight: 700;
            color: {{ $brand600 }};
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 6px;
        }
        .notas p { margin: 0; font-size: 11px; color: #374151; line-height: 1.5; }

        /* FOOTER */
        .footer {
            border-top: 1px solid {{ $light }};
            padding-top: 16px;
            margin-top: 32px;
            display: table;
            width: 100%;
        }
        .footer .left, .footer .right {
            display: table-cell;
            vertical-align: middle;
            font-size: 10px;
            color: {{ $gray }};
        }
        .footer .right { text-align: right; }
        .footer .left strong { color: {{ $brand600 }}; font-weight: 700; }

        /* Stripe decorativa al pie */
        .bottom-stripe {
            height: 6px;
            background: linear-gradient(90deg, {{ $accent500 }} 0%, {{ $brand500 }} 50%, {{ $brand600 }} 100%);
            margin-top: 20px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <div class="header">
        <div class="logo-cell">
            <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Big Studio">
        </div>
        <div class="brand-cell">
            <h1>Big Studio</h1>
            <p class="tagline">Agencia de Marketing Digital</p>
        </div>
        <div class="meta-cell">
            <div class="doc-type">Cotizaci&oacute;n</div>
            <p class="number">N&deg; {{ $cotizacion->numero }}</p>
        </div>
    </div>

    {{-- INFO CLIENTE + COTIZACION --}}
    <div class="info-grid">
        <div class="info-block">
            <p class="label">Cliente</p>
            <p class="name">{{ $cotizacion->cliente_nombre }}</p>
            @if($cotizacion->cliente_rut)
                <p><strong>RUT:</strong> {{ $cotizacion->cliente_rut }}</p>
            @endif
            <p><strong>Email:</strong> {{ $cotizacion->cliente_email }}</p>
            @if($cotizacion->cliente_telefono ?? false)
                <p><strong>Tel:</strong> {{ $cotizacion->cliente_telefono }}</p>
            @endif
        </div>
        <div class="spacer"></div>
        <div class="info-block right">
            <p class="label">Detalles</p>
            <p><strong>Fecha emisi&oacute;n:</strong> {{ $cotizacion->created_at->format('d/m/Y') }}</p>
            <p><strong>V&aacute;lida hasta:</strong> {{ $cotizacion->valida_hasta ? $cotizacion->valida_hasta->format('d/m/Y') : '—' }}</p>
            <p><strong>Estado:</strong> {{ ucfirst($cotizacion->estado) }}</p>
            @if($cotizacion->moneda ?? false)
                <p><strong>Moneda:</strong> {{ strtoupper($cotizacion->moneda) }}</p>
            @endif
        </div>
    </div>

    {{-- ITEMS --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 90px;">C&oacute;digo</th>
                <th>Descripci&oacute;n</th>
                <th class="center" style="width: 60px;">Cant.</th>
                <th class="right" style="width: 110px;">P. unitario</th>
                <th class="right" style="width: 110px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cotizacion->items as $item)
                @php
                    $cantItem   = (float) ($item->cantidad ?? 0);
                    $precioItem = (float) ($item->precio_unitario_neto ?? 0);
                    $subt       = (float) ($item->total_neto ?? ($cantItem * $precioItem));
                @endphp
                <tr>
                    <td class="code">{{ $item->codigo ?: 'SVC' }}</td>
                    <td>{{ $item->descripcion }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($cantItem, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="right">${{ number_format($precioItem, 0, ',', '.') }}</td>
                    <td class="right">${{ number_format($subt, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALES --}}
    <div class="totals-wrap">
        <table class="totals">
            <tr>
                <td class="label">Subtotal neto</td>
                <td class="value">${{ number_format($subtotalNeto, 0, ',', '.') }}</td>
            </tr>
            @if($descuento > 0)
            <tr class="descuento">
                <td class="label">Descuento ({{ $cotizacion->descuento_porcentaje }}%)</td>
                <td class="value">-${{ number_format($descuento, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Neto con descuento</td>
                <td class="value">${{ number_format($netoFinal, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">IVA (19%)</td>
                <td class="value">${{ number_format($iva, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td class="label">TOTAL</td>
                <td class="value">${{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- NOTAS / OBSERVACIONES --}}
    @if($cotizacion->observaciones ?? false)
    <div class="notas">
        <p class="label">Observaciones</p>
        <p>{!! nl2br(e($cotizacion->observaciones)) !!}</p>
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        <div class="left">
            <strong>Big Studio</strong> &middot; Agencia de Marketing Digital<br>
            hola@bigstudio.cl &middot; www.bigstudio.cl
        </div>
        <div class="right">
            Documento generado el {{ now()->format('d/m/Y H:i') }}<br>
            Cotizaci&oacute;n no constituye documento tributario.
        </div>
    </div>

    <div class="bottom-stripe"></div>

</div>
</body>
</html>
