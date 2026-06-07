<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Solicitud;
use App\Models\Suscripcion;
use App\Models\Payment;
use App\Models\FacturaServicio;
use App\Models\PagoTransferencia;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ClienteController extends Controller
{
    /**
     * Dashboard del cliente
     */
    public function dashboard()
    {
        return view('cliente.dashboard');
    }

    /**
     * Obtener el valor actual de la UF
     * Prioridad: 1) Cache, 2) Base de datos (system_settings), 3) API mindicador.cl
     */
    private function obtenerValorUF(): ?float
    {
        return Cache::remember('valor_uf_actual', 60 * 60 * 6, function () {
            // 1. Intentar desde la base de datos (actualizada por cron diario)
            try {
                $dbValue = \DB::table('system_settings')->where('key', 'valor_uf')->value('value');
                if ($dbValue && (float) $dbValue > 0) {
                    return (float) $dbValue;
                }
            } catch (\Exception $e) {
                \Log::warning('No se pudo leer UF desde DB: ' . $e->getMessage());
            }

            // 2. Intentar desde la API como respaldo
            try {
                $response = Http::timeout(15)->get('https://mindicador.cl/api/uf');
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['serie'][0]['valor'])) {
                        $valor = (float) $data['serie'][0]['valor'];
                        // Actualizar DB para mantenerla al día
                        \DB::table('system_settings')->updateOrInsert(
                            ['key' => 'valor_uf'],
                            ['value' => (string) $valor, 'updated_at' => now()]
                        );
                        return $valor;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error al obtener valor UF desde API: ' . $e->getMessage());
            }

            return 39841.72; // Valor de respaldo
        });
    }

    /**
     * Planes disponibles
     */
    public function planes()
    {
        $planes = Plan::with('empresa')
            ->where('precio', '>', 0)
            ->where('activo', true)
            ->get();
        
        // Obtener valor UF actual
        $valorUF = $this->obtenerValorUF();
        
        // Convertir precios de UF a CLP para cada plan
        foreach ($planes as $plan) {
            if ($plan->moneda === 'UF' && $valorUF) {
                /* PARCHE_10_IVA_CLIENTE */ $plan->precio_clp = round($plan->precio * $valorUF * 1.19);
                $plan->precio_original_uf = $plan->precio;
                $plan->precio_anual_clp = ($plan->plan_anual_activo && $plan->precio_anual) ? round($plan->precio_anual * $valorUF) : null;
            } else {
                $plan->precio_clp = $plan->precio;
                $plan->precio_original_uf = null;
                $plan->precio_anual_clp = ($plan->plan_anual_activo && $plan->precio_anual) ? round($plan->precio_anual) : null;
            }
        }
        
        // Get the user's active subscription to highlight their current plan
        $suscripcionActiva = \App\Models\Suscripcion::where('user_id', auth()->id())
            ->where('estado', 'activa')
            ->first();
        $planActivoId = $suscripcionActiva ? $suscripcionActiva->plan_id : null;
        
        return view('cliente.planes', compact('planes', 'planActivoId', 'suscripcionActiva', 'valorUF'));
    }

    /**
     * Estados de solicitud
     */
    public function estadosSolicitud()
    {
        $user = auth()->user();
        
        $solicitudes = Solicitud::where('cliente_id', $user->id)
            ->with(['plan.empresa'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('cliente.solicitudes.estados', compact('solicitudes'));
    }

    /**
     * Planes activos con contador de documentos emitidos
     */
    public function planesActivos()
    {
        $user = auth()->user();
        
        $suscripcion = Suscripcion::where('user_id', $user->id)
            ->where('estado', 'activa')
            ->with('plan')
            ->first();
        
        $pagos = Payment::where('user_id', $user->id)
            ->whereNotNull('suscripcion_id')
            ->with('suscripcion.plan')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calcular documentos emitidos en el mes actual
        $documentosEmitidos = $this->calcularDocumentosEmitidos($user->id);
        
        $valorUF = $this->obtenerValorUF();
        return view('cliente.planes-activos', compact('suscripcion', 'pagos', 'documentosEmitidos', 'valorUF'));
    }

    /**
     * Mis facturas del servicio (facturas de BigStudio al cliente)
     */
    public function facturas()
    {
        $user = auth()->user();

        // Buscar en facturas_servicio
        $facturas = FacturaServicio::where('user_id', $user->id)
            ->with(['plan', 'suscripcion'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Si no hay facturas_servicio, generar desde payments existentes
        if ($facturas->total() === 0) {
            $this->generarFacturasDesdePayments($user->id);
            $facturas = FacturaServicio::where('user_id', $user->id)
                ->with(['plan', 'suscripcion'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        // Calculate real CLP amounts for each factura
        $valorUF = $this->obtenerValorUF();
        foreach ($facturas as $factura) {
            $monto = (float) $factura->monto;
            if ($factura->moneda === 'UF' || ($monto > 0 && $monto < 100)) {
                $totalCLP = round($monto * $valorUF);
            } else {
                $totalCLP = round($monto);
            }
            $factura->total_clp = $totalCLP;
            $factura->neto_clp = round($totalCLP / 1.19);
            $factura->iva_clp = $totalCLP - $factura->neto_clp;
        }

        return view('cliente.facturas', compact('facturas'));
    }

    /**
     * Calcular documentos emitidos en el mes actual
     */
    private function calcularDocumentosEmitidos(int $userId): array
    {
        // Usar ciclo de suscripción en vez de mes calendario
        $suscripcion = Suscripcion::where('user_id', $userId)
            ->where('estado', 'activa')
            ->first();
        
        if ($suscripcion && $suscripcion->fecha_inicio) {
            $inicioCiclo = $suscripcion->fecha_inicio;
            $finCiclo = $suscripcion->fecha_fin ?? $suscripcion->proximo_pago ?? now();
        } else {
            $inicioCiclo = now()->startOfMonth();
            $finCiclo = now()->endOfMonth();
        }
        
        // Contar boletas
        $boletas = DB::table('boletas')
            ->where('user_id', $userId)
            ->whereNotIn('tipodoc', [33, 61])
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->count();
        // Contar facturas (tipodoc 33 en boletas + facturas_emitidas)
        $facturas = DB::table('boletas')
            ->where('user_id', $userId)
            ->where('tipodoc', 33)
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->count();
        $facturas += DB::table('facturas_emitidas')
            ->where('user_id', $userId)
            ->where('tipo_documento', 33)
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->count();
        // Contar notas de crédito
        $notasCredito = DB::table('boletas')
            ->where('user_id', $userId)
            ->where('tipodoc', 61)
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->count();
        $notasCredito += DB::table('facturas_emitidas')
            ->where('user_id', $userId)
            ->where('tipo_documento', 61)
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->count();
        $notasCredito += DB::table('notas_credito')
            ->where('user_id', $userId)
            ->where('status', 'emitida')
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->count();
        return [
            'boletas' => $boletas,
            'facturas' => $facturas,
            'notas_credito' => $notasCredito,
            'total' => $boletas + $facturas + $notasCredito,
            'ciclo_inicio' => $inicioCiclo,
            'ciclo_fin' => $finCiclo,
        ];
    }

    /**
     * Generar facturas de servicio desde payments existentes
     */
    private function generarFacturasDesdePayments(int $userId): void
    {
        $payments = Payment::where('user_id', $userId)
            ->where('status', 2) // Pagado
            ->whereNotNull('suscripcion_id')
            ->get();

        foreach ($payments as $payment) {
            $existe = FacturaServicio::where('payment_id', $payment->id)->exists();
            if (!$existe) {
                FacturaServicio::create([
                    'user_id' => $userId,
                    'suscripcion_id' => $payment->suscripcion_id,
                    'plan_id' => $payment->suscripcion->plan_id ?? null,
                    'payment_id' => $payment->id,
                    'numero_factura' => 'FS-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                    'concepto' => $payment->subject ?? 'Suscripción',
                    'monto' => $payment->amount,
                    'moneda' => $payment->currency ?? 'CLP',
                    'periodo_inicio' => $payment->periodo_inicio,
                    'periodo_fin' => $payment->periodo_fin,
                    'estado' => 'pagada',
                ]);
            }
        }
    }

    /**
     * Procesar pago por transferencia - subir comprobante
     */
    public function pagoTransferencia(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:planes,id',
            'periodo' => 'required|in:mensual,anual',
            'comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        try {
            $user = auth()->user();
            $plan = Plan::findOrFail($request->plan_id);

            // Calcular monto según periodo
            $valorUF = $this->obtenerValorUF();
            if ($plan->moneda === 'UF' && $valorUF) {
                $monto = $request->periodo === 'anual' ? round($plan->precio_anual * $valorUF) : round($plan->precio * $valorUF);
            } else {
                $monto = $request->periodo === 'anual' ? (int)$plan->precio_anual : (int)$plan->precio;
            }

            // Guardar archivo
            $file = $request->file('comprobante');
            $filename = 'comprobante_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('comprobantes', $filename, 'public');

            // Crear registro
            $pago = PagoTransferencia::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'periodo' => $request->periodo,
                'monto' => $monto,
                'comprobante_path' => $path,
                'comprobante_original_name' => $file->getClientOriginalName(),
                'status' => 'pendiente',
            ]);

            Log::channel('single')->info('Pago por transferencia recibido', [
                'user_id' => $user->id,
                'plan' => $plan->nombre,
                'monto' => $monto,
                'periodo' => $request->periodo,
                'pago_transferencia_id' => $pago->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comprobante enviado exitosamente. Nuestro equipo verificar\u00e1 el pago.',
            ]);
        } catch (\Exception $e) {
            Log::channel('single')->error('Error al procesar pago por transferencia', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el comprobante: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Documentos Emitidos - Boletas, Facturas y Notas de Crédito del cliente
     */
    public function documentosEmitidos(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;

        // Boletas: id, tipodoc, folio, receptor_nombre, receptor_rut, monto_total, status, shopify_order_id, created_at
        // Facturas: id, tipo_documento, folio, razon_social, rut_receptor, monto_total, status, shopify_order_id, shopify_order_number, created_at

        $boletas = DB::table('boletas')
            ->where('user_id', $userId)
            ->select(
                'id',
                DB::raw("'boleta' as source"),
                'tipodoc',
                'folio',
                'receptor_nombre',
                'receptor_rut',
                'monto_total',
                'status',
                DB::raw("REGEXP_SUBSTR(observaciones, '[0-9]+$') as shopify_order_number"),
                'shopify_order_id',
                'created_at'
            );

        $facturas = DB::table('facturas_emitidas')
            ->where('user_id', $userId)
            ->select(
                'id',
                DB::raw("'factura' as source"),
                DB::raw("tipo_documento as tipodoc"),
                'folio',
                DB::raw("razon_social as receptor_nombre"),
                DB::raw("rut_receptor as receptor_rut"),
                'monto_total',
                'status',
                'shopify_order_number',
                'shopify_order_id',
                'created_at'
            );

        $notasCredito = DB::table('notas_credito')
            ->where('user_id', $userId)
            ->select(
                'id',
                DB::raw("'nota_credito' as source"),
                DB::raw("'61' as tipodoc"),
                'folio',
                DB::raw("razon_social as receptor_nombre"),
                DB::raw("rut_receptor as receptor_rut"),
                'monto_total',
                'status',
                'shopify_order_number',
                'shopify_order_id',
                'created_at'
            );

        // Union and order
        $sql = "SELECT * FROM ({$boletas->toSql()} UNION ALL {$facturas->toSql()} UNION ALL {$notasCredito->toSql()}) as docs ORDER BY created_at DESC";
        $bindings = array_merge($boletas->getBindings(), $facturas->getBindings(), $notasCredito->getBindings());
        $documentos = collect(DB::select($sql, $bindings));

        // Filtro por tipo
        if ($request->filled('tipo')) {
            $tipo = $request->tipo;
            if ($tipo === 'boleta') {
                $documentos = $documentos->filter(fn($d) => $d->tipodoc == 39);
            } elseif ($tipo === 'factura') {
                $documentos = $documentos->filter(fn($d) => $d->tipodoc == 33);
            } elseif ($tipo === 'nota_credito') {
                $documentos = $documentos->filter(fn($d) => $d->tipodoc == 61);
            }
        }

        // Filtro por mes
        if ($request->filled('mes')) {
            $mes = $request->mes;
            $documentos = $documentos->filter(fn($d) => substr($d->created_at, 0, 7) === $mes);
        }

        // Obtener suscripción activa para info de ciclo
        $suscripcion = Suscripcion::where('user_id', $userId)
            ->where('estado', 'activa')
            ->with('plan')
            ->first();
        
        // Determinar fechas del ciclo
        if ($suscripcion && $suscripcion->fecha_inicio) {
            $inicioCiclo = $suscripcion->fecha_inicio;
            $finCiclo = $suscripcion->fecha_fin ?? $suscripcion->proximo_pago ?? now();
        } else {
            $inicioCiclo = now()->startOfMonth();
            $finCiclo = now()->endOfMonth();
        }
        
        // Contar documentos emitidos en el ciclo actual (solo status emitida)
        $docsCicloBoletas = DB::table('boletas')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->where('status', 'emitida')
            ->count();
        $docsCicloFacturas = DB::table('facturas_emitidas')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->where('status', 'emitida')
            ->count();
        $docsCicloTotal = $docsCicloBoletas + $docsCicloFacturas;
        
        $limiteCiclo = ($suscripcion && $suscripcion->plan) ? $suscripcion->plan->monthly_order_limit : null;
        
        $cicloInfo = [
            'inicio' => $inicioCiclo,
            'fin' => $finCiclo,
            'emitidos' => $docsCicloTotal,
            'limite' => $limiteCiclo,
            'disponibles' => $limiteCiclo ? max(0, $limiteCiclo - $docsCicloTotal) : null,
            'porcentaje' => $limiteCiclo > 0 ? min(100, round(($docsCicloTotal / $limiteCiclo) * 100)) : 0,
            'plan' => ($suscripcion && $suscripcion->plan) ? $suscripcion->plan->nombre : null,
        ];
        
        // Estadísticas (del listado filtrado)
        $stats = [
            'total' => $documentos->count(),
            'boletas' => $documentos->filter(fn($d) => $d->tipodoc == 39)->count(),
            'facturas' => $documentos->filter(fn($d) => $d->tipodoc == 33)->count(),
            'notas_credito' => $documentos->filter(fn($d) => $d->tipodoc == 61)->count(),
            'monto_total' => $documentos->sum('monto_total'),
        ];
        return view('cliente.documentos-emitidos', compact('documentos', 'stats', 'cicloInfo'));
    }

    /**
     * Descargar PDF de un documento (boleta o factura)
     */
    public function documentoPdf($tipo, $id)
    {
        $userId = auth()->id();
        
        if ($tipo === 'boleta') {
            $doc = Boleta::where('id', $id)->where('user_id', $userId)->firstOrFail();
            
            if ($doc->pdf_path && \Storage::exists($doc->pdf_path)) {
                return response(\Storage::get($doc->pdf_path))
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=boleta_{$doc->folio}.pdf");
            }
            if ($doc->pdf_base64) {
                $pdf = base64_decode($doc->pdf_base64);
                return response($pdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=boleta_{$doc->folio}.pdf");
            }
        } elseif ($tipo === 'factura') {
            $factura = FacturaEmitida::where('id', $id)->where('user_id', $userId)->firstOrFail();
            
            if ($factura->pdf_path && \Storage::exists($factura->pdf_path)) {
                return response(\Storage::get($factura->pdf_path))
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=factura_{$factura->folio}.pdf");
            }
            if ($factura->pdf_base64) {
                $pdf = base64_decode($factura->pdf_base64);
                return response($pdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=factura_{$factura->folio}.pdf");
            }
        } elseif ($tipo === 'nota_credito') {
            $nc = \App\Models\NotaCredito::where('id', $id)->where('user_id', $userId)->firstOrFail();
            
            if ($nc->pdf_path && \Storage::exists($nc->pdf_path)) {
                return response(\Storage::get($nc->pdf_path))
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=nc_{$nc->folio}.pdf");
            }
            if ($nc->pdf_base64) {
                $pdf = base64_decode($nc->pdf_base64);
                return response($pdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=nc_{$nc->folio}.pdf");
            }
        }
        
        abort(404, 'PDF no disponible');
    }

    /**
     * Descargar XML de un documento (boleta o factura)
     */
    public function documentoXml($tipo, $id)
    {
        $userId = auth()->id();
        
        if ($tipo === 'boleta') {
            $doc = Boleta::where('id', $id)->where('user_id', $userId)->firstOrFail();
            
            if ($doc->xml_base64) {
                $xml = base64_decode($doc->xml_base64);
                return response($xml)
                    ->header('Content-Type', 'application/xml')
                    ->header('Content-Disposition', "attachment; filename=boleta_{$doc->folio}.xml");
            }
        } elseif ($tipo === 'factura') {
            $factura = FacturaEmitida::where('id', $id)->where('user_id', $userId)->firstOrFail();
            
            if ($factura->xml_path && \Storage::exists($factura->xml_path)) {
                return response(\Storage::get($factura->xml_path))
                    ->header('Content-Type', 'application/xml')
                    ->header('Content-Disposition', "attachment; filename=factura_{$factura->folio}.xml");
            }
            if ($factura->xml_base64) {
                $xml = base64_decode($factura->xml_base64);
                return response($xml)
                    ->header('Content-Type', 'application/xml')
                    ->header('Content-Disposition', "attachment; filename=factura_{$factura->folio}.xml");
            }
        } elseif ($tipo === 'nota_credito') {
            $nc = \App\Models\NotaCredito::where('id', $id)->where('user_id', $userId)->firstOrFail();
            
            if ($nc->xml_base64) {
                $xml = base64_decode($nc->xml_base64);
                return response($xml)
                    ->header('Content-Type', 'application/xml')
                    ->header('Content-Disposition', "attachment; filename=nc_{$nc->folio}.xml");
            }
        }
        
        abort(404, 'XML no disponible');
    }
}
