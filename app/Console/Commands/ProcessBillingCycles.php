<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Suscripcion;
use App\Models\FacturaServicio;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use App\Models\Cliente;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessBillingCycles extends Command
{
    protected $signature = 'billing:process-cycles';
    protected $description = 'Process billing cycles: generate invoices for ended cycles, pause subscriptions pending payment';

    // Costo extra por documento en UF + IVA
    const EXTRA_DOC_PRICE_UF = 0.0002;
    const IVA_RATE = 0.19;

    public function handle()
    {
        $this->info('=== Procesando ciclos de facturación ===');
        Log::info('=== billing:process-cycles INICIADO ===');

        // Obtener valor UF actual
        $valorUF = $this->obtenerValorUF();
        $this->info("Valor UF: $" . number_format($valorUF, 2, ',', '.'));

        // Buscar suscripciones activas cuyo ciclo ha terminado (fecha_fin <= hoy)
        $suscripcionesVencidas = Suscripcion::where('estado', 'activa')
            ->where('pausada', false)
            ->where('fecha_fin', '<=', now()->toDateString())
            ->with(['plan', 'user'])
            ->get();

        $this->info("Suscripciones con ciclo terminado: " . $suscripcionesVencidas->count());

        foreach ($suscripcionesVencidas as $suscripcion) {
            try {
                $this->procesarCiclo($suscripcion, $valorUF);
            } catch (\Exception $e) {
                $this->error("Error procesando suscripción #{$suscripcion->id}: " . $e->getMessage());
                Log::error("Error billing:process-cycles suscripcion #{$suscripcion->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('=== Procesamiento completado ===');
        Log::info('=== billing:process-cycles COMPLETADO ===');
    }

    private function procesarCiclo(Suscripcion $suscripcion, float $valorUF)
    {
        $user = $suscripcion->user;
        $plan = $suscripcion->plan;

        if (!$user || !$plan) {
            $this->warn("Suscripción #{$suscripcion->id} sin usuario o plan - omitida");
            return;
        }

        // Verificar si es la cuenta LIOREN (plan gratis) - no facturar
        $empresa = $plan->empresa;
        if ($empresa && $empresa->slug === 'lioren') {
            $this->info("Suscripción #{$suscripcion->id} - Cuenta LIOREN (gratis) - renovando sin factura");
            $this->renovarCiclo($suscripcion);
            return;
        }

        $this->info("Procesando: {$user->name} ({$user->email}) - Plan: {$plan->nombre}");

        // Contar documentos emitidos en el ciclo
        $periodoInicio = $suscripcion->fecha_inicio;
        $periodoFin = $suscripcion->fecha_fin;

        $docsEmitidos = $this->contarDocumentosCiclo($user->id, $periodoInicio, $periodoFin);
        $limiteIncluido = $plan->monthly_order_limit ?? 0;
        $docsExtra = max(0, $docsEmitidos - $limiteIncluido);

        // Calcular montos
        $montoExtraUF = $docsExtra * self::EXTRA_DOC_PRICE_UF;
        $montoExtraCLP = round($montoExtraUF * $valorUF);
        $montoPlanCLP = round($plan->precio * $valorUF); // Plan price is in UF

        $montoNeto = $montoPlanCLP + $montoExtraCLP;
        $montoIVA = round($montoNeto * self::IVA_RATE);
        $montoTotal = $montoNeto + $montoIVA;

        $this->info("  Docs emitidos: {$docsEmitidos} / Límite: {$limiteIncluido} / Extra: {$docsExtra}");
        $this->info("  Plan: $" . number_format($montoPlanCLP, 0, ',', '.') . " + Extra: $" . number_format($montoExtraCLP, 0, ',', '.') . " + IVA: $" . number_format($montoIVA, 0, ',', '.') . " = Total: $" . number_format($montoTotal, 0, ',', '.'));

        // Generar concepto
        $concepto = "Plan {$plan->nombre} - Ciclo " . $periodoInicio->format('d/m/Y') . " al " . $periodoFin->format('d/m/Y');
        if ($docsExtra > 0) {
            $concepto .= " + {$docsExtra} docs extra";
        }

        // Verificar si ya existe factura para este ciclo
        $existingFactura = FacturaServicio::where('suscripcion_id', $suscripcion->id)
            ->where('periodo_inicio', $periodoInicio)
            ->where('periodo_fin', $periodoFin)
            ->first();

        if ($existingFactura) {
            $this->warn("  Factura ya existe para este ciclo (#{$existingFactura->id}) - omitida");
            return;
        }

        // Crear factura de servicio
        $factura = FacturaServicio::create([
            'user_id' => $user->id,
            'suscripcion_id' => $suscripcion->id,
            'plan_id' => $plan->id,
            'numero_factura' => 'FS-' . date('Ym') . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            'concepto' => $concepto,
            'documentos_incluidos' => $limiteIncluido,
            'documentos_emitidos' => $docsEmitidos,
            'documentos_extra' => $docsExtra,
            'precio_extra_uf' => self::EXTRA_DOC_PRICE_UF,
            'monto_extra_clp' => $montoExtraCLP,
            'monto_plan_clp' => $montoPlanCLP,
            'monto' => $montoTotal,
            'monto_neto' => $montoNeto,
            'monto_iva' => $montoIVA,
            'moneda' => 'CLP',
            'periodo_inicio' => $periodoInicio,
            'periodo_fin' => $periodoFin,
            'estado' => 'pendiente',
            'tipo' => 'ciclo',
            'valor_uf_usado' => $valorUF,
        ]);

        $this->info("  Factura creada: #{$factura->id} - {$factura->numero_factura}");

        // Emitir DTE real via Lioren (cuenta madre BigStudio/LIOREN)
        $this->emitirDTEFactura($factura, $user);

        // Pausar la suscripción hasta que se pague
        $suscripcion->update([
            'pausada' => true,
            'pausada_at' => now(),
            'motivo_pausa' => 'Factura pendiente de pago: ' . $factura->numero_factura,
        ]);

        $this->info("  Suscripción pausada hasta pago de factura");

        Log::info("Ciclo procesado", [
            'user_id' => $user->id,
            'factura_id' => $factura->id,
            'monto_total' => $montoTotal,
            'docs_extra' => $docsExtra,
        ]);
    }

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

    private function renovarCiclo(Suscripcion $suscripcion)
    {
        $nuevaFechaInicio = $suscripcion->fecha_fin;
        $nuevaFechaFin = $suscripcion->fecha_fin->copy()->addDays(30);

        $suscripcion->update([
            'fecha_inicio' => $nuevaFechaInicio,
            'fecha_fin' => $nuevaFechaFin,
            'proximo_pago' => $nuevaFechaFin,
        ]);
    }

    private function emitirDTEFactura(FacturaServicio $factura, $user)
    {
        try {
            $cliente = Cliente::where('user_id', $user->id)->first();

            if (!$cliente || !$cliente->rut) {
                Log::warning("No se puede emitir DTE: datos de facturación incompletos", [
                    'user_id' => $user->id,
                ]);
                $this->warn("  Sin datos de facturación - DTE no emitido");
                return;
            }

            // Obtener API key de la cuenta madre LIOREN/BigStudio
            $cuentaMadre = \App\Models\IntegracionConfig::whereHas('user', function ($q) {
                $q->where('role', 'admin');
            })->first();

            if (!$cuentaMadre || !$cuentaMadre->lioren_api_key) {
                Log::warning("No se encontró cuenta madre para emitir DTE de factura de servicio");
                $this->warn("  Sin cuenta madre configurada - DTE no emitido");
                return;
            }

            $apiKey = $cuentaMadre->lioren_api_key;

            // Emitir factura electrónica (tipo 33) via Lioren API
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(30)->post('https://www.lioren.cl/api/dte', [
                'tipo' => 33, // Factura electrónica
                'rut_receptor' => $cliente->rut,
                'razon_social' => $cliente->razon_social ?? $cliente->empresa,
                'giro' => $cliente->giro ?? 'Servicios',
                'direccion' => $cliente->direccion ?? 'Santiago',
                'items' => $this->buildInvoiceItems($factura),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $factura->update([
                    'lioren_factura_id' => $data['id'] ?? null,
                    'folio' => $data['folio'] ?? null,
                    'pdf_base64' => $data['pdf'] ?? null,
                ]);
                $this->info("  DTE emitido - Folio: " . ($data['folio'] ?? 'N/A'));
                Log::info("DTE factura servicio emitido", [
                    'factura_id' => $factura->id,
                    'folio' => $data['folio'] ?? null,
                ]);
            } else {
                Log::error("Error emitiendo DTE factura servicio", [
                    'factura_id' => $factura->id,
                    'response' => $response->body(),
                ]);
                $this->error("  Error emitiendo DTE: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Exception emitiendo DTE factura servicio: " . $e->getMessage());
            $this->error("  Exception DTE: " . $e->getMessage());
        }
    }

    private function buildInvoiceItems(FacturaServicio $factura): array
    {
        $items = [];

        // Item 1: Plan mensual
        $items[] = [
            'nombre' => 'Servicio Plan ' . ($factura->plan->nombre ?? 'N/A'),
            'cantidad' => 1,
            'precio' => $factura->monto_plan_clp,
        ];

        // Item 2: Documentos extra (si aplica)
        if ($factura->documentos_extra > 0) {
            $precioUnitarioExtra = $factura->monto_extra_clp > 0
                ? round($factura->monto_extra_clp / $factura->documentos_extra)
                : 0;

            $items[] = [
                'nombre' => 'Documentos adicionales (' . $factura->documentos_extra . ' docs)',
                'cantidad' => $factura->documentos_extra,
                'precio' => $precioUnitarioExtra,
            ];
        }

        return $items;
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
        return 39841.72; // Fallback
    }
}
