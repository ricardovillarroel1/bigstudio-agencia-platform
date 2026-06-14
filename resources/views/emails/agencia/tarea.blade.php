@php
    $brand600 = '#FF8100';
    $brand500 = '#FF9C00';
    $accent   = '#FFC800';
    $dark     = '#111827';
    $gray     = '#6B7280';
    $estados  = ['borrador'=>'Borrador','pendiente'=>'Pendiente','en_curso'=>'En curso','en_revision'=>'En revisión','requiere_cambios'=>'Requiere cambios','terminado'=>'Terminado'];
    $estadoColors = ['borrador'=>'#6B7280','pendiente'=>'#D97706','en_curso'=>'#2563EB','en_revision'=>'#7C3AED','requiere_cambios'=>'#DC2626','terminado'=>'#059669'];
    $estadoLabel = $estados[$tarea->estado] ?? ucfirst($tarea->estado);
    $estadoColor = $estadoColors[$tarea->estado] ?? '#6B7280';
    $clienteTxt = $cliente ? ' del cliente <strong>' . e($cliente->nombre) . '</strong>' : '';
@endphp
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0; padding:0; background:#F3F4F6; font-family:'Helvetica Neue',Arial,sans-serif; color:{{ $dark }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6; padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">

                {{-- Header --}}
                <tr><td style="background:linear-gradient(135deg,{{ $accent }} 0%,{{ $brand500 }} 50%,{{ $brand600 }} 100%); padding:28px 32px;">
                    <p style="margin:0; color:#ffffff; font-size:13px; font-weight:700; letter-spacing:2px; text-transform:uppercase;">Big Studio &middot; Agencia</p>
                    <p style="margin:6px 0 0; color:#ffffff; font-size:22px; font-weight:900;">Tienes una nueva tarea</p>
                </td></tr>

                {{-- Body --}}
                <tr><td style="padding:32px;">
                    <p style="margin:0 0 16px; font-size:14px; color:#374151;">Hola, se te ha compartido una tarea{!! $clienteTxt !!}. Aquí los detalles:</p>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E7EB; border-radius:10px; overflow:hidden;">
                        <tr><td style="padding:18px 20px;">
                            <span style="display:inline-block; background:{{ $estadoColor }}; color:#ffffff; font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:4px 10px; border-radius:20px;">{{ $estadoLabel }}</span>
                            <p style="margin:12px 0 0; font-size:18px; font-weight:800; color:{{ $dark }};">{{ $tarea->titulo }}</p>
                            @if($tarea->descripcion)
                                <p style="margin:10px 0 0; font-size:13px; color:#374151; line-height:1.6;">{!! nl2br(e($tarea->descripcion)) !!}</p>
                            @endif
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
                                @if($cliente)
                                <tr><td style="padding:4px 0; font-size:12px; color:{{ $gray }}; width:120px;">Cliente</td><td style="padding:4px 0; font-size:12px; color:{{ $dark }}; font-weight:600;">{{ $cliente->nombre }}</td></tr>
                                @endif
                                <tr><td style="padding:4px 0; font-size:12px; color:{{ $gray }};">Prioridad</td><td style="padding:4px 0; font-size:12px; color:{{ $dark }}; font-weight:600;">{{ ucfirst($tarea->prioridad) }}</td></tr>
                                @if($tarea->fecha_limite)
                                <tr><td style="padding:4px 0; font-size:12px; color:{{ $gray }};">Fecha límite</td><td style="padding:4px 0; font-size:12px; color:{{ $dark }}; font-weight:600;">{{ $tarea->fecha_limite->format('d/m/Y') }}</td></tr>
                                @endif
                            </table>
                        </td></tr>
                    </table>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;"><tr><td align="center">
                        <a href="{{ $panelUrl }}" style="display:inline-block; background:linear-gradient(135deg,{{ $brand500 }} 0%,{{ $brand600 }} 100%); color:#ffffff; text-decoration:none; font-size:14px; font-weight:700; padding:13px 28px; border-radius:8px;">Ver mis tareas</a>
                    </td></tr></table>
                    <p style="margin:14px 0 0; font-size:11px; color:{{ $gray }}; text-align:center;">Ingresa con tu cuenta de colaborador para ver y actualizar el estado de tus tareas.</p>
                </td></tr>

                {{-- Footer --}}
                <tr><td style="background:#F9FAFB; padding:18px 32px; border-top:1px solid #E5E7EB;">
                    <p style="margin:0; font-size:11px; color:{{ $gray }};"><strong style="color:{{ $brand600 }};">Big Studio</strong> &middot; Agencia de Marketing Digital &middot; hola@bigstudio.cl</p>
                </td></tr>

            </table>
        </td></tr>
    </table>
</body>
</html>
