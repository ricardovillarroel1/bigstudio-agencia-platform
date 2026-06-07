@extends('demo.layout')
@section('page-title', 'Planes Activos')
@section('content')
<div class="card" style="margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e293b;">{{ $suscripcion->plan->nombre }}</h3>
            <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Suscripción activa</p>
        </div>
        <span class="badge badge-green" style="font-size: 13px; padding: 4px 14px;">Activa</span>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4" style="margin-bottom: 20px;">
        <div class="card stat-card" style="border-color: #22c55e; padding: 14px;">
            <div class="label">Boletas</div>
            <div class="value" style="color: #16a34a; font-size: 28px;">{{ $documentosEmitidos['boletas'] }}</div>
        </div>
        <div class="card stat-card" style="border-color: #3b82f6; padding: 14px;">
            <div class="label">Facturas</div>
            <div class="value" style="color: #2563eb; font-size: 28px;">{{ $documentosEmitidos['facturas'] }}</div>
        </div>
        <div class="card stat-card" style="border-color: #ef4444; padding: 14px;">
            <div class="label">Notas de Crédito</div>
            <div class="value" style="color: #dc2626; font-size: 28px;">{{ $documentosEmitidos['notas_credito'] }}</div>
        </div>
        <div class="card stat-card" style="border-color: #6366f1; padding: 14px;">
            <div class="label">Total Ciclo</div>
            <div class="value" style="color: #4f46e5; font-size: 28px;">{{ $documentosEmitidos['total'] }}</div>
        </div>
    </div>

    <!-- Usage bar -->
    @php
        $limite = $suscripcion->plan->monthly_order_limit ?? 0;
        $total = $documentosEmitidos['total'];
        $porcentaje = $limite > 0 ? min(100, round(($total / $limite) * 100)) : 0;
        $restantes = $limite > 0 ? max(0, $limite - $total) : null;
    @endphp
    @if($limite > 0)
    <div style="background: #f8fafc; border-radius: 8px; padding: 16px; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="font-size: 13px; color: #475569;">Documentos utilizados: <strong>{{ $total }}</strong> de <strong>{{ number_format($limite, 0, ',', '.') }}</strong></span>
            <span style="font-size: 13px; font-weight: 600; color: #16a34a;">{{ $porcentaje }}%</span>
        </div>
        <div class="progress-bar" style="height: 14px;">
            <div class="progress-fill" style="width: {{ $porcentaje }}%; background: #22c55e;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 8px;">
            <span style="font-size: 12px; color: #64748b;">Te quedan <strong style="color: #16a34a;">{{ number_format($restantes, 0, ',', '.') }}</strong> documentos por emitir en este ciclo</span>
            <span style="font-size: 12px; color: #64748b;">Ciclo: {{ $suscripcion->fecha_inicio->format('d/m/Y') }} - {{ $suscripcion->proximo_pago->format('d/m/Y') }}</span>
        </div>
    </div>
    @endif
</div>

<!-- Plan details -->
<div class="card" style="margin-bottom: 16px;">
    <h3 style="font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">Detalles del Plan</h3>
    <div class="grid grid-cols-4">
        <div>
            <p style="font-size: 11px; color: #64748b; text-transform: uppercase;">Plan</p>
            <p style="font-size: 15px; font-weight: 600; margin-top: 2px;">{{ $suscripcion->plan->nombre }}</p>
        </div>
        <div>
            <p style="font-size: 11px; color: #64748b; text-transform: uppercase;">Fecha Inicio</p>
            <p style="font-size: 15px; font-weight: 600; margin-top: 2px;">{{ $suscripcion->fecha_inicio->format('d/m/Y') }}</p>
        </div>
        <div>
            <p style="font-size: 11px; color: #64748b; text-transform: uppercase;">Próximo Pago</p>
            <p style="font-size: 15px; font-weight: 600; margin-top: 2px;">{{ $suscripcion->proximo_pago->format('d/m/Y') }}</p>
        </div>
        <div>
            <p style="font-size: 11px; color: #64748b; text-transform: uppercase;">Valor Mensual</p>
            <p style="font-size: 15px; font-weight: 600; margin-top: 2px;">{{ $suscripcion->plan->precio }} UF +IVA</p>
            <p style="font-size: 11px; color: #64748b;">${{ number_format($suscripcion->plan->precio * $valorUF, 0, ',', '.') }} CLP aprox.</p>
        </div>
    </div>
</div>

<!-- Pagos -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
        <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Historial de Pagos</h3>
    </div>
    <table>
        <thead>
            <tr><th>Fecha</th><th>Plan</th><th>Período</th><th>Monto</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @foreach($pagos as $pago)
            <tr>
                <td>{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $pago->plan->nombre ?? $pago->concepto ?? 'Suscripción' }}</td>
                <td>
                    @if($pago->periodo_inicio && $pago->periodo_fin)
                        {{ \Carbon\Carbon::parse($pago->periodo_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($pago->periodo_fin)->format('d/m/Y') }}
                    @else - @endif
                </td>
                <td style="font-weight: 700;">{{ number_format($pago->monto ?? $pago->amount ?? 0, 0, ',', '.') }} CLP</td>
                <td><span class="badge badge-green">Pagado</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
