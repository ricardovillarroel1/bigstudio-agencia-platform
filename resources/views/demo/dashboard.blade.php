@extends('demo.layout')
@section('page-title', 'Dashboard')
@section('content')
<div style="padding: 8px 0;">
    <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">Bienvenido, Tienda Demo SpA</h2>
    <p style="color: #64748b; font-size: 14px;">Gestiona tu integración Shopify - Facturación Electrónica</p>
</div>

<div class="grid grid-cols-2" style="margin-top: 20px;">
    <a href="{{ route('demo.planes') }}" class="card" style="text-decoration: none; transition: box-shadow 0.15s;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="background: #dbeafe; border-radius: 12px; padding: 12px; display: flex;">
                <svg width="28" height="28" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
            </div>
            <div>
                <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Planes</h3>
                <p style="color: #64748b; font-size: 12px;">Ver planes disponibles</p>
            </div>
        </div>
    </a>
    <a href="{{ route('demo.chats') }}" class="card" style="text-decoration: none;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="background: #fce7f3; border-radius: 12px; padding: 12px; display: flex;">
                <svg width="28" height="28" fill="none" stroke="#db2777" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            </div>
            <div>
                <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Chats</h3>
                <p style="color: #64748b; font-size: 12px;">Soporte directo</p>
            </div>
        </div>
    </a>
    <a href="{{ route('demo.estados-solicitud') }}" class="card" style="text-decoration: none;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="background: #fef3c7; border-radius: 12px; padding: 12px; display: flex;">
                <svg width="28" height="28" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </div>
            <div>
                <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Estados de Solicitud</h3>
                <p style="color: #64748b; font-size: 12px;">Ver mis solicitudes</p>
            </div>
        </div>
    </a>
    <a href="{{ route('demo.planes-activos') }}" class="card" style="text-decoration: none;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="background: #dcfce7; border-radius: 12px; padding: 12px; display: flex;">
                <svg width="28" height="28" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Planes Activos</h3>
                <p style="color: #64748b; font-size: 12px;">Mis planes contratados</p>
            </div>
        </div>
    </a>
</div>

<div class="grid grid-cols-2" style="margin-top: 16px;">
    <a href="{{ route('demo.facturas') }}" class="card" style="text-decoration: none;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="background: #ede9fe; border-radius: 12px; padding: 12px; display: flex;">
                <svg width="28" height="28" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Mis Facturas</h3>
                <p style="color: #64748b; font-size: 12px;">Documentos tributarios</p>
            </div>
        </div>
    </a>
    <a href="{{ route('demo.documentos-emitidos') }}" class="card" style="text-decoration: none;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="background: #d1fae5; border-radius: 12px; padding: 12px; display: flex;">
                <svg width="28" height="28" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Documentos Emitidos</h3>
                <p style="color: #64748b; font-size: 12px;">Boletas, facturas y notas de crédito</p>
            </div>
        </div>
    </a>
    <a href="#" onclick="showDemoToast(); return false;" class="card" style="text-decoration: none;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="background: #e0e7ff; border-radius: 12px; padding: 12px; display: flex;">
                <svg width="28" height="28" fill="none" stroke="#4f46e5" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div>
                <h3 style="font-size: 15px; font-weight: 600; color: #1e293b;">Mi Perfil</h3>
                <p style="color: #64748b; font-size: 12px;">Configuración</p>
            </div>
        </div>
    </a>
</div>

<div class="card" style="margin-top: 24px; background: #eff6ff; border: 1px solid #bfdbfe;">
    <h3 style="font-size: 15px; font-weight: 600; color: #1e40af; margin-bottom: 6px;">📦 Información</h3>
    <p style="color: #1e40af; font-size: 13px;">Desde este panel podrás gestionar tus planes, ver el estado de tus solicitudes y consultar tus facturas.</p>
</div>
@endsection
