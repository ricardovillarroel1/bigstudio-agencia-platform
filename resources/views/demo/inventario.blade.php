@extends('demo.layout')
@section('page-title', 'Inventario')
@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: #1e293b;">Inventario Sincronizado</h2>
        <p style="font-size: 13px; color: #64748b;">Productos de Shopify vinculados con Lioren</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <button class="btn btn-outline" onclick="showDemoToast()"><i class="fas fa-sync"></i> Sincronizar</button>
    </div>
</div>

<div class="grid grid-cols-4" style="margin-bottom: 16px;">
    <div class="card stat-card" style="border-color: #3b82f6; padding: 14px;">
        <div class="label">Productos Shopify</div>
        <div class="value" style="color: #2563eb;">47</div>
    </div>
    <div class="card stat-card" style="border-color: #22c55e; padding: 14px;">
        <div class="label">Vinculados</div>
        <div class="value" style="color: #16a34a;">42</div>
    </div>
    <div class="card stat-card" style="border-color: #f59e0b; padding: 14px;">
        <div class="label">Sin Vincular</div>
        <div class="value" style="color: #d97706;">5</div>
    </div>
    <div class="card stat-card" style="border-color: #6366f1; padding: 14px;">
        <div class="label">Productos Lioren</div>
        <div class="value" style="color: #4f46e5;">42</div>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table>
        <thead>
            <tr><th>SKU</th><th>Producto Shopify</th><th>Producto Lioren</th><th>Stock</th><th>Precio</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @php
            $productos = [
                ['SKU-001', 'Camiseta Premium Algodón', 'Camiseta Premium Algodón', 156, 29990, true],
                ['SKU-002', 'Jeans Slim Fit', 'Jeans Slim Fit', 89, 45990, true],
                ['SKU-003', 'Zapatillas Running Pro', 'Zapatillas Running Pro', 34, 79990, true],
                ['SKU-004', 'Chaqueta Invierno', 'Chaqueta Invierno', 22, 129990, true],
                ['SKU-005', 'Polera Oversize', 'Polera Oversize', 201, 19990, true],
                ['SKU-006', 'Gorra Snapback', 'Gorra Snapback', 67, 14990, true],
                ['SKU-007', 'Mochila Urbana 25L', 'Mochila Urbana 25L', 15, 49990, true],
                ['SKU-008', 'Cinturón Cuero', null, 45, 24990, false],
                ['SKU-009', 'Bufanda Lana Merino', null, 30, 34990, false],
            ];
            @endphp
            @foreach($productos as $p)
            <tr>
                <td style="font-family: monospace; font-size: 12px;">{{ $p[0] }}</td>
                <td>{{ $p[1] }}</td>
                <td>{{ $p[2] ?? '-' }}</td>
                <td><span class="badge {{ $p[3] < 20 ? 'badge-yellow' : 'badge-green' }}">{{ $p[3] }}</span></td>
                <td style="font-weight: 600;">${{ number_format($p[4], 0, ',', '.') }}</td>
                <td>
                    @if($p[5])
                        <span class="badge badge-green">Vinculado</span>
                    @else
                        <span class="badge badge-yellow">Sin vincular</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
