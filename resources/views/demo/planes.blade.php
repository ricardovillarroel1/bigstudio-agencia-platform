@extends('demo.layout')
@section('page-title', 'Planes Disponibles')
@section('content')
<div style="margin-bottom: 20px;">
    <h2 style="font-size: 20px; font-weight: 700; color: #1e293b;">Planes Disponibles</h2>
    <p style="font-size: 13px; color: #64748b;">Selecciona el plan que mejor se adapte a tu negocio</p>
</div>

<div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 20px;">
    @foreach($planes as $plan)
    <div class="card" style="border: 2px solid {{ $plan->precio == 1.7 ? '#6366f1' : '#e2e8f0' }}; position: relative;">
        @if($plan->precio == 1.7)
        <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #6366f1; color: #fff; padding: 2px 14px; border-radius: 12px; font-size: 11px; font-weight: 600;">POPULAR</div>
        @endif
        <div style="text-align: center; padding: 12px 0 20px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b;">{{ $plan->nombre }}</h3>
            <p style="font-size: 13px; color: #64748b; margin-top: 4px;">{{ $plan->descripcion }}</p>
            <div style="margin-top: 16px;">
                <span style="font-size: 36px; font-weight: 800; color: #1e293b;">{{ $plan->precio }}</span>
                <span style="font-size: 14px; color: #64748b;"> UF +IVA/mes</span>
            </div>
            <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Hasta {{ number_format($plan->monthly_order_limit, 0, ',', '.') }} documentos por ciclo</p>
        </div>
        <div style="border-top: 1px solid #f1f5f9; padding-top: 16px;">
            <ul style="list-style: none; padding: 0;">
                @foreach($plan->features as $feature)
                <li style="display: flex; align-items: center; gap: 8px; padding: 6px 0; font-size: 13px; color: #475569;">
                    <svg width="16" height="16" fill="#22c55e" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>
        </div>
        <div style="margin-top: 16px;">
            <a href="#" onclick="showDemoToast(); return false;" class="btn btn-primary" style="display: block; text-align: center; width: 100%; {{ $plan->precio == 1.7 ? 'background: #6366f1;' : '' }}">
                Contratar Plan
            </a>
        </div>
    </div>
    @endforeach
</div>
@endsection
