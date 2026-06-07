@extends('demo.layout')
@section('page-title', 'Facturas de Servicio')
@section('content')
<div style="margin-bottom: 16px;">
    <h2 style="font-size: 20px; font-weight: 700; color: #1e293b;">Facturas de Servicio</h2>
    <p style="font-size: 13px; color: #64748b;">Facturas emitidas por Big Studio por el servicio de integración</p>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table>
        <thead>
            <tr><th>Fecha</th><th>Folio</th><th>Concepto</th><th>Neto</th><th>IVA</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ now()->subDays(15)->format('d/m/Y') }}</td>
                <td style="font-weight: 600;">FS-0045</td>
                <td>Suscripción PLAN PRO 1.7 UF +IVA</td>
                <td>${{ number_format(55000, 0, ',', '.') }}</td>
                <td>${{ number_format(10450, 0, ',', '.') }}</td>
                <td style="font-weight: 700;">${{ number_format(65450, 0, ',', '.') }}</td>
                <td><span class="badge badge-green">Pagada</span></td>
                <td><a href="#" onclick="showDemoToast(); return false;" style="color: #dc2626; font-size: 12px; text-decoration: none;"><i class="fas fa-file-pdf"></i> PDF</a></td>
            </tr>
            <tr>
                <td>{{ now()->subDays(45)->format('d/m/Y') }}</td>
                <td style="font-weight: 600;">FS-0038</td>
                <td>Suscripción PLAN PRO 1.7 UF +IVA</td>
                <td>${{ number_format(54454, 0, ',', '.') }}</td>
                <td>${{ number_format(10346, 0, ',', '.') }}</td>
                <td style="font-weight: 700;">${{ number_format(64800, 0, ',', '.') }}</td>
                <td><span class="badge badge-green">Pagada</span></td>
                <td><a href="#" onclick="showDemoToast(); return false;" style="color: #dc2626; font-size: 12px; text-decoration: none;"><i class="fas fa-file-pdf"></i> PDF</a></td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
