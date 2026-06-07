<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FacturaServicio;
use App\Models\Suscripcion;
use App\Models\User;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use App\Services\FacturaServicioEmitter;
use App\Models\AdminActionLog;

class AdminBillingController extends Controller
{
    /**
     * Vista principal de facturación - lista de clientes con uso de documentos
     */
    public function index()
    {
        $clientes = User::role('cliente')
            ->with(['suscripciones' => function ($q) {
                $q->where('estado', 'activa')->with('plan');
            }])
            ->get()
            ->map(function ($cliente) {
                $suscripcion = $cliente->suscripciones->first();
                $plan = $suscripcion ? $suscripcion->plan : null;

                $docsEmitidos = 0;
                $limiteIncluido = 0;
                $docsExtra = 0;

                if ($suscripcion) {
                    $docsEmitidos = $this->contarDocumentosCiclo(
                        $cliente->id,
                        $suscripcion->fecha_inicio,
                        $suscripcion->fecha_fin ?? now()
                    );
                    $limiteIncluido = $plan->monthly_order_limit ?? 0;
                    $docsExtra = max(0, $docsEmitidos - $limiteIncluido);
                }

                return [
                    'id' => $cliente->id,
                    'name' => $cliente->name,
                    'email' => $cliente->email,
                    'plan_nombre' => $plan ? $plan->nombre : 'Sin plan',
                    'docs_emitidos' => $docsEmitidos,
                    'limite_incluido' => $limiteIncluido,
                    'docs_extra' => $docsExtra,
                    'suscripcion' => $suscripcion,
                    'pausada' => $suscripcion ? $suscripcion->pausada : false,
                    'facturas_pendientes' => FacturaServicio::where('user_id', $cliente->id)
                        ->where('estado', 'pendiente')
                        ->count(),
                ];
            });

        return view('admin.billing.index', compact('clientes'));
    }

    /**
     * Detalle de facturación de un cliente específico
     */
    public function show(Request $request, $userId)
    {
        $cliente = User::findOrFail($userId);

        $suscripcion = Suscripcion::where('user_id', $userId)
            ->where('estado', 'activa')
            ->with('plan')
            ->first();

        $valorUF = $this->obtenerValorUF();

        // Filtros del historial de facturas
        $filtros = [
            'estado' => $request->query('estado', ''),
            'desde'  => $request->query('desde', ''),
            'hasta'  => $request->query('hasta', ''),
            'q'      => trim((string) $request->query('q', '')),
        ];
        $hayFiltros = $filtros['estado'] !== '' || $filtros['desde'] !== '' || $filtros['hasta'] !== '' || $filtros['q'] !== '';

        $totalSinFiltros = FacturaServicio::where('user_id', $userId)->count();

        $facturasQuery = FacturaServicio::where('user_id', $userId);
        if ($filtros['estado'] !== '') {
            $facturasQuery->where('estado', $filtros['estado']);
        }
        if ($filtros['desde'] !== '') {
            $facturasQuery->whereDate('periodo_inicio', '>=', $filtros['desde']);
        }
        if ($filtros['hasta'] !== '') {
            $facturasQuery->whereDate('periodo_inicio', '<=', $filtros['hasta']);
        }
        if ($filtros['q'] !== '') {
            $q = $filtros['q'];
            $facturasQuery->where(function ($w) use ($q) {
                $w->where('numero_factura', 'like', "%{$q}%")
                  ->orWhere('folio', 'like', "%{$q}%");
            });
        }

        $facturas = $facturasQuery
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($factura) use ($valorUF) {
                $monto = (float) $factura->monto;
                // If monto_plan_clp is 0 or null, calculate from monto (which is in UF)
                if (empty($factura->monto_plan_clp) || $factura->monto_plan_clp == 0) {
                    $totalCLP = round($monto * $valorUF);
                    $netoCLP = round($totalCLP / 1.19);
                    $ivaCLP = $totalCLP - $netoCLP;
                    $factura->monto_plan_clp = $totalCLP;
                    $factura->monto_neto = $netoCLP;
                    $factura->monto_iva = $ivaCLP;
                    $factura->total_clp = $totalCLP + ($factura->monto_extra_clp ?? 0);
                } else {
                    $factura->total_clp = $factura->monto_plan_clp + ($factura->monto_extra_clp ?? 0);
                }
                return $factura;
            });

        $usoCiclo = null;
        if ($suscripcion) {
            $docsEmitidos = $this->contarDocumentosCiclo(
                $userId,
                $suscripcion->fecha_inicio,
                $suscripcion->fecha_fin ?? now()
            );
            $limiteIncluido = $suscripcion->plan->monthly_order_limit ?? 0;
            $docsExtra = max(0, $docsEmitidos - $limiteIncluido);
            // $valorUF already obtained above
            $montoExtraCLP = round($docsExtra * 0.0002 * $valorUF);

            $usoCiclo = [
                'docs_emitidos' => $docsEmitidos,
                'limite_incluido' => $limiteIncluido,
                'docs_extra' => $docsExtra,
                'monto_extra_clp' => $montoExtraCLP,
                'precio_extra_uf' => $docsExtra * 0.0002,
                'valor_uf' => $valorUF,
                'fecha_inicio' => $suscripcion->fecha_inicio,
                'fecha_fin' => $suscripcion->fecha_fin,
                'pausada' => $suscripcion->pausada,
                'plan_nombre' => $suscripcion->plan->nombre ?? 'N/A',
            ];
        }

        // Datos de facturación del cliente
        $datosFacturacion = [
            'razon_social' => $cliente->cliente->empresa ?? '-',
            'rut' => $cliente->cliente->rut ?? '-',
            'giro' => $cliente->cliente->giro ?? '-',
            'direccion' => $cliente->cliente->direccion ?? '-',
        ];

        // Historial de acciones admin para este cliente (últimas 15)
        $adminLogs = AdminActionLog::with('admin:id,name,email')
            ->where('target_user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('admin.billing.show', compact('cliente', 'suscripcion', 'facturas', 'usoCiclo', 'datosFacturacion', 'valorUF', 'filtros', 'hayFiltros', 'totalSinFiltros', 'adminLogs'));
    }

    /**
     * Pausar/reanudar manualmente una suscripción
     */
    public function togglePausa(Request $request, $suscripcionId)
    {
        $suscripcion = Suscripcion::findOrFail($suscripcionId);
        $estabaPausada = (bool) $suscripcion->pausada;

        if ($estabaPausada) {
            $suscripcion->update([
                'pausada' => false,
                'pausada_at' => null,
                'motivo_pausa' => null,
            ]);
            AdminActionLog::record('reanudar', $suscripcion, [
                'plan_id'       => $suscripcion->plan_id,
                'pausada_desde' => optional($suscripcion->pausada_at)->toIso8601String(),
            ], $suscripcion->user_id);
            return back()->with('success', 'Suscripción reanudada manualmente');
        } else {
            $suscripcion->update([
                'pausada' => true,
                'pausada_at' => now(),
                'motivo_pausa' => 'Pausada manualmente por administrador',
            ]);
            AdminActionLog::record('pausar', $suscripcion, [
                'plan_id' => $suscripcion->plan_id,
                'motivo'  => 'manual',
            ], $suscripcion->user_id);
            return back()->with('success', 'Suscripción pausada manualmente');
        }
    }

    // ---- Helpers ----

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

    private function obtenerValorUF(): float
    {
        try {
            $response = Http::withoutVerifying()->timeout(10)->get('https://mindicador.cl/api/uf');
            if ($response->successful()) {
                $data = $response->json();
                return $data['serie'][0]['valor'] ?? 39841.72;
            }
        } catch (\Exception $e) {
            Log::error('Error obteniendo valor UF: ' . $e->getMessage());
        }
        return 39841.72;
    }

    /**
     * Descargar/Ver PDF de factura de servicio (admin)
     */
    public function descargarPDF($facturaId)
    {
        $factura = FacturaServicio::with(['plan', 'suscripcion', 'user'])->findOrFail($facturaId);

        // If we have a stored PDF from Lioren, use it
        if ($factura->pdf_base64) {
            $pdfContent = base64_decode($factura->pdf_base64);
            $filename = 'factura_' . ($factura->folio ? 'folio_' . $factura->folio : $factura->numero_factura) . '.pdf';
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        }

        // Generate PDF on-the-fly if no stored PDF
        $valorUF = $this->obtenerValorUF();
        $monto = (float) $factura->monto;
        if ($factura->moneda === 'UF' || ($monto > 0 && $monto < 100)) {
            $totalCLP = round($monto * $valorUF);
        } else {
            $totalCLP = round($monto);
        }
        // Use stored values if available
        if (!empty($factura->monto_plan_clp) && $factura->monto_plan_clp > 0) {
            $totalCLP = (int) $factura->monto_plan_clp + (int) ($factura->monto_extra_clp ?? 0);
        }
        $netoCLP = round($totalCLP / 1.19);
        $ivaCLP = $totalCLP - $netoCLP;

        $user = $factura->user;
        $cliente = $user ? $user->cliente : null;

        $fecha = $factura->created_at ? $factura->created_at->format('d/m/Y') : date('d/m/Y');
        $periodoInicio = $factura->periodo_inicio ? $factura->periodo_inicio->format('d/m/Y') : '-';
        $periodoFin = $factura->periodo_fin ? $factura->periodo_fin->format('d/m/Y') : '-';
        $planNombre = $factura->plan->nombre ?? $factura->concepto ?? 'Suscripción';
        $clienteNombre = $user->name ?? 'Cliente';
        $clienteEmail = $user->email ?? '';
        $clienteRut = $cliente->rut ?? '';
        $clienteRazonSocial = $cliente->razon_social ?? $cliente->empresa ?? $clienteNombre;

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 40px; }
                .company { font-size: 18px; font-weight: bold; color: #1a1a1a; }
                .company-details { font-size: 10px; color: #666; margin-top: 5px; }
                .invoice-title { font-size: 24px; font-weight: bold; color: #FFC107; text-align: right; }
                .invoice-number { font-size: 14px; color: #666; text-align: right; }
                .section-title { font-size: 11px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; }
                .client-info { background: #f9f9f9; padding: 15px; border-radius: 6px; }
                .client-info p { margin: 3px 0; }
                table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
                table.items th { background: #1a1a1a; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; }
                table.items td { padding: 10px 12px; border-bottom: 1px solid #eee; }
                .totals { margin-top: 20px; text-align: right; }
                .totals table { width: 300px; margin-left: auto; }
                .totals td { padding: 6px 12px; border: none; }
                .total-row { font-size: 16px; font-weight: bold; background: #FFC107; color: #1a1a1a; }
                .footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #999; text-align: center; }
            </style>
        </head>
        <body>
            <table style="width: 100%; border: none; margin-bottom: 30px; border-bottom: 3px solid #FFC107; padding-bottom: 20px;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <div class="company">Big Studio SpA</div>
                        <div class="company-details">RUT: 78.153.109-K<br>hola@bigstudio.cl<br>Santiago, Chile</div>
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <div class="invoice-title">FACTURA DE SERVICIO</div>
                        <div class="invoice-number">' . htmlspecialchars($factura->numero_factura) . '</div>
                        <div style="font-size: 11px; color: #999; margin-top: 4px;">Fecha: ' . $fecha . '</div>
                    </td>
                </tr>
            </table>
            <div class="client-info">
                <div class="section-title">Datos del Cliente</div>
                <p><strong>' . htmlspecialchars($clienteRazonSocial) . '</strong></p>
                <p>RUT: ' . htmlspecialchars($clienteRut) . '</p>
                <p>Email: ' . htmlspecialchars($clienteEmail) . '</p>
            </div>
            <table class="items">
                <thead><tr><th>Concepto</th><th>Período</th><th style="text-align:right;">Monto</th></tr></thead>
                <tbody>
                    <tr>
                        <td>' . htmlspecialchars($planNombre) . '</td>
                        <td>' . $periodoInicio . ' - ' . $periodoFin . '</td>
                        <td style="text-align:right;">$' . number_format($netoCLP, 0, ',', '.') . '</td>
                    </tr>
                </tbody>
            </table>
            <div class="totals">
                <table>
                    <tr><td>Neto:</td><td style="text-align:right;">$' . number_format($netoCLP, 0, ',', '.') . '</td></tr>
                    <tr><td>IVA (19%):</td><td style="text-align:right;">$' . number_format($ivaCLP, 0, ',', '.') . '</td></tr>
                    <tr class="total-row"><td style="padding:10px 12px;">TOTAL:</td><td style="text-align:right; padding:10px 12px;">$' . number_format($totalCLP, 0, ',', '.') . '</td></tr>
                </table>
            </div>
            <div class="footer">
                <p>Big Studio SpA - Servicios de Integración Shopify-Lioren</p>
                <p>Este documento es una factura de servicio generada automáticamente.</p>
            </div>
        </body>
        </html>';

        $tempHtml = tempnam(sys_get_temp_dir(), 'factura_') . '.html';
        $tempPdf = tempnam(sys_get_temp_dir(), 'factura_') . '.pdf';
        file_put_contents($tempHtml, $html);

        $command = 'wkhtmltopdf --quiet --page-size A4 --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm ' . escapeshellarg($tempHtml) . ' ' . escapeshellarg($tempPdf) . ' 2>&1';
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($tempPdf)) {
            @unlink($tempHtml);
            @unlink($tempPdf);
            Log::error('Error generating admin billing PDF', ['output' => implode("\n", $output)]);
            return back()->withErrors(['error' => 'Error al generar el PDF']);
        }

        $pdfContent = file_get_contents($tempPdf);
        @unlink($tempHtml);
        @unlink($tempPdf);

        $filename = 'factura_servicio_' . $factura->numero_factura . '.pdf';
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }


    /**
     * Re-emitir el DTE de una FacturaServicio que quedó sin folio.
     * Usado por el botón "⚡ Emitir DTE" en /admin/billing/{userId}.
     */
    public function reemitirDTE($facturaId)
    {
        $factura = FacturaServicio::with('plan', 'user')->findOrFail($facturaId);

        if ($factura->folio) {
            return back()->with('error',
                "Esta factura ya tiene folio Lioren #{$factura->folio}. No se re-emitió.");
        }

        $resultado = FacturaServicioEmitter::reemitirDTE($factura);

        if ($resultado['success']) {
            AdminActionLog::record('emitir_dte', $factura, [
                'folio'             => $resultado['folio'] ?? null,
                'numero_factura'    => $factura->numero_factura,
                'monto'             => (float) $factura->monto,
                'lioren_factura_id' => $resultado['lioren_factura_id'] ?? null,
            ], $factura->user_id);
            return back()->with('success',
                "✅ DTE re-emitido correctamente. Folio Lioren: {$resultado['folio']}");
        }

        AdminActionLog::record('emitir_dte', $factura, [
            'success' => false,
            'error'   => $resultado['error'] ?? 'desconocido',
        ], $factura->user_id);
        return back()->with('error', '❌ ' . $resultado['error']);
    }

    /**
     * PARCHE_11_RESET_CICLO: Reiniciar ciclo de docs manualmente
     */
    public function reiniciarCiclo($suscripcionId)
    {
        $suscripcion = \App\Models\Suscripcion::findOrFail($suscripcionId);
        $fechaInicioAnterior = optional($suscripcion->fecha_inicio)->toDateString();
        $fechaFinAnterior    = optional($suscripcion->fecha_fin)->toDateString();

        $inicio = now();
        $fin = (clone $inicio)->addDays(30);
        $suscripcion->update([
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'proximo_pago' => $fin,
            'pausada' => false,
            'pausada_at' => null,
            'motivo_pausa' => null,
        ]);

        AdminActionLog::record('reiniciar_ciclo', $suscripcion, [
            'fecha_inicio_anterior' => $fechaInicioAnterior,
            'fecha_fin_anterior'    => $fechaFinAnterior,
            'fecha_inicio_nueva'    => $inicio->toDateString(),
            'fecha_fin_nueva'       => $fin->toDateString(),
        ], $suscripcion->user_id);

        return back()->with('success', 'Ciclo reiniciado. Nuevo periodo: ' . $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y'));
    }

    /**
     * Marcar una factura de servicio como pagada manualmente.
     */
    public function marcarPagada($facturaId)
    {
        $factura = FacturaServicio::findOrFail($facturaId);
        if ($factura->estado === 'pagada') {
            return back()->with('error', 'Esta factura ya está marcada como pagada.');
        }
        $estadoAnterior = $factura->estado;
        $factura->update([
            'estado' => 'pagada',
            'pagada_at' => now(),
        ]);

        AdminActionLog::record('marcar_pagada', $factura, [
            'estado_anterior' => $estadoAnterior,
            'monto'           => (float) $factura->monto,
            'monto_clp'       => (int) ($factura->monto_plan_clp ?? 0) + (int) ($factura->monto_extra_clp ?? 0),
            'numero_factura'  => $factura->numero_factura,
            'folio'           => $factura->folio,
        ], $factura->user_id);

        return back()->with('success', 'Factura marcada como pagada correctamente.');
    }
}
