<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\FacturaServicio;
use App\Models\Suscripcion;
use App\Models\Payment;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use App\Models\Setting;

class FacturaServicioController extends Controller
{
    private $apiKey;
    private $secretKey;
    private $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('flow.api_key');
        $this->secretKey = config('flow.secret_key');
        $this->apiUrl = config('flow.api_url');
    }

    /**
     * Vista de facturas del cliente
     */
    public function index()
    {
        $userId = auth()->id();

        $facturas = FacturaServicio::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener uso actual del ciclo
        $suscripcion = Suscripcion::where('user_id', $userId)
            ->where('estado', 'activa')
            ->with('plan')
            ->first();

        $usoCiclo = null;
        if ($suscripcion) {
            $docsEmitidos = $this->contarDocumentosCiclo(
                $userId,
                $suscripcion->fecha_inicio,
                $suscripcion->fecha_fin ?? now()
            );
            $limiteIncluido = $suscripcion->plan->monthly_order_limit ?? 0;
            $docsExtra = max(0, $docsEmitidos - $limiteIncluido);

            // Usar valor UF desde cache/DB en vez de llamada HTTP externa
            $valorUF = $this->obtenerValorUF();
            $montoExtraCLP = round($docsExtra * 0.0002 * $valorUF);

            $usoCiclo = [
                'docs_emitidos' => $docsEmitidos,
                'limite_incluido' => $limiteIncluido,
                'docs_extra' => $docsExtra,
                'monto_extra_clp' => $montoExtraCLP,
                'fecha_inicio' => $suscripcion->fecha_inicio,
                'fecha_fin' => $suscripcion->fecha_fin,
                'pausada' => $suscripcion->pausada,
                'plan_nombre' => $suscripcion->plan->nombre ?? 'N/A',
            ];
        }

        // Calculate real CLP amounts for each factura
        $valorUFActual = $this->obtenerValorUF();
        foreach ($facturas as $factura) {
            $monto = (float) $factura->monto;
            // If monto is in UF (small number), convert to CLP
            if ($factura->moneda === 'UF' || ($monto > 0 && $monto < 100)) {
                $totalCLP = round($monto * $valorUFActual);
            } else {
                $totalCLP = round($monto);
            }
            // Calculate neto and IVA (IVA 19%)
            $factura->total_clp = $totalCLP;
            $factura->neto_clp = round($totalCLP / 1.19);
            $factura->iva_clp = $totalCLP - $factura->neto_clp;
        }

        return view('cliente.facturas-servicio', compact('facturas', 'usoCiclo', 'suscripcion', 'valorUFActual'));
    }

    /**
     * Pagar factura pendiente via Flow
     */
    public function pagarFactura(Request $request)
    {
        $request->validate([
            'factura_id' => 'required|numeric',
        ]);

        $factura = FacturaServicio::where('id', $request->factura_id)
            ->where('user_id', auth()->id())
            ->where('estado', 'pendiente')
            ->first();

        if (!$factura) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada o ya pagada'
            ], 404);
        }

        $monto = (int) $factura->monto;
        if ($monto < 350) {
            $monto = 350; // Monto mínimo de Flow
        }

        $params = [
            'apiKey' => $this->apiKey,
            'commerceOrder' => 'FACT-' . $factura->id . '-' . time(),
            'subject' => 'Factura ' . $factura->numero_factura . ' - ' . $factura->concepto,
            'currency' => 'CLP',
            'amount' => $monto,
            'email' => auth()->user()->email,
            'urlConfirmation' => route('factura-servicio.confirmation'),
            'urlReturn' => route('factura-servicio.return'),
            'optional' => 'factura_id:' . $factura->id . '|user_id:' . auth()->id(),
        ];

        $params['s'] = $this->signParams($params);

        try {
            $response = Http::withoutVerifying()->asForm()->post("{$this->apiUrl}/payment/create", $params);

            if ($response->successful()) {
                $data = $response->json();
                $checkoutUrl = $data['url'] . '?token=' . $data['token'];

                // Guardar token en la factura
                $factura->update(['flow_token' => $data['token']]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => $checkoutUrl,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pago: ' . $response->body()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error Flow factura pago: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con Flow'
            ], 500);
        }
    }

    /**
     * Return URL después del pago de factura
     */
    public function returnFromFlow(Request $request)
    {
        $token = $request->get('token');

        if (!$token) {
            return redirect()->route('cliente.facturas-servicio')->withErrors(['error' => 'Token no válido']);
        }

        $paymentStatus = $this->getPaymentStatus($token);

        if ($paymentStatus && isset($paymentStatus['status']) && $paymentStatus['status'] == 2) {
            $this->processFacturaPayment($paymentStatus, $token);
            return view('flow.success', ['payment' => $paymentStatus]);
        }

        return view('flow.failed', ['payment' => $paymentStatus ?? []]);
    }

    /**
     * Webhook de confirmación de pago de factura
     */
    public function confirmationWebhook(Request $request)
    {
        $token = $request->get('token');

        if (!$token) {
            return response('Token inválido', 400);
        }

        $paymentStatus = $this->getPaymentStatus($token);

        if ($paymentStatus && $paymentStatus['status'] == 2) {
            $this->processFacturaPayment($paymentStatus, $token);
        }

        return response('OK', 200);
    }

    /**
     * Procesar pago de factura: marcar como pagada y reanudar suscripción
     */
    private function processFacturaPayment($paymentStatus, $token)
    {
        try {
            // Buscar factura por flow_token
            $factura = FacturaServicio::where('flow_token', $token)->first();

            if (!$factura) {
                // Intentar extraer factura_id del optional
                $optional = $paymentStatus['optional'] ?? '';
                if (!empty($optional)) {
                    parse_str(str_replace(['|', ':'], ['&', '='], $optional), $optionalData);
                    $facturaId = $optionalData['factura_id'] ?? null;
                    if ($facturaId) {
                        $factura = FacturaServicio::find($facturaId);
                    }
                }
            }

            if (!$factura || $factura->estado === 'pagada') {
                Log::info('Factura ya pagada o no encontrada', ['token' => $token]);
                return;
            }

            // Marcar factura como pagada
            $factura->update([
                'estado' => 'pagada',
                'pagada_at' => now(),
                'flow_token' => $token,
            ]);

            Log::info('Factura de servicio pagada', [
                'factura_id' => $factura->id,
                'monto' => $factura->monto,
            ]);

            // Reanudar suscripción
            $suscripcion = $factura->suscripcion;
            if ($suscripcion && $suscripcion->pausada) {
                // Renovar ciclo: nuevo periodo de 30 días
                $nuevaFechaInicio = now();
                $nuevaFechaFin = now()->addDays(30);

                $suscripcion->update([
                    'pausada' => false,
                    'pausada_at' => null,
                    'motivo_pausa' => null,
                    'fecha_inicio' => $nuevaFechaInicio,
                    'fecha_fin' => $nuevaFechaFin,
                    'proximo_pago' => $nuevaFechaFin,
                ]);

                Log::info('Suscripción reanudada tras pago de factura', [
                    'suscripcion_id' => $suscripcion->id,
                    'nueva_fecha_fin' => $nuevaFechaFin,
                ]);

                $suscripcion->resetReminders();
            }

            // Crear registro de pago
            Payment::create([
                'order_id' => $paymentStatus['commerceOrder'],
                'flow_token' => $token,
                'subject' => $paymentStatus['subject'],
                'amount' => $paymentStatus['amount'],
                'currency' => $paymentStatus['currency'],
                'email' => $paymentStatus['payer'],
                'payment_method' => $paymentStatus['paymentMethod'] ?? 0,
                'status' => $paymentStatus['status'],
                'flow_response' => $paymentStatus,
                'paid_at' => now(),
                'user_id' => $factura->user_id,
                'suscripcion_id' => $factura->suscripcion_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando pago de factura: ' . $e->getMessage());
        }
    }

    /**
     * Descargar PDF de factura
     */
    public function descargarPDF($id)
    {
        $factura = FacturaServicio::where('id', $id)
            ->where('user_id', auth()->id())
            ->with(['plan', 'suscripcion'])
            ->first();

        if (!$factura) {
            return back()->withErrors(['error' => 'Factura no encontrada']);
        }

        // If we have a stored PDF, use it
        if ($factura->pdf_base64) {
            $pdfContent = base64_decode($factura->pdf_base64);
            $filename = 'factura_' . $factura->numero_factura . '.pdf';
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        // Generate PDF on-the-fly
        $valorUF = $this->obtenerValorUF();
        $monto = (float) $factura->monto;
        if ($factura->moneda === 'UF' || ($monto > 0 && $monto < 100)) {
            $totalCLP = round($monto * $valorUF);
        } else {
            $totalCLP = round($monto);
        }
        $netoCLP = round($totalCLP / 1.19);
        $ivaCLP = $totalCLP - $netoCLP;

        $user = auth()->user();
        $cliente = $user->cliente ?? null;

        $html = $this->generarHTMLFactura($factura, $user, $cliente, $netoCLP, $ivaCLP, $totalCLP, $valorUF);

        // Use wkhtmltopdf to generate PDF
        $tempHtml = tempnam(sys_get_temp_dir(), 'factura_') . '.html';
        $tempPdf = tempnam(sys_get_temp_dir(), 'factura_') . '.pdf';
        file_put_contents($tempHtml, $html);

        $command = 'wkhtmltopdf --quiet --page-size A4 --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm ' . escapeshellarg($tempHtml) . ' ' . escapeshellarg($tempPdf) . ' 2>&1';
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($tempPdf)) {
            @unlink($tempHtml);
            @unlink($tempPdf);
            Log::error('Error generating PDF with wkhtmltopdf', ['output' => implode("\n", $output)]);
            return back()->withErrors(['error' => 'Error al generar el PDF']);
        }

        $pdfContent = file_get_contents($tempPdf);
        @unlink($tempHtml);
        @unlink($tempPdf);

        $filename = 'factura_servicio_' . $factura->numero_factura . '.pdf';
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Generar HTML para la factura de servicio PDF
     */
    private function generarHTMLFactura($factura, $user, $cliente, $neto, $iva, $total, $valorUF)
    {
        $fecha = $factura->created_at ? $factura->created_at->format('d/m/Y') : date('d/m/Y');
        $periodoInicio = $factura->periodo_inicio ? $factura->periodo_inicio->format('d/m/Y') : '-';
        $periodoFin = $factura->periodo_fin ? $factura->periodo_fin->format('d/m/Y') : '-';
        $planNombre = $factura->plan->nombre ?? $factura->concepto ?? 'Suscripci\u00f3n';
        $clienteNombre = $user->name ?? 'Cliente';
        $clienteEmail = $user->email ?? '';
        $clienteRut = $cliente->rut ?? '';
        $clienteRazonSocial = $cliente->razon_social ?? $clienteNombre;

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 40px; }
                .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 3px solid #FFC107; padding-bottom: 20px; }
                .company { font-size: 18px; font-weight: bold; color: #1a1a1a; }
                .company-details { font-size: 10px; color: #666; margin-top: 5px; }
                .invoice-title { font-size: 24px; font-weight: bold; color: #FFC107; text-align: right; }
                .invoice-number { font-size: 14px; color: #666; text-align: right; }
                .section { margin-bottom: 20px; }
                .section-title { font-size: 11px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; }
                .client-info { background: #f9f9f9; padding: 15px; border-radius: 6px; }
                .client-info p { margin: 3px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background: #1a1a1a; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; }
                td { padding: 10px 12px; border-bottom: 1px solid #eee; }
                .totals { margin-top: 20px; text-align: right; }
                .totals table { width: 300px; margin-left: auto; }
                .totals td { padding: 6px 12px; border: none; }
                .totals .total-row { font-size: 16px; font-weight: bold; background: #FFC107; color: #1a1a1a; }
                .footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #999; text-align: center; }
            </style>
        </head>
        <body>
            <table style="width: 100%; border: none; margin-bottom: 30px; border-bottom: 3px solid #FFC107; padding-bottom: 20px;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <div class="company">Big Studio SpA</div>
                        <div class="company-details">
                            RUT: 78.153.109-K<br>
                            hola@bigstudio.cl<br>
                            Santiago, Chile
                        </div>
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <div class="invoice-title">FACTURA DE SERVICIO</div>
                        <div class="invoice-number">' . $factura->numero_factura . '</div>
                        <div style="color: #666; margin-top: 5px;">Fecha: ' . $fecha . '</div>
                    </td>
                </tr>
            </table>

            <div class="section">
                <div class="section-title">Datos del Cliente</div>
                <div class="client-info">
                    <p><strong>' . htmlspecialchars($clienteRazonSocial) . '</strong></p>
                    ' . ($clienteRut ? '<p>RUT: ' . htmlspecialchars($clienteRut) . '</p>' : '') . '
                    <p>Email: ' . htmlspecialchars($clienteEmail) . '</p>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Detalle del Servicio</div>
                <table>
                    <thead>
                        <tr>
                            <th>Descripci\u00f3n</th>
                            <th>Per\u00edodo</th>
                            <th style="text-align: right;">Neto</th>
                            <th style="text-align: right;">IVA (19%)</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . htmlspecialchars($planNombre) . '</td>
                            <td>' . $periodoInicio . ' - ' . $periodoFin . '</td>
                            <td style="text-align: right;">$' . number_format($neto, 0, ',', '.') . '</td>
                            <td style="text-align: right;">$' . number_format($iva, 0, ',', '.') . '</td>
                            <td style="text-align: right;">$' . number_format($total, 0, ',', '.') . '</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="totals">
                <table>
                    <tr><td>Neto:</td><td style="text-align: right;">$' . number_format($neto, 0, ',', '.') . '</td></tr>
                    <tr><td>IVA (19%):</td><td style="text-align: right;">$' . number_format($iva, 0, ',', '.') . '</td></tr>
                    <tr class="total-row"><td style="padding: 10px 12px;">TOTAL CLP:</td><td style="text-align: right; padding: 10px 12px;">$' . number_format($total, 0, ',', '.') . '</td></tr>
                </table>
            </div>

            <div class="footer">
                <p>Big Studio SpA - RUT: 78.153.109-K - hola@bigstudio.cl</p>
                <p>Este documento es una factura de servicio por el uso de la plataforma de integraci\u00f3n Shopify-Lioren.</p>
                ' . ($factura->moneda === 'UF' ? '<p>Valor UF utilizado: $' . number_format($valorUF, 2, ',', '.') . '</p>' : '') . '
            </div>
        </body>
        </html>';
    }

    // ---- Helper methods ----

    private function contarDocumentosCiclo(int $userId, $inicio, $fin): int
    {
        $boletas = Boleta::where('user_id', $userId)
            ->whereBetween('created_at', [$inicio, $fin])
            ->where('status', 'emitida')
            ->count();

        $facturas = FacturaEmitida::where('user_id', $userId)
            ->whereBetween('created_at', [$inicio, $fin])
            ->where('status', 'emitida')
            ->count();

        return $boletas + $facturas;
    }

    /**
     * Obtener valor UF desde cache (1 hora) o DB, sin llamada HTTP externa en cada request
     */
    private function obtenerValorUF(): float
    {
        return Cache::remember('valor_uf', 3600, function () {
            // Primero intentar desde system_settings (actualizado por cron diario)
            $setting = Setting::where('key', 'valor_uf')->first();
            if ($setting && $setting->value > 0) {
                return (float) $setting->value;
            }

            // Fallback: llamada HTTP (solo si no hay valor en DB)
            try {
                $response = Http::withoutVerifying()->timeout(5)->get('https://mindicador.cl/api/uf');
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['serie'][0]['valor'] ?? 39841.72;
                }
            } catch (\Exception $e) {
                Log::error('Error obteniendo valor UF: ' . $e->getMessage());
            }

            return 39841.72; // Fallback
        });
    }

    private function getPaymentStatus($token)
    {
        $params = [
            'apiKey' => $this->apiKey,
            'token' => $token,
        ];
        $params['s'] = $this->signParams($params);

        try {
            $response = Http::withoutVerifying()->get("{$this->apiUrl}/payment/getStatus", $params);
            if ($response->successful()) {
                return $response->json();
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Error Flow getStatus: ' . $e->getMessage());
            return null;
        }
    }

    private function signParams(array $params)
    {
        ksort($params);
        $toSign = '';
        foreach ($params as $key => $value) {
            $toSign .= $key . $value;
        }
        return hash_hmac('sha256', $toSign, $this->secretKey);
    }
}
