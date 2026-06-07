@extends('demo.layout')
@section('page-title', 'Mis Facturas')
@section('content')
<div style="margin-bottom: 16px;">
    <h2 style="font-size: 20px; font-weight: 700; color: #1e293b;">Mis Facturas</h2>
    <p style="font-size: 13px; color: #64748b;">Facturas emitidas por el servicio de integración</p>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table>
        <thead>
            <tr><th>Fecha</th><th>Folio</th><th>Concepto</th><th>Período</th><th>Monto UF</th><th>Total CLP</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            @foreach($facturas as $factura)
            <tr>
                <td>{{ $factura->fecha->format('d/m/Y') }}</td>
                <td style="font-weight: 600;">{{ $factura->folio }}</td>
                <td>{{ $factura->concepto }}</td>
                <td style="font-size: 12px;">
                    {{ \Carbon\Carbon::parse($factura->periodo_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($factura->periodo_fin)->format('d/m/Y') }}
                </td>
                <td>{{ $factura->monto }} UF</td>
                <td style="font-weight: 600;">${{ number_format($factura->total_clp, 0, ',', '.') }}</td>
                <td><span class="badge badge-green">Pagada</span></td>
                <td>
                    <a href="#" onclick="showDemoToast(); return false;" style="color: #dc2626; font-size: 12px; text-decoration: none;"><i class="fas fa-file-pdf"></i> PDF</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
