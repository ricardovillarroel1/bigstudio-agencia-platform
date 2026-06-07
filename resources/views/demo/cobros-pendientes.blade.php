@extends('demo.layout')
@section('page-title', 'Cobros Pendientes')
@section('content')
<div style="margin-bottom: 16px;">
    <h2 style="font-size: 20px; font-weight: 700; color: #1e293b;">Cobros Pendientes</h2>
    <p style="font-size: 13px; color: #64748b;">Pagos por realizar</p>
</div>

<div class="card" style="text-align: center; padding: 48px 20px;">
    <div style="font-size: 48px; margin-bottom: 12px;">🎉</div>
    <h3 style="font-size: 18px; font-weight: 600; color: #1e293b;">No tienes cobros pendientes</h3>
    <p style="font-size: 13px; color: #64748b; margin-top: 6px;">Todos tus pagos están al día. ¡Excelente!</p>
</div>
@endsection
