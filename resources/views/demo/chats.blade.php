@extends('demo.layout')
@section('page-title', 'Chats')
@section('content')
<div class="grid grid-cols-2" style="grid-template-columns: 300px 1fr; height: calc(100vh - 180px);">
    <!-- Conversations list -->
    <div class="card" style="padding: 0; overflow: hidden; border-radius: 8px 0 0 8px;">
        <div style="padding: 16px; border-bottom: 1px solid #e2e8f0;">
            <h3 style="font-size: 14px; font-weight: 600;">Conversaciones</h3>
        </div>
        <div style="padding: 0;">
            <div style="padding: 14px 16px; background: #f1f5f9; border-left: 3px solid #6366f1; cursor: pointer;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Soporte Técnico</span>
                    <span style="font-size: 10px; color: #64748b;">Hoy</span>
                </div>
                <p style="font-size: 12px; color: #64748b; margin-top: 2px;">Tu integración ha sido configurada...</p>
            </div>
            <div style="padding: 14px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Consulta Facturación</span>
                    <span style="font-size: 10px; color: #64748b;">Ayer</span>
                </div>
                <p style="font-size: 12px; color: #64748b; margin-top: 2px;">Gracias por tu consulta, el plan...</p>
            </div>
        </div>
    </div>
    <!-- Chat area -->
    <div class="card" style="padding: 0; overflow: hidden; border-radius: 0 8px 8px 0; display: flex; flex-direction: column;">
        <div style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <h3 style="font-size: 14px; font-weight: 600;">Soporte Técnico</h3>
        </div>
        <div style="flex: 1; padding: 20px; overflow-y: auto;">
            <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                <div style="width: 32px; height: 32px; background: #6366f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 600; flex-shrink: 0;">BS</div>
                <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 0 12px 12px 12px; max-width: 70%;">
                    <p style="font-size: 13px; color: #334155;">Hola, bienvenido a Big Studio Integraciones. Tu integración con Shopify ha sido configurada correctamente. ¿En qué podemos ayudarte?</p>
                    <span style="font-size: 10px; color: #94a3b8; margin-top: 4px; display: block;">10:30 AM</span>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 16px; justify-content: flex-end;">
                <div style="background: #6366f1; padding: 10px 14px; border-radius: 12px 0 12px 12px; max-width: 70%;">
                    <p style="font-size: 13px; color: #fff;">Perfecto, gracias. Quería consultar sobre el límite de documentos de mi plan.</p>
                    <span style="font-size: 10px; color: rgba(255,255,255,0.6); margin-top: 4px; display: block;">10:32 AM</span>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                <div style="width: 32px; height: 32px; background: #6366f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 600; flex-shrink: 0;">BS</div>
                <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 0 12px 12px 12px; max-width: 70%;">
                    <p style="font-size: 13px; color: #334155;">Con el Plan PRO tienes hasta 2.000 documentos por ciclo de facturación. Puedes ver tu uso actual en la sección "Planes Activos".</p>
                    <span style="font-size: 10px; color: #94a3b8; margin-top: 4px; display: block;">10:35 AM</span>
                </div>
            </div>
        </div>
        <div style="padding: 12px 16px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px;">
            <input type="text" placeholder="Escribe un mensaje..." style="flex: 1; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px;" onclick="showDemoToast()">
            <button class="btn btn-primary" onclick="showDemoToast()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>
@endsection
