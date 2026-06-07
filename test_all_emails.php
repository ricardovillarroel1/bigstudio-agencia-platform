<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

$userName = 'Ricardo Villarroel';
$userEmail = 'ricardoavillarroelgonzalez@gmail.com';
$planNombre = 'PLAN PRO 1.7 UF +IVA';
$precioUF = 1.7;
$precioCLP = '80.480';
$fechaVencimiento = '01/04/2026';
$urlRenovar = 'https://integration-conector.bigstudio.cl/planes-activos';

// ============================================
// CORREO 1 - 7 DÍAS ANTES
// ============================================
$html1 = "
<div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
    <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
        <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
        <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
        <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
    </div>
    <div style='background: #FFC107; padding: 14px 20px; text-align: center;'>
        <p style='color: #0A0A0A; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Tu plan vence en 7 días</p>
    </div>
    <div style='padding: 30px 30px 20px;'>
        <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$userName}</strong>,</p>
        <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Te informamos que tu plan de integración con BigStudio está próximo a vencer. A continuación, los detalles de tu suscripción actual:</p>
        <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Plan</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$planNombre}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Precio</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFC107; border-bottom: 1px solid #222222;'>{$precioUF} UF +IVA = \${$precioCLP} CLP</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Fecha de vencimiento</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$fechaVencimiento}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Días restantes</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 16px; color: #FFC107;'>7 días</td>
                </tr>
            </table>
        </div>
        <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Para asegurar la continuidad de tu servicio de sincronización con Shopify, te recomendamos renovar tu plan antes de la fecha de vencimiento.</p>
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$urlRenovar}' style='background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>RENOVAR MI PLAN</a>
        </div>
        <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si ya realizaste el pago, puedes ignorar este mensaje.</p>
    </div>
    <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
    <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
        <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
        <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
        <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Este es un correo automático. Si tienes consultas, contáctanos por el chat interno o responde a este correo.</p>
    </div>
</div>";

Mail::html($html1, function ($message) use ($userEmail) {
    $message->to($userEmail)->subject('[MUESTRA 1/4] Tu plan vence en 7 días — Renueva ahora | Integraciones BigStudio');
});
echo "Correo 1 (7 días) enviado\n";

sleep(2);

// ============================================
// CORREO 2 - 3 DÍAS ANTES
// ============================================
$html2 = "
<div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
    <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
        <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
        <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
        <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
    </div>
    <div style='background: #FF9800; padding: 14px 20px; text-align: center;'>
        <p style='color: #0A0A0A; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Quedan 3 días para renovar</p>
    </div>
    <div style='padding: 30px 30px 20px;'>
        <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$userName}</strong>,</p>
        <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Este es un recordatorio importante: tu plan de integración vence en <strong style='color: #FFC107;'>3 días</strong>. Si no renuevas antes del <strong>{$fechaVencimiento}</strong>, tu servicio de sincronización será suspendido automáticamente.</p>
        <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Plan</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$planNombre}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Precio</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFC107; border-bottom: 1px solid #222222;'>{$precioUF} UF +IVA = \${$precioCLP} CLP</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Fecha de vencimiento</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$fechaVencimiento}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Días restantes</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 16px; color: #FF9800;'>3 días</td>
                </tr>
            </table>
        </div>
        <div style='background: #1A1A1A; border-left: 4px solid #FF9800; padding: 15px 20px; margin: 20px 0; border-radius: 0 6px 6px 0;'>
            <p style='margin: 0 0 10px; font-weight: bold; color: #FF9800; font-size: 14px;'>¿Qué sucede si no renuevas?</p>
            <ul style='margin: 0; padding-left: 20px; color: #AAAAAA; font-size: 13px;'>
                <li style='margin-bottom: 5px;'>La sincronización de productos entre Lioren y Shopify se detendrá</li>
                <li style='margin-bottom: 5px;'>Los pedidos de Shopify dejarán de facturarse automáticamente</li>
                <li>El inventario dejará de actualizarse</li>
            </ul>
        </div>
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$urlRenovar}' style='background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>RENOVAR AHORA</a>
        </div>
        <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si ya realizaste el pago, puedes ignorar este mensaje.</p>
    </div>
    <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
    <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
        <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
        <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
        <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Este es un correo automático. Si tienes consultas, contáctanos por el chat interno o responde a este correo.</p>
    </div>
</div>";

Mail::html($html2, function ($message) use ($userEmail) {
    $message->to($userEmail)->subject('[MUESTRA 2/4] Quedan 3 días para renovar tu plan | Integraciones BigStudio');
});
echo "Correo 2 (3 días) enviado\n";

sleep(2);

// ============================================
// CORREO 3 - DÍA 0 (VENCE HOY)
// ============================================
$html3 = "
<div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
    <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
        <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
        <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
        <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
    </div>
    <div style='background: #FF5252; padding: 14px 20px; text-align: center;'>
        <p style='color: #FFFFFF; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Tu plan vence HOY</p>
    </div>
    <div style='padding: 30px 30px 20px;'>
        <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$userName}</strong>,</p>
        <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'><strong style='color: #FF5252;'>Tu plan de integración vence hoy, {$fechaVencimiento}.</strong> Si no renuevas durante el día de hoy, tu servicio será suspendido automáticamente al finalizar la jornada.</p>
        <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Plan</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$planNombre}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Precio</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFC107; border-bottom: 1px solid #222222;'>{$precioUF} UF +IVA = \${$precioCLP} CLP</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Fecha de vencimiento</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$fechaVencimiento}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Días restantes</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 16px; color: #FF5252;'>HOY</td>
                </tr>
            </table>
        </div>
        <div style='background: #1A1A1A; border-left: 4px solid #FF5252; padding: 15px 20px; margin: 20px 0; border-radius: 0 6px 6px 0;'>
            <p style='margin: 0 0 10px; font-weight: bold; color: #FF5252; font-size: 14px;'>Importante: Una vez suspendido el servicio</p>
            <ul style='margin: 0; padding-left: 20px; color: #AAAAAA; font-size: 13px;'>
                <li style='margin-bottom: 5px;'>Se detendrá toda sincronización con Shopify</li>
                <li style='margin-bottom: 5px;'>No se emitirán facturas ni boletas automáticas</li>
                <li>El inventario no se actualizará</li>
            </ul>
        </div>
        <p style='color: #AAAAAA; font-size: 14px;'>Puedes reactivar tu plan en cualquier momento renovando desde tu cuenta.</p>
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$urlRenovar}' style='background: #FF5252; color: #FFFFFF; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>RENOVAR AHORA — ÚLTIMO DÍA</a>
        </div>
        <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si ya realizaste el pago, puedes ignorar este mensaje.</p>
    </div>
    <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
    <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
        <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
        <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
        <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Este es un correo automático. Si tienes consultas, contáctanos por el chat interno o responde a este correo.</p>
    </div>
</div>";

Mail::html($html3, function ($message) use ($userEmail) {
    $message->to($userEmail)->subject('[MUESTRA 3/4] Tu plan vence hoy — Renueva para no perder tu servicio | Integraciones BigStudio');
});
echo "Correo 3 (día 0) enviado\n";

sleep(2);

// ============================================
// CORREO 4 - PLAN SUSPENDIDO
// ============================================
$fechaSuspension = '02/04/2026';
$html4 = "
<div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
    <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
        <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
        <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
        <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
    </div>
    <div style='background: #FF5252; padding: 14px 20px; text-align: center;'>
        <p style='color: #FFFFFF; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Plan Suspendido</p>
    </div>
    <div style='padding: 30px 30px 20px;'>
        <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$userName}</strong>,</p>
        <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Te informamos que tu plan de integración <strong style='color: #FFFFFF;'>{$planNombre}</strong> ha sido <strong style='color: #FF5252;'>suspendido</strong> debido a que no fue renovado antes de su fecha de vencimiento ({$fechaVencimiento}).</p>
        <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Plan</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$planNombre}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Estado</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FF5252; border-bottom: 1px solid #222222;'>Suspendido</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Fecha de suspensión</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF;'>{$fechaSuspension}</td>
                </tr>
            </table>
        </div>
        <div style='background: #1A1A1A; border-left: 4px solid #FF5252; padding: 15px 20px; margin: 0 0 20px; border-radius: 0 6px 6px 0;'>
            <p style='margin: 0 0 10px; font-weight: bold; color: #FF5252; font-size: 14px;'>¿Qué significa esto?</p>
            <ul style='margin: 0; padding-left: 20px; color: #AAAAAA; font-size: 13px;'>
                <li style='margin-bottom: 5px;'>La sincronización de productos entre Lioren y Shopify se ha detenido</li>
                <li style='margin-bottom: 5px;'>Los pedidos de Shopify ya no se facturan automáticamente</li>
                <li>El inventario ya no se actualiza entre ambas plataformas</li>
            </ul>
        </div>
        <div style='background: #1A1A1A; border-left: 4px solid #4CAF50; padding: 15px 20px; margin: 0 0 20px; border-radius: 0 6px 6px 0;'>
            <p style='margin: 0 0 5px; font-weight: bold; color: #4CAF50; font-size: 14px;'>¿Quieres reactivar tu servicio?</p>
            <p style='margin: 0; color: #AAAAAA; font-size: 13px;'>Puedes renovar tu plan en cualquier momento desde tu cuenta. Una vez renovado, el servicio se reactivará automáticamente.</p>
        </div>
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$urlRenovar}' style='background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>REACTIVAR MI PLAN</a>
        </div>
        <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si tienes consultas sobre tu cuenta o necesitas asistencia, contáctanos a hola@bigstudio.cl.</p>
    </div>
    <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
    <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
        <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
        <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
        <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Este es un correo automático. Si tienes consultas, contáctanos por el chat interno o responde a este correo.</p>
    </div>
</div>";

Mail::html($html4, function ($message) use ($userEmail) {
    $message->to($userEmail)->subject('[MUESTRA 4/4] Tu plan ha sido suspendido | Integraciones BigStudio');
});
echo "Correo 4 (suspensión) enviado\n";

echo "\n¡Los 4 correos de muestra fueron enviados exitosamente!\n";
