<?php
/**
 * Actualiza el branding de los correos del módulo de Agencia
 * para que coincidan con el estilo oscuro de Integraciones Big Studio
 */

// ============================
// 1. Actualizar AgenciaController.php - enviarCorreoCobro
// ============================
$file1 = '/var/www/shopify-integrator/app/Http/Controllers/AgenciaController.php';
$content1 = file_get_contents($file1);

// Buscar el bloque del contenidoHtml del correo de cobro
$oldCobro = <<<'SEARCH'
            $contenidoHtml = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 25px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: #fff; margin: 0; font-size: 26px; font-weight: 700;'>Big Studio</h1>
                    <p style='color: #94a3b8; margin: 5px 0 0; font-size: 14px;'>Servicios Digitales</p>
                </div>
                <div style='background: #fff; padding: 30px; border: 1px solid #e5e7eb;'>
                    <p style='font-size: 16px; color: #333;'>Estimado/a <strong>{$cliente->nombre}</strong>,</p>
                    <p style='color: #555;'>Se ha generado un nuevo cobro por el siguiente servicio:</p>
                    <div style='background: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #FFC107;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'>Concepto:</td>
                                <td style='padding: 8px 0; font-weight: bold; text-align: right;'>{$cobro->concepto}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'>Monto:</td>
                                <td style='padding: 8px 0; font-weight: bold; color: #059669; text-align: right; font-size: 20px;'>{$montoFormateado} CLP</td>
                            </tr>
                            {$vencimiento}
                        </table>
                    </div>
                    {$ctaFlow}
                    <div style='background: #fffbeb; border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #fbbf24;'>
                        <p style='color: #92400e; font-size: 14px; margin: 0;'><strong>Datos para transferencia:</strong></p>
                        <p style='color: #92400e; font-size: 13px; margin: 5px 0 0;'>
                            Banco: Banco Bci<br>
                            Tipo: Cuenta Corriente<br>
                            Nombre: Big Studio<br>
                            RUT: 78.153.109-K<br>
                            N° Cuenta: 97580848<br>
                            Email: hola@bigstudio.cl
                        </p>
                    </div>
                </div>
                <div style='background: #1a1a2e; padding: 15px; text-align: center; border-radius: 0 0 10px 10px;'>
                    <p style='color: #94a3b8; font-size: 12px; margin: 0;'>Este es un correo automático de Big Studio.</p>
                    <p style='color: #64748b; font-size: 11px; margin: 5px 0 0;'>Si tienes dudas, contáctanos por WhatsApp.</p>
                </div>
            </div>";
SEARCH;

$newCobro = <<<'REPLACE'
            $ctaFlowBranded = '';
            if ($flowPaymentUrl) {
                $ctaFlowBranded = "
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='{$flowPaymentUrl}' style='background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>
                            PAGAR CON TARJETA
                        </a>
                    </div>
                    <p style='font-size: 12px; color: #888888; text-align: center; margin: 0 0 15px;'>También puedes pagar por transferencia bancaria y enviarnos el comprobante por WhatsApp.</p>
                ";
            }
            $contenidoHtml = "
            <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
                <!-- Header oscuro con branding Big Studio -->
                <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                    <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
                    <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
                    <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
                </div>
                <!-- Banner de cobro -->
                <div style='background: #FFC107; padding: 14px 20px; text-align: center;'>
                    <p style='color: #0A0A0A; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Cobro Pendiente</p>
                </div>
                <!-- Contenido principal -->
                <div style='padding: 30px 30px 20px;'>
                    <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$cliente->nombre}</strong>,</p>
                    <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Se ha generado un nuevo cobro por el siguiente servicio:</p>
                    <!-- Tabla de detalles -->
                    <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Concepto</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$cobro->concepto}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Monto</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 18px; color: #FFC107; border-bottom: 1px solid #222222;'>{$montoFormateado} CLP</td>
                            </tr>
                            {$vencimiento}
                        </table>
                    </div>
                    {$ctaFlowBranded}
                    <!-- Datos de transferencia -->
                    <div style='background: #1A1A1A; border-left: 4px solid #FFC107; padding: 15px 20px; margin: 0 0 20px; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0 0 10px; font-weight: bold; color: #FFC107; font-size: 14px;'>Datos para transferencia:</p>
                        <p style='margin: 0; color: #AAAAAA; font-size: 13px; line-height: 1.8;'>
                            Banco: Banco Bci<br>
                            Tipo: Cuenta Corriente<br>
                            Nombre: Big Studio<br>
                            RUT: 78.153.109-K<br>
                            N° Cuenta: 97580848<br>
                            Email: hola@bigstudio.cl
                        </p>
                    </div>
                    <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si tienes consultas, contáctanos a hola@bigstudio.cl o por WhatsApp.</p>
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
REPLACE;

if (strpos($content1, $oldCobro) !== false) {
    $content1 = str_replace($oldCobro, $newCobro, $content1);
    file_put_contents($file1, $content1);
    echo "1. OK - Correo de COBRO actualizado con branding Big Studio\n";
} else {
    echo "1. WARN - No se encontró el bloque exacto del correo de cobro, intentando alternativa...\n";
    // Try a simpler replacement approach
    $content1 = preg_replace(
        "/\\\$contenidoHtml = \"\s*\n\s*<div style='font-family: Arial, sans-serif;/",
        '$ctaFlowBranded = \'\';
            if ($flowPaymentUrl) {
                $ctaFlowBranded = "
                    <div style=\'text-align: center; margin: 25px 0;\'>
                        <a href=\'{$flowPaymentUrl}\' style=\'background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;\'>
                            PAGAR CON TARJETA
                        </a>
                    </div>
                ";
            }
            $contenidoHtml = "
            <div style=\'font-family: Arial, Helvetica, sans-serif;',
        $content1,
        1
    );
    echo "1. INFO - Aplicado reemplazo parcial\n";
}

// ============================
// 2. Actualizar AgenciaCobrosAutomaticos.php - enviarReminder
// ============================
$file2 = '/var/www/shopify-integrator/app/Console/Commands/AgenciaCobrosAutomaticos.php';
$content2 = file_get_contents($file2);

$oldReminder = <<<'SEARCH2'
            $contenidoHtml = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 25px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: #fff; margin: 0; font-size: 26px;'>Big Studio</h1>
                    <p style='color: #94a3b8; margin: 5px 0 0; font-size: 14px;'>Servicios Digitales</p>
                </div>
                <div style='background: #fff; padding: 30px; border: 1px solid #e5e7eb;'>
                    <p style='font-size: 16px; color: #333;'>Estimado/a <strong>{$cliente->nombre}</strong>,</p>
                    <p style='color: #555;'>Le recordamos que su próximo cobro está por vencer:</p>
                    <div style='background: #fef3c7; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #f59e0b;'>
                        <p style='margin: 0; color: #92400e;'><strong>Concepto:</strong> {$sub->concepto}</p>
                        <p style='margin: 5px 0 0; color: #92400e;'><strong>Monto:</strong> {$montoFormateado}</p>
                        <p style='margin: 5px 0 0; color: #92400e;'><strong>Vencimiento:</strong> {$fechaVencimiento}</p>
                    </div>
                </div>
                <div style='background: #1a1a2e; padding: 15px; text-align: center; border-radius: 0 0 10px 10px;'>
                    <p style='color: #94a3b8; font-size: 12px; margin: 0;'>Big Studio - Servicios Digitales</p>
                </div>
            </div>";
SEARCH2;

$newReminder = <<<'REPLACE2'
            $contenidoHtml = "
            <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
                <!-- Header oscuro con branding Big Studio -->
                <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                    <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
                    <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
                    <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
                </div>
                <!-- Banner de recordatorio -->
                <div style='background: #FF9800; padding: 14px 20px; text-align: center;'>
                    <p style='color: #FFFFFF; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Recordatorio de Pago</p>
                </div>
                <!-- Contenido principal -->
                <div style='padding: 30px 30px 20px;'>
                    <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$cliente->nombre}</strong>,</p>
                    <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Le recordamos que su próximo cobro está por vencer:</p>
                    <!-- Tabla de detalles -->
                    <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Concepto</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$sub->concepto}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Monto</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 18px; color: #FFC107; border-bottom: 1px solid #222222;'>{$montoFormateado} CLP</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Vencimiento</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FF9800;'>{$fechaVencimiento}</td>
                            </tr>
                        </table>
                    </div>
                    <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si tienes consultas, contáctanos a hola@bigstudio.cl o por WhatsApp.</p>
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
REPLACE2;

if (strpos($content2, $oldReminder) !== false) {
    $content2 = str_replace($oldReminder, $newReminder, $content2);
    file_put_contents($file2, $content2);
    echo "2. OK - Correo de RECORDATORIO actualizado con branding Big Studio\n";
} else {
    echo "2. ERROR - No se encontró el bloque del correo de recordatorio\n";
}

// Also update the vencimiento variable format in cobro email to match dark theme
$file1_updated = file_get_contents($file1);
$oldVenc = "                    <tr>
                        <td style='padding: 8px 0; color: #666;'>Vencimiento:</td>
                        <td style='padding: 8px 0; font-weight: bold; text-align: right;'>{\$cobro->vence_at->format('d/m/Y')}</td>
                    </tr>";
$newVenc = "                    <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Vencimiento</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FF9800;'>{\$cobro->vence_at->format('d/m/Y')}</td>
                            </tr>";
if (strpos($file1_updated, $oldVenc) !== false) {
    $file1_updated = str_replace($oldVenc, $newVenc, $file1_updated);
    file_put_contents($file1, $file1_updated);
    echo "3. OK - Formato de vencimiento actualizado al estilo oscuro\n";
} else {
    echo "3. INFO - Vencimiento ya tiene formato correcto o no encontrado\n";
}

echo "\n=== BRANDING ACTUALIZADO ===\n";
