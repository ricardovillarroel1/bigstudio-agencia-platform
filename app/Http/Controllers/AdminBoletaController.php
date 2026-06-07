<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use App\Models\IntegracionConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminBoletaController extends Controller
{
    /**
     * Mostrar todos los documentos emitidos (boletas, facturas, notas de crédito)
     */
    public function index(Request $request)
    {
        // Obtener clientes con suscripción activa
        $clientes = User::whereHas('suscripciones', function ($q) {
            $q->where('estado', 'activa');
        })->orWhereHas('integracionConfig')->orderBy('name')->get();

        // Construir query unificada de boletas + facturas_emitidas + notas_credito
        $boletasQuery = DB::table('boletas')
            ->select(
                'boletas.id',
                'boletas.user_id',
                'boletas.created_at',
                'boletas.tipodoc',
                DB::raw("CASE WHEN boletas.tipodoc = 33 THEN 'factura' WHEN boletas.tipodoc = 61 THEN 'nota_credito' ELSE 'boleta' END as tipo"),
                'boletas.folio',
                'boletas.shopify_order_id',
                'boletas.observaciones',
                DB::raw("NULL as shopify_order_number"),
                'boletas.monto_total',
                'boletas.status',
                'boletas.receptor_nombre',
                'boletas.receptor_rut',
                DB::raw("NULL as rut_receptor"),
                DB::raw("NULL as razon_social"),
                DB::raw("'boleta' as source")
            );

        $facturasQuery = DB::table('facturas_emitidas')
            ->select(
                'facturas_emitidas.id',
                'facturas_emitidas.user_id',
                'facturas_emitidas.created_at',
                'facturas_emitidas.tipo_documento as tipodoc',
                DB::raw("CASE WHEN facturas_emitidas.tipo_documento = 33 THEN 'factura' WHEN facturas_emitidas.tipo_documento = 61 THEN 'nota_credito' ELSE 'boleta' END as tipo"),
                'facturas_emitidas.folio',
                'facturas_emitidas.shopify_order_id',
                DB::raw("NULL as observaciones"),
                'facturas_emitidas.shopify_order_number',
                'facturas_emitidas.monto_total',
                'facturas_emitidas.status',
                DB::raw("NULL as receptor_nombre"),
                DB::raw("NULL as receptor_rut"),
                'facturas_emitidas.rut_receptor',
                'facturas_emitidas.razon_social',
                DB::raw("'factura_emitida' as source")
            );

        $notasCreditoQuery = DB::table('notas_credito')
            ->where('notas_credito.status', 'emitida')
            ->select(
                'notas_credito.id',
                'notas_credito.user_id',
                'notas_credito.created_at',
                DB::raw("61 as tipodoc"),
                DB::raw("'nota_credito' as tipo"),
                'notas_credito.folio',
                'notas_credito.shopify_order_id',
                DB::raw("NULL as observaciones"),
                'notas_credito.shopify_order_number',
                'notas_credito.monto_total',
                'notas_credito.status',
                DB::raw("NULL as receptor_nombre"),
                DB::raw("NULL as receptor_rut"),
                'notas_credito.rut_receptor',
                'notas_credito.razon_social',
                DB::raw("'nota_credito' as source")
            );

        // Aplicar filtros
        if ($request->filled('user_id')) {
            $boletasQuery->where('boletas.user_id', $request->user_id);
            $facturasQuery->where('facturas_emitidas.user_id', $request->user_id);
            $notasCreditoQuery->where('notas_credito.user_id', $request->user_id);
        }

        if ($request->filled('tipo')) {
            if ($request->tipo === 'boleta') {
                $boletasQuery->whereNotIn('boletas.tipodoc', [33, 61]);
                $facturasQuery->whereNotIn('facturas_emitidas.tipo_documento', [33, 61]);
                $notasCreditoQuery->whereRaw('1 = 0');
            } elseif ($request->tipo === 'factura') {
                $boletasQuery->where('boletas.tipodoc', 33);
                $facturasQuery->where('facturas_emitidas.tipo_documento', 33);
                $notasCreditoQuery->whereRaw('1 = 0');
            } elseif ($request->tipo === 'nota_credito') {
                $boletasQuery->where('boletas.tipodoc', 61);
                $facturasQuery->where('facturas_emitidas.tipo_documento', 61);
            }
        }

        if ($request->filled('mes')) {
            $year = substr($request->mes, 0, 4);
            $month = substr($request->mes, 5, 2);
            $boletasQuery->whereYear('boletas.created_at', $year)->whereMonth('boletas.created_at', $month);
            $facturasQuery->whereYear('facturas_emitidas.created_at', $year)->whereMonth('facturas_emitidas.created_at', $month);
            $notasCreditoQuery->whereYear('notas_credito.created_at', $year)->whereMonth('notas_credito.created_at', $month);
        }

        // Unir y obtener (boletas + facturas + notas de credito)
        $documentosRaw = $boletasQuery->unionAll($facturasQuery)->unionAll($notasCreditoQuery)
            ->orderBy('created_at', 'desc')
            ->get();

        // Cargar usuarios manualmente
        $userIds = $documentosRaw->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->with('integracionConfig')->get()->keyBy('id');

        // Agregar relación user a cada documento
        $documentosRaw->transform(function ($doc) use ($users) {
            $doc->user = $users->get($doc->user_id);
            return $doc;
        });

        // Paginar manualmente
        $page = $request->get('page', 1);
        $perPage = 20;
        $total = $documentosRaw->count();
        $documentos = new \Illuminate\Pagination\LengthAwarePaginator(
            $documentosRaw->forPage($page, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Estadísticas
        $estadisticas = [
            'total' => DB::table('boletas')->count() + DB::table('facturas_emitidas')->count() + DB::table('notas_credito')->where('status', 'emitida')->count(),
            'boletas' => DB::table('boletas')->whereNotIn('tipodoc', [33, 61])->count(),
            'facturas' => DB::table('boletas')->where('tipodoc', 33)->count() + DB::table('facturas_emitidas')->where('tipo_documento', 33)->count(),
            'notas_credito' => DB::table('boletas')->where('tipodoc', 61)->count() + DB::table('facturas_emitidas')->where('tipo_documento', 61)->count() + DB::table('notas_credito')->where('status', 'emitida')->count(),
        ];

        // Per-client document stats with plan limits
        $clienteStats = [];
        foreach ($clientes as $cliente) {
            $suscripcion = \App\Models\Suscripcion::with('plan')
                ->where('user_id', $cliente->id)
                ->where('estado', 'activa')
                ->first();

            $limiteDocumentos = null;
            $planNombre = 'Sin plan';
            
            if ($suscripcion && $suscripcion->plan) {
                $planNombre = $suscripcion->plan->nombre;
                $limiteDocumentos = $suscripcion->plan->monthly_order_limit;
                $inicioCiclo = $suscripcion->fecha_inicio;
                $finCiclo = $suscripcion->fecha_fin ?? $suscripcion->proximo_pago ?? now();
            } else {
                $inicioCiclo = now()->startOfMonth();
                $finCiclo = now()->endOfMonth();
            }
            
            $docsEmitidosBoletas = Boleta::where('user_id', $cliente->id)
                ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
                ->where('status', 'emitida')
                ->count();
            $docsEmitidosFacturas = FacturaEmitida::where('user_id', $cliente->id)
                ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
                ->where('status', 'emitida')
                ->count();
            $docsEmitidos = $docsEmitidosBoletas + $docsEmitidosFacturas;
            
            $docsTotalBoletas = Boleta::where('user_id', $cliente->id)->count();
            $docsTotalFacturas = FacturaEmitida::where('user_id', $cliente->id)->count();
            
            $clienteStats[] = [
                'id' => $cliente->id,
                'name' => $cliente->name,
                'email' => $cliente->email,
                'plan' => $planNombre,
                'docs_emitidos' => $docsEmitidos,
                'docs_total' => $docsTotalBoletas + $docsTotalFacturas,
                'limite' => $limiteDocumentos,
                'disponibles' => $limiteDocumentos ? max(0, $limiteDocumentos - $docsEmitidos) : null,
                'ciclo_inicio' => $inicioCiclo ?? null,
                'ciclo_fin' => $finCiclo ?? null,
            ];
        }

        return view('integracion.boletas', compact('documentos', 'estadisticas', 'clientes', 'clienteStats'));
    }

    /**
     * Re-emitir un documento con estado Error
     * Soporta boletas (tabla boletas) y facturas (tabla facturas_emitidas)
     */
    public function reemitir(Request $request, $source, $id)
    {
        try {
            if ($source === 'boleta') {
                return $this->reemitirBoleta($id);
            } elseif ($source === 'factura_emitida') {
                return $this->reemitirFactura($id);
            } else {
                return back()->with('error', 'Tipo de documento no soportado para re-emisión.');
            }
        } catch (\Exception $e) {
            Log::error("Error al re-emitir documento {$source}#{$id}: " . $e->getMessage());
            return back()->with('error', 'Error al re-emitir: ' . $e->getMessage());
        }
    }

    /**
     * Re-emitir una boleta con error
     */
    private function reemitirBoleta($id)
    {
        $boleta = Boleta::findOrFail($id);

        if (!in_array($boleta->status, ['error', 'pendiente'])) {
            return back()->with('error', "La boleta #{$id} no está en estado Error/Pendiente (estado actual: {$boleta->status}).");
        }

        // Obtener config del cliente
        $config = IntegracionConfig::where('user_id', $boleta->user_id)->first();
        if (!$config || !$config->lioren_api_key) {
            return back()->with('error', "No se encontró la configuración de Lioren para el usuario #{$boleta->user_id}.");
        }

        // Obtener detalles guardados
        $detalles = $boleta->detalles;
        if (is_string($detalles)) {
            $detalles = json_decode($detalles, true);
        }
        if (empty($detalles)) {
            return back()->with('error', "La boleta #{$id} no tiene detalles guardados para re-emitir.");
        }

        // Determinar tipo de documento
        $tipodoc = $boleta->tipodoc ?: '39';

        // Es factura (33/34) o boleta (39)
        $esFactura = in_array($tipodoc, ['33', '34', 33, 34]);
        $obsReem = $boleta->observaciones ?? 'Re-emisión manual';

        if ($esFactura) {
            // Receptor para factura.
            $receptorReem = [];
            if ($boleta->receptor_rut) {
                $receptorReem = array_filter([
                    'rut' => $boleta->receptor_rut,
                    'rs' => $boleta->receptor_nombre ?: 'Cliente',
                    'giro' => 'Comercio',
                    'comuna' => 316,
                    'ciudad' => 15,
                    'direccion' => 'Sin dirección',
                    'email' => $boleta->receptor_email,
                ]);
            }
        } else {
            // Receptor para boleta.
            $receptorReem = [];
            if ($boleta->receptor_rut || $boleta->receptor_nombre) {
                $receptorReem = array_filter([
                    'rut' => $boleta->receptor_rut,
                    'rs' => $boleta->receptor_nombre ?: 'Cliente',
                    'email' => $boleta->receptor_email,
                ]);
            }
        }

        $tipoLabel = $esFactura ? 'factura' : 'boleta';
        Log::info("Re-emitiendo {$tipoLabel} #{$id} a Lioren", [
            'detalles_count' => count($detalles),
            'user_id' => $boleta->user_id,
        ]);

        // Emisión vía LiorenService (punto ÚNICO de comunicación con Lioren).
        $lioren = app(\App\Services\LiorenService::class);
        if ($esFactura) {
            $result = $lioren->emitirFactura($config->lioren_api_key, $detalles, $receptorReem, $obsReem, ['tipodoc' => (string)$tipodoc]);
        } else {
            $result = $lioren->emitirBoleta($config->lioren_api_key, $detalles, $receptorReem, $obsReem);
        }

        if (!$result['ok']) {
            $boleta->update([
                'error_message' => 'Re-emisión fallida: ' . $result['error'],
                'last_retry_at' => now(),
                'retry_count' => ($boleta->retry_count ?? 0) + 1,
            ]);
            return back()->with('error', "Error de Lioren al re-emitir boleta #{$id}: " . $result['error']);
        }

        // Actualizar boleta en BD
        $boleta->update([
            'lioren_id' => $result['id'] ?? $boleta->lioren_id,
            'folio' => $result['folio'] ?? $boleta->folio,
            'fecha' => $result['fecha'] ?? now()->format('Y-m-d'),
            'monto_neto' => $result['montoneto'] ?? $boleta->monto_neto,
            'monto_exento' => $result['montoexento'] ?? $boleta->monto_exento,
            'monto_iva' => $result['montoiva'] ?? $boleta->monto_iva,
            'monto_total' => $result['montototal'] ?? $boleta->monto_total,
            'status' => 'emitida',
            'error_message' => null,
            'last_retry_at' => now(),
            'retry_count' => ($boleta->retry_count ?? 0) + 1,
        ]);

        // Guardar PDF y XML
        if (isset($result['pdf'])) {
            $boleta->pdf_path = $boleta->savePdfFromBase64($result['pdf']);
            $boleta->pdf_base64 = $result['pdf'];
        }
        if (isset($result['xml'])) {
            $boleta->xml_path = $boleta->saveXmlFromBase64($result['xml']);
            $boleta->xml_base64 = $result['xml'];
        }
        $boleta->save();

        // Actualizar Shopify si hay order_id
        $tipoNombre = in_array($tipodoc, ['33', '34', 33, 34]) ? 'Factura' : 'Boleta';
        $this->actualizarShopify($config, $boleta->shopify_order_id, $result['folio'] ?? '', $tipoNombre);

        $folio = $result['folio'] ?? 'N/A';
        Log::info("✅ Re-emisión exitosa: {$tipoNombre} #{$folio} (boleta BD #{$id})");

        return back()->with('success', "✅ {$tipoNombre} #{$folio} re-emitida exitosamente.");
    }

    /**
     * Re-emitir una factura con error (tabla facturas_emitidas)
     */
    private function reemitirFactura($id)
    {
        $factura = FacturaEmitida::findOrFail($id);

        if (!in_array($factura->status, ['error', 'pendiente'])) {
            return back()->with('error', "La factura #{$id} no está en estado Error/Pendiente (estado actual: {$factura->status}).");
        }

        // Obtener config del cliente
        $config = IntegracionConfig::where('user_id', $factura->user_id)->first();
        if (!$config || !$config->lioren_api_key) {
            return back()->with('error', "No se encontró la configuración de Lioren para el usuario #{$factura->user_id}.");
        }

        // Obtener detalles guardados
        $detalles = $factura->detalles;
        if (is_string($detalles)) {
            $detalles = json_decode($detalles, true);
        }
        if (empty($detalles)) {
            return back()->with('error', "La factura #{$id} no tiene detalles guardados para re-emitir.");
        }

        // Receptor para re-emisión.
        $receptorReem = array_filter([
            'rut' => $factura->rut_receptor,
            'rs' => $factura->razon_social ?: 'Cliente',
            'giro' => $factura->giro ?: 'Comercio',
            'comuna' => $factura->comuna_id ?: 316,
            'ciudad' => $factura->ciudad_id ?: 15,
            'direccion' => $factura->direccion ?: 'Sin dirección',
            'email' => $factura->receptor_email,
        ]);

        Log::info("Re-emitiendo factura #{$id} a Lioren", [
            'rut' => $factura->rut_receptor,
            'detalles_count' => count($detalles),
            'user_id' => $factura->user_id,
        ]);

        // Emisión vía LiorenService (punto ÚNICO de comunicación con Lioren).
        $result = app(\App\Services\LiorenService::class)->emitirFactura(
            $config->lioren_api_key,
            $detalles,
            $receptorReem,
            'Re-emisión - Pedido Shopify #' . ($factura->shopify_order_number ?? $factura->shopify_order_id),
            ['tipodoc' => (string)($factura->tipo_documento ?: '33')]
        );

        if (!$result['ok']) {
            $factura->update([
                'error_message' => 'Re-emisión fallida: ' . $result['error'],
                'last_retry_at' => now(),
                'retry_count' => ($factura->retry_count ?? 0) + 1,
            ]);
            return back()->with('error', "Error de Lioren al re-emitir factura #{$id}: " . $result['error']);
        }

        // Actualizar factura en BD
        $factura->update([
            'lioren_factura_id' => $result['id'] ?? $factura->lioren_factura_id,
            'folio' => $result['folio'] ?? $factura->folio,
            'monto_neto' => $result['montoneto'] ?? $factura->monto_neto,
            'monto_iva' => $result['montoiva'] ?? $factura->monto_iva,
            'monto_total' => $result['montototal'] ?? $factura->monto_total,
            'status' => 'emitida',
            'emitida_at' => now(),
            'error_message' => null,
            'last_retry_at' => now(),
            'retry_count' => ($factura->retry_count ?? 0) + 1,
        ]);

        // Guardar PDF y XML
        if (isset($result['pdf'])) {
            $pdfContent = base64_decode($result['pdf']);
            $pdfPath = "facturas/factura_{$factura->id}_{$result['folio']}.pdf";
            Storage::put($pdfPath, $pdfContent);
            $factura->pdf_path = $pdfPath;
            $factura->pdf_base64 = $result['pdf'];
        }
        if (isset($result['xml'])) {
            $xmlContent = base64_decode($result['xml']);
            $xmlPath = "facturas/factura_{$factura->id}_{$result['folio']}.xml";
            Storage::put($xmlPath, $xmlContent);
            $factura->xml_path = $xmlPath;
            $factura->xml_base64 = $result['xml'];
        }
        $factura->save();

        // Actualizar Shopify
        $this->actualizarShopify($config, $factura->shopify_order_id, $result['folio'] ?? '', 'Factura');

        $folio = $result['folio'] ?? 'N/A';
        Log::info("✅ Re-emisión exitosa: Factura #{$folio} (factura BD #{$id})");

        return back()->with('success', "✅ Factura #{$folio} re-emitida exitosamente.");
    }

    /**
     * Actualizar nota y tags en Shopify después de re-emitir
     */
    private function actualizarShopify($config, $shopifyOrderId, $folio, $tipoNombre)
    {
        if (!$shopifyOrderId || !$config->shopify_token || !$config->shopify_tienda) {
            return;
        }

        try {
            $shopUrl = $config->shopify_tienda;
            $token = $config->shopify_token;
            $apiVersion = '2024-01';

            // Obtener pedido actual para el order_number
            $orderResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type' => 'application/json',
            ])->get("https://{$shopUrl}/admin/api/{$apiVersion}/orders/{$shopifyOrderId}.json");

            if (!$orderResponse->successful()) return;

            $order = $orderResponse->json()['order'] ?? [];
            $orderNumber = $order['order_number'] ?? $shopifyOrderId;
            $existingTags = $order['tags'] ?? '';
            $existingNote = $order['note'] ?? '';

            // Agregar nota
            $nuevaNota = "{$tipoNombre} Lioren #{$folio}";
            if (!str_contains($existingNote, $nuevaNota)) {
                $notaFinal = trim($existingNote . "\n" . $nuevaNota);
                Http::withHeaders([
                    'X-Shopify-Access-Token' => $token,
                    'Content-Type' => 'application/json',
                ])->put("https://{$shopUrl}/admin/api/{$apiVersion}/orders/{$shopifyOrderId}.json", [
                    'order' => ['id' => $shopifyOrderId, 'note' => $notaFinal]
                ]);
            }

            // Agregar tag
            $nuevoTag = "{$tipoNombre}-Lioren-#{$folio}";
            if (!str_contains($existingTags, $nuevoTag)) {
                $tagsFinal = trim($existingTags . ', ' . $nuevoTag, ', ');
                Http::withHeaders([
                    'X-Shopify-Access-Token' => $token,
                    'Content-Type' => 'application/json',
                ])->put("https://{$shopUrl}/admin/api/{$apiVersion}/orders/{$shopifyOrderId}.json", [
                    'order' => ['id' => $shopifyOrderId, 'tags' => $tagsFinal]
                ]);
            }

            Log::info("Shopify actualizado: Pedido #{$orderNumber} - {$tipoNombre} #{$folio}");
        } catch (\Exception $e) {
            Log::warning("No se pudo actualizar Shopify para orden {$shopifyOrderId}: " . $e->getMessage());
        }
    }

    /**
     * Descargar PDF de factura emitida
     */
    public function facturaPdf($id)
    {
        $factura = FacturaEmitida::findOrFail($id);

        if ($factura->pdf_path && Storage::exists($factura->pdf_path)) {
            return response(Storage::get($factura->pdf_path))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=factura_{$factura->folio}.pdf");
        }

        if ($factura->pdf_base64) {
            $pdf = base64_decode($factura->pdf_base64);
            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=factura_{$factura->folio}.pdf");
        }

        abort(404, 'PDF no disponible');
    }

    /**
     * Descargar XML de factura emitida
     */
    public function facturaXml($id)
    {
        $factura = FacturaEmitida::findOrFail($id);

        if ($factura->xml_path && Storage::exists($factura->xml_path)) {
            return response(Storage::get($factura->xml_path))
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', "attachment; filename=factura_{$factura->folio}.xml");
        }

        if ($factura->xml_base64) {
            $xml = base64_decode($factura->xml_base64);
            return response($xml)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', "attachment; filename=factura_{$factura->folio}.xml");
        }

        abort(404, 'XML no disponible');
    }
}
