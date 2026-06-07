<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sub = \App\Models\Suscripcion::with(['plan', 'user'])->where('user_id', 8)->where('estado', 'activa')->first();
$user = $sub->user;
$plan = $sub->plan;

$fechaVencimiento = $sub->proximo_pago->format('d/m/Y');
$precioUF = $plan->precio;
$valorUF = 39842;
$precioCLP = number_format(round($precioUF * $valorUF), 0, ',', '.');
$urlRenovar = url('/planes-activos');

$contenidoHtml = "
<div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff;'>
    <div style='background: #1B2A4A; padding: 30px 20px; text-align: center;'>
        <h1 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: bold; letter-spacing: 1px;'>INTEGRACIONES BIG STUDIO</h1>
        <div style='width: 50px; height: 3px; background: #26C6DA; margin: 12px auto 0;'></div>
    </div>
    <div style='background: #26C6DA; padding: 15px 20px; text-align: center;'>
        <p style='color: #ffffff; margin: 0; font-size: 18px; font-weight: bold;'>Tu plan vence en 7 dias</p>
    </div>
    <div style='padding: 30px 30px 20px;'>
        <p style='font-size: 15px; color: #333; margin: 0 0 15px;'>Hola <strong>{$user->name}</strong>,</p>
        <p style='font-size: 14px; color: #555; line-height: 1.6; margin: 0 0 20px;'>Te informamos que tu plan de integracion con BigStudio esta proximo a vencer. A continuacion, los detalles de tu suscripcion actual:</p>
        <div style='background: #F8F9FA; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #E9ECEF;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr><td style='padding: 10px 0; color: #666; font-size: 14px; border-bottom: 1px solid #E9ECEF;'>Plan</td><td style='padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #333; border-bottom: 1px solid #E9ECEF;'>{$plan->nombre}</td></tr>
                <tr><td style='padding: 10px 0; color: #666; font-size: 14px; border-bottom: 1px solid #E9ECEF;'>Precio</td><td style='padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #333; border-bottom: 1px solid #E9ECEF;'>{$precioUF} UF (+IVA) ~ \${$precioCLP} CLP</td></tr>
                <tr><td style='padding: 10px 0; color: #666; font-size: 14px; border-bottom: 1px solid #E9ECEF;'>Fecha de vencimiento</td><td style='padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #333; border-bottom: 1px solid #E9ECEF;'>{$fechaVencimiento}</td></tr>
                <tr><td style='padding: 10px 0; color: #666; font-size: 14px;'>Dias restantes</td><td style='padding: 10px 0; font-weight: bold; text-align: right; font-size: 16px; color: #333;'>7 dias</td></tr>
            </table>
        </div>
        <p style='font-size: 14px; color: #555; line-height: 1.6; margin: 0 0 20px;'>Para asegurar la continuidad de tu servicio de sincronizacion con Shopify, te recomendamos renovar tu plan antes de la fecha de vencimiento.</p>
        <div style='text-align: center; margin: 25px 0;'>
            <a href='{$urlRenovar}' style='background: #26C6DA; color: #ffffff; padding: 14px 35px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 15px; display: inline-block; letter-spacing: 0.5px;'>RENOVAR MI PLAN</a>
        </div>
        <p style='font-size: 12px; color: #999; text-align: center; margin: 15px 0 0;'>Si ya realizaste el pago, puedes ignorar este mensaje.</p>
    </div>
    <div style='height: 3px; background: #26C6DA; margin: 0 30px;'></div>
    <div style='background: #F5F5F5; padding: 20px 30px; text-align: center;'>
        <p style='color: #666; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
        <p style='color: #999; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
        <p style='color: #bbb; font-size: 11px; margin: 10px 0 0;'>Este es un correo automatico. Si tienes consultas, contactanos por el chat interno o responde a este correo.</p>
    </div>
</div>";

\Illuminate\Support\Facades\Mail::html($contenidoHtml, function ($message) {
    $message->to('ricardoavillarroelgonzalez@gmail.com')->subject('Tu plan vence en 7 dias - Renueva ahora | Integraciones BigStudio');
});

echo "Correo de prueba (7 dias) enviado a ricardoavillarroelgonzalez@gmail.com\n";
