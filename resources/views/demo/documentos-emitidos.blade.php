@extends('demo.layout')
@section('page-title', 'Documentos Emitidos')
@section('content')

<!-- Ciclo Info -->
@if(isset($cicloInfo))
<div class="card" style="margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="font-size: 14px; font-weight: 600; color: #1e293b;">
            Uso del Ciclo Actual
            @if($cicloInfo['plan'])
                <span class="badge badge-indigo" style="margin-left: 8px;">{{ $cicloInfo['plan'] }}</span>
            @endif
        </h3>
        <span style="font-size: 13px; color: #64748b;">
            {{ \Carbon\Carbon::parse($cicloInfo['inicio'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($cicloInfo['fin'])->format('d/m/Y') }}
        </span>
    </div>
    @if($cicloInfo['limite'])
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span style="font-size: 13px; color: #475569;">
                Documentos emitidos: <strong>{{ $cicloInfo['emitidos'] }}</strong> de <strong>{{ number_format($cicloInfo['limite'], 0, ',', '.') }}</strong>
            </span>
            <span style="font-size: 13px; font-weight: 600; color: #16a34a;">{{ $cicloInfo['porcentaje'] }}%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $cicloInfo['porcentaje'] }}%; background: #22c55e;"></div>
        </div>
        <div style="margin-top: 6px;">
            <span style="font-size: 12px; color: #64748b;">
                Te quedan <strong style="color: #16a34a;">{{ number_format($cicloInfo['disponibles'], 0, ',', '.') }}</strong> documentos disponibles en este ciclo
            </span>
        </div>
    @endif
</div>
@endif

<!-- Stats -->
<div class="grid grid-cols-4" style="margin-bottom: 16px;">
    <div class="card stat-card" style="border-color: #3b82f6;">
        <div class="label">Total Documentos</div>
        <div class="value" style="color: #1e293b;">{{ $stats['total'] }}</div>
    </div>
    <div class="card stat-card" style="border-color: #22c55e;">
        <div class="label">Boletas</div>
        <div class="value" style="color: #16a34a;">{{ $stats['boletas'] }}</div>
    </div>
    <div class="card stat-card" style="border-color: #6366f1;">
        <div class="label">Facturas</div>
        <div class="value" style="color: #4f46e5;">{{ $stats['facturas'] }}</div>
    </div>
    <div class="card stat-card" style="border-color: #ef4444;">
        <div class="label">Notas de Crédito</div>
        <div class="value" style="color: #dc2626;">{{ $stats['notas_credito'] }}</div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 16px;">
    <form method="GET" action="{{ route('demo.documentos-emitidos') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <div>
            <label style="display: block; font-size: 11px; font-weight: 500; color: #64748b; margin-bottom: 4px;">Tipo de Documento</label>
            <select name="tipo" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                <option value="">Todos</option>
                <option value="boleta" {{ request('tipo') == 'boleta' ? 'selected' : '' }}>Boletas</option>
                <option value="factura" {{ request('tipo') == 'factura' ? 'selected' : '' }}>Facturas</option>
                <option value="nota_credito" {{ request('tipo') == 'nota_credito' ? 'selected' : '' }}>Notas de Crédito</option>
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('demo.documentos-emitidos') }}" class="btn btn-outline">Limpiar</a>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
        <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Historial de Documentos Tributarios</h3>
    </div>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Folio</th>
                    <th>Receptor</th>
                    <th>RUT</th>
                    <th>Pedido</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documentos as $doc)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($doc->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($doc->tipodoc == 39)
                            <span class="badge badge-green">Boleta</span>
                        @elseif($doc->tipodoc == 33)
                            <span class="badge badge-blue">Factura</span>
                        @elseif($doc->tipodoc == 61)
                            <span class="badge badge-red">NC</span>
                        @endif
                    </td>
                    <td style="font-weight: 600;">{{ $doc->folio }}</td>
                    <td>{{ $doc->receptor_nombre }}</td>
                    <td style="font-size: 12px; color: #64748b;">{{ $doc->receptor_rut }}</td>
                    <td>#{{ $doc->shopify_order_number }}</td>
                    <td style="font-weight: 600;">${{ number_format($doc->monto_total, 0, ',', '.') }}</td>
                    <td><span class="badge badge-green">Emitida</span></td>
                    <td>
                        <a href="#" onclick="showDemoToast(); return false;" style="color: #dc2626; font-size: 12px; text-decoration: none; margin-right: 8px;" title="PDF"><i class="fas fa-file-pdf"></i> PDF</a>
                        <a href="#" onclick="showDemoToast(); return false;" style="color: #2563eb; font-size: 12px; text-decoration: none;" title="XML"><i class="fas fa-file-code"></i> XML</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
