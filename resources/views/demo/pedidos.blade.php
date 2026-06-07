@extends('demo.layout')
@section('page-title', 'Pedidos')
@section('content')
<div style="margin-bottom: 16px;">
    <h2 style="font-size: 20px; font-weight: 700; color: #1e293b;">Pedidos Shopify</h2>
    <p style="font-size: 13px; color: #64748b;">Últimos pedidos procesados desde tu tienda Shopify</p>
</div>

<div class="grid grid-cols-4" style="margin-bottom: 16px;">
    <div class="card stat-card" style="border-color: #3b82f6; padding: 14px;">
        <div class="label">Total Pedidos</div>
        <div class="value" style="color: #2563eb;">187</div>
    </div>
    <div class="card stat-card" style="border-color: #22c55e; padding: 14px;">
        <div class="label">Completados</div>
        <div class="value" style="color: #16a34a;">180</div>
    </div>
    <div class="card stat-card" style="border-color: #f59e0b; padding: 14px;">
        <div class="label">Pendientes</div>
        <div class="value" style="color: #d97706;">5</div>
    </div>
    <div class="card stat-card" style="border-color: #ef4444; padding: 14px;">
        <div class="label">Reembolsados</div>
        <div class="value" style="color: #dc2626;">2</div>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table>
        <thead>
            <tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Productos</th><th>Total</th><th>Documento</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @php
            $pedidos = [
                ['#2670', now()->subHours(2), 'María González', 'Camiseta Premium x2', 45990, 'Boleta #6480', 'Completado'],
                ['#2669', now()->subHours(5), 'Pedro Soto', 'Zapatillas Running Pro', 128500, 'Boleta #6479', 'Completado'],
                ['#2668', now()->subHours(8), 'Comercial Andina SpA', 'Jeans Slim Fit x10, Polera Oversize x15', 892000, 'Factura #245', 'Completado'],
                ['#2667', now()->subDay(), 'Carlos Fuentes', 'Chaqueta Invierno', 67890, 'Boleta #6478', 'Completado'],
                ['#2666', now()->subDays(2), 'Laura Díaz', 'Mochila Urbana 25L x3, Gorra Snapback x5', 234500, 'Boleta #6477', 'Completado'],
                ['#2665', now()->subDays(2), 'Ana Muñoz', 'Polera Oversize x2', 35990, 'NC #128', 'Reembolsado'],
                ['#2664', now()->subDays(3), 'Importadora del Sur Ltda', 'Lote mayorista variado', 1450000, 'Factura #244', 'Completado'],
                ['#2663', now()->subDays(3), 'Roberto Araya', 'Zapatillas Running Pro, Cinturón Cuero', 89990, 'Boleta #6476', 'Completado'],
            ];
            @endphp
            @foreach($pedidos as $p)
            <tr>
                <td style="font-weight: 600;">{{ $p[0] }}</td>
                <td>{{ $p[1]->format('d/m/Y H:i') }}</td>
                <td>{{ $p[2] }}</td>
                <td style="font-size: 12px; max-width: 200px;">{{ $p[3] }}</td>
                <td style="font-weight: 600;">${{ number_format($p[4], 0, ',', '.') }}</td>
                <td>
                    @if(str_contains($p[5], 'NC'))
                        <span class="badge badge-red">{{ $p[5] }}</span>
                    @elseif(str_contains($p[5], 'Factura'))
                        <span class="badge badge-blue">{{ $p[5] }}</span>
                    @else
                        <span class="badge badge-green">{{ $p[5] }}</span>
                    @endif
                </td>
                <td>
                    @if($p[6] === 'Reembolsado')
                        <span class="badge badge-red">{{ $p[6] }}</span>
                    @else
                        <span class="badge badge-green">{{ $p[6] }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
