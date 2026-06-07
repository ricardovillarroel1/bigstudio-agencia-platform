@extends('demo.layout')
@section('page-title', 'Estados de Solicitud')
@section('content')
<div style="margin-bottom: 16px;">
    <h2 style="font-size: 20px; font-weight: 700; color: #1e293b;">Estados de Solicitud</h2>
    <p style="font-size: 13px; color: #64748b;">Seguimiento de tus solicitudes de integración</p>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table>
        <thead>
            <tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Estado</th><th>Última Actualización</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ now()->subDays(15)->format('d/m/Y') }}</td>
                <td>Integración Shopify</td>
                <td>Configuración inicial de la tienda tiendademo.myshopify.com</td>
                <td><span class="badge badge-green">Completada</span></td>
                <td>{{ now()->subDays(14)->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>{{ now()->subDays(15)->format('d/m/Y') }}</td>
                <td>Credenciales SII</td>
                <td>Configuración de certificado digital y credenciales del SII</td>
                <td><span class="badge badge-green">Completada</span></td>
                <td>{{ now()->subDays(14)->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>{{ now()->subDays(3)->format('d/m/Y') }}</td>
                <td>Soporte</td>
                <td>Consulta sobre configuración de descuentos automáticos</td>
                <td><span class="badge badge-blue">En Proceso</span></td>
                <td>{{ now()->subDays(1)->format('d/m/Y H:i') }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
