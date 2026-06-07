<?php
require __DIR__ . '/../shopify-integrator/vendor/autoload.php';
$app = require_once __DIR__ . '/../shopify-integrator/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

$user = (object)[
    'name' => 'Ricardo Villarroel',
    'email' => 'ricardoavillarroelgonzalez@gmail.com',
];
$plan = (object)['nombre' => 'Plan Profesional'];
$fechaVencimiento = '01/04/2026';
$precioUF = '2.02';
$precioCLP = '80.480';
$urlRenovar = 'https://integration-conector.bigstudio.cl/planes-activos';

// Test Correo 1 - 7 días
$contenidoHtml = "
<div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
    <!-- Header oscuro con branding Big Studio -->
    <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
        <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
        <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
        <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
    </div>

    <!-- Banner de alerta -->
    <div style='background: #FFC107; padding: 14px 20px; text-align: center;'>
        <p style='color: #0A0A0A; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Tu plan vence en 7 días</p>
    </div>

    <!-- Contenido principal -->
    <div style='padding: 30px 30px 20px;'>
        <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$user->name}</strong>,</p>
        <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Te informamos que tu plan de integración con BigStudio está próximo a vencer. A continuación, los detalles de tu suscripción actual:</p>

        <!-- Tabla de detalles del plan -->
        <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Plan</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$plan->nombre}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Precio</td>
                    <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFC107; border-bottom: 1px solid #222222;'>{$precioUF} UF (+IVA) &asymp; \${$precioCLP} CLP</td>
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

        <!-- Botón CTA -->
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$urlRenovar}' style='background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>
                RENOVAR MI PLAN
            </a>
        </div>

        <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si ya realizaste el pago, puedes ignorar este mensaje.</p>
    </div>

    <!-- Separador dorado -->
    <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>

    <!-- Footer oscuro -->
    <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
        <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
        <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
        <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Este es un correo automático. Si tienes consultas, contáctanos por el chat interno o responde a este correo.</p>
    </div>
</div>";

Mail::html($contenidoHtml, function ($message) use ($user) {
    $message->to($user->email)->subject('TEST Branding - Tu plan vence en 7 días | Integraciones BigStudio');
});

echo "Correo de prueba con branding dark+gold enviado a {$user->email}\n";
