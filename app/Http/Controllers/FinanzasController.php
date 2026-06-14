<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class FinanzasController extends Controller
{
    /**
     * Get admin user IDs (Big Studio's own documents)
     */
    private function getAdminUserIds()
    {
        return \App\Models\User::role('admin')->pluck('id')->toArray();
    }

    /**
     * Ingresos consolidados de un rango (CLP), desglosados por origen.
     * FIX: la fuente SaaS es facturas_servicio (CLP), NO payments (que viene en UF y corrompe el total).
     * Anti-doble-conteo: Agencia=agencia_cobros, SaaS=facturas_servicio (no payments), DTE propios=admin (menos NC).
     */
    private function ingresosRango($desde, $hasta, $adminIds): array
    {
        $desdeD = Carbon::parse($desde)->toDateString();
        $hastaD = Carbon::parse($hasta)->toDateString();

        $boletas = (float) DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
            ->where('tipodoc', '!=', 61)->whereBetween('created_at', [$desde, $hasta])->sum('monto_total');
        $notasCredito = (float) DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
            ->where('tipodoc', 61)->whereBetween('created_at', [$desde, $hasta])->sum('monto_total');
        $facturas = (float) DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
            ->whereBetween('created_at', [$desde, $hasta])->sum('monto_total');
        $dtePropios = $boletas + $facturas - $notasCredito;

        $agencia = (float) DB::table('agencia_cobros')->where('estado', 'pagado')
            ->whereBetween('pagado_at', [$desde, $hasta])->sum('monto');

        // SaaS en CLP. Fecha de devengo = COALESCE(pagada_at, periodo_inicio, created_at) — pagada_at está incompleta.
        $saas = (float) DB::table('facturas_servicio')->where('estado', 'pagada')
            ->whereRaw('COALESCE(pagada_at, periodo_inicio, created_at) BETWEEN ? AND ?', [$desde, $hasta])
            ->sum('monto');

        $manuales = (float) DB::table('ingresos_manuales')
            ->whereBetween('fecha', [$desdeD, $hastaD])->sum('monto_total');

        return [
            'dte_propios' => (int) round($dtePropios),
            'agencia'     => (int) round($agencia),
            'saas'        => (int) round($saas),
            'manuales'    => (int) round($manuales),
            'nc'          => (int) round($notasCredito),
            'total'       => (int) round($dtePropios + $agencia + $saas + $manuales),
        ];
    }

    /** Egresos de un rango (CLP). gastos_operativos son recurrentes mensuales (se cuentan completos en cada mes). */
    private function egresosRango($desde, $hasta): array
    {
        $desdeD = Carbon::parse($desde)->toDateString();
        $hastaD = Carbon::parse($hasta)->toDateString();
        $facturasCompra = (float) DB::table('facturas_compra')->whereIn('estado', ['pendiente', 'pagada'])
            ->whereBetween('fecha_emision', [$desdeD, $hastaD])->sum('monto_total');
        $gastosOp = (float) DB::table('gastos_operativos')->where('activo', true)->sum('monto');
        return [
            'facturas_compra' => (int) round($facturasCompra),
            'gastos_op'       => (int) round($gastosOp),
            'total'           => (int) round($facturasCompra + $gastosOp),
        ];
    }

    /** IVA débito consolidado de un rango (incluye SaaS desde facturas_servicio). */
    private function ivaDebitoRango($desde, $hasta, $adminIds): int
    {
        $desdeD = Carbon::parse($desde)->toDateString();
        $hastaD = Carbon::parse($hasta)->toDateString();
        $iva = (float) DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
            ->whereBetween('created_at', [$desde, $hasta])->sum('monto_iva');
        $iva += (float) DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
            ->whereBetween('created_at', [$desde, $hasta])->sum('monto_iva');
        $iva += (float) DB::table('agencia_cobros')->where('estado', 'pagado')->where('factura_estado', 'emitida')
            ->whereBetween('pagado_at', [$desde, $hasta])->sum(DB::raw('ROUND(monto * 0.19 / 1.19)'));
        $iva += (float) DB::table('facturas_servicio')->where('estado', 'pagada')
            ->whereRaw('COALESCE(pagada_at, periodo_inicio, created_at) BETWEEN ? AND ?', [$desde, $hasta])->sum('monto_iva');
        $iva += (float) DB::table('ingresos_manuales')->whereBetween('fecha', [$desdeD, $hastaD])->sum('monto_iva');
        return (int) round($iva);
    }

    // ==========================================
    // DASHBOARD
    // ==========================================
    public function dashboard(Request $request)
    {
        $mes = (int) $request->get('mes', now()->month);
        $anio = (int) $request->get('anio', now()->year);

        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();
        $adminIds = $this->getAdminUserIds();

        // ===== Mes actual (consolidado) =====
        $ing = $this->ingresosRango($inicioMes, $finMes, $adminIds);
        $egr = $this->egresosRango($inicioMes, $finMes);
        $totalIngresos = $ing['total'];
        $totalEgresos = $egr['total'];
        $utilidad = $totalIngresos - $totalEgresos;
        $margen = $totalIngresos > 0 ? round($utilidad / $totalIngresos * 100, 1) : 0;
        $egresoFacturasCompra = $egr['facturas_compra'];
        $egresoGastosOp = $egr['gastos_op'];

        // ===== Mes anterior (comparativa) =====
        $iniPrev = $inicioMes->copy()->subMonthNoOverflow()->startOfMonth();
        $finPrev = $inicioMes->copy()->subMonthNoOverflow()->endOfMonth();
        $ingPrev = $this->ingresosRango($iniPrev, $finPrev, $adminIds)['total'];
        $egrPrev = $this->egresosRango($iniPrev, $finPrev)['total'];
        $utilPrev = $ingPrev - $egrPrev;
        $varPct = function ($actual, $previo) {
            if ($previo == 0) return null;
            return round(($actual - $previo) / abs($previo) * 100, 1);
        };
        $comparativa = [
            'ingresos' => ['previo' => $ingPrev, 'var' => $varPct($totalIngresos, $ingPrev)],
            'egresos'  => ['previo' => $egrPrev, 'var' => $varPct($totalEgresos, $egrPrev)],
            'utilidad' => ['previo' => $utilPrev, 'var' => $varPct($utilidad, $utilPrev)],
        ];

        // ===== IVA =====
        $totalIvaDebito = $this->ivaDebitoRango($inicioMes, $finMes, $adminIds);
        $ivaCredito = (int) round(DB::table('facturas_compra')->whereIn('estado', ['pendiente', 'pagada'])
            ->whereBetween('fecha_emision', [$inicioMes->toDateString(), $finMes->toDateString()])->sum('monto_iva'));
        $remanente = 0;
        $ivaPrevio = DB::table('iva_mensual')
            ->where('anio', $mes == 1 ? $anio - 1 : $anio)
            ->where('mes', $mes == 1 ? 12 : $mes - 1)->first();
        if ($ivaPrevio) {
            $remanente = $ivaPrevio->remanente_siguiente;
        }
        $ivaPagar = max(0, $totalIvaDebito - $ivaCredito - $remanente);
        $remanenteSiguiente = max(0, ($ivaCredito + $remanente) - $totalIvaDebito);

        // ===== Tendencia 12 meses (consolidada, ingresos y egresos consistentes con el KPI) =====
        $tendencia = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonthsNoOverflow($i);
            $mInicio = $m->copy()->startOfMonth();
            $mFin = $m->copy()->endOfMonth();
            $tendencia[] = [
                'mes' => ucfirst($m->translatedFormat('M Y')),
                'ingresos' => $this->ingresosRango($mInicio, $mFin, $adminIds)['total'],
                'egresos' => $this->egresosRango($mInicio, $mFin)['total'],
            ];
        }

        // ===== Ingresos por origen (donut) =====
        $ingresosPorOrigen = array_values(array_filter([
            ['label' => 'Agencia', 'monto' => $ing['agencia'], 'color' => '#FF8100'],
            ['label' => 'Integraciones (SaaS)', 'monto' => $ing['saas'], 'color' => '#8b5cf6'],
            ['label' => 'Documentos propios', 'monto' => $ing['dte_propios'], 'color' => '#10b981'],
            ['label' => 'Manuales', 'monto' => $ing['manuales'], 'color' => '#64748b'],
        ], fn ($x) => $x['monto'] > 0));

        // ===== Gastos por categoría (donut) =====
        $gastosPorCategoria = DB::table('facturas_compra')
            ->join('categorias_gasto', 'facturas_compra.categoria_id', '=', 'categorias_gasto.id')
            ->whereIn('facturas_compra.estado', ['pendiente', 'pagada'])
            ->whereBetween('facturas_compra.fecha_emision', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->select('categorias_gasto.nombre', 'categorias_gasto.color', DB::raw('SUM(facturas_compra.monto_total) as total'))
            ->groupBy('categorias_gasto.nombre', 'categorias_gasto.color')
            ->get();

        return view('finanzas.dashboard', compact(
            'mes', 'anio',
            'totalIngresos', 'totalEgresos', 'utilidad', 'margen', 'comparativa',
            'ing', 'egresoFacturasCompra', 'egresoGastosOp', 'ingresosPorOrigen',
            'totalIvaDebito', 'ivaCredito', 'remanente', 'ivaPagar', 'remanenteSiguiente',
            'tendencia', 'gastosPorCategoria'
        ));
    }

    // ==========================================
    // INGRESOS
    // ==========================================
    public function ingresos(Request $request)
    {
        $adminIds = $this->getAdminUserIds();
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        $boletas = DB::table('boletas')
            ->where('status', 'emitida')
            ->whereIn('user_id', $adminIds)
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->select('id', 'folio', 'receptor_nombre', 'monto_neto', 'monto_total', 'created_at', DB::raw("'Boleta' as tipo_doc"))
            ->get();

        $facturas = DB::table('facturas_emitidas')
            ->where('status', 'emitida')
            ->whereIn('user_id', $adminIds)
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->select('id', 'folio', 'razon_social as receptor_nombre', 'monto_neto', 'monto_total', 'created_at', DB::raw("'Factura' as tipo_doc"))
            ->get();

        $cobrosAgencia = DB::table('agencia_cobros')
            ->join('agencia_clientes', 'agencia_cobros.agencia_cliente_id', '=', 'agencia_clientes.id')
            ->where('agencia_cobros.estado', 'pagado')
            ->whereBetween('agencia_cobros.pagado_at', [$inicioMes, $finMes])
            ->select('agencia_cobros.id', DB::raw('agencia_cobros.lioren_folio as folio'), 'agencia_clientes.nombre as receptor_nombre',
                     DB::raw('ROUND(agencia_cobros.monto / 1.19) as monto_neto'), 'agencia_cobros.monto as monto_total',
                     'agencia_cobros.pagado_at as created_at', DB::raw("'Cobro Agencia' as tipo_doc"))
            ->get();

        $manuales = DB::table('ingresos_manuales')
            ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->select('id', 'numero_documento as folio', 'cliente_nombre as receptor_nombre', 'monto_neto', 'monto_total', 'fecha as created_at', DB::raw("'Manual' as tipo_doc"))
            ->get();

        // SaaS: facturas_servicio en CLP (NO payments en UF, que corrompía el total).
        // Fecha del ingreso = COALESCE(pagada_at, periodo_inicio, created_at), igual que el dashboard.
        $payments = DB::table('facturas_servicio')
            ->leftJoin('users', 'facturas_servicio.user_id', '=', 'users.id')
            ->where('facturas_servicio.estado', 'pagada')
            ->whereRaw('COALESCE(facturas_servicio.pagada_at, facturas_servicio.periodo_inicio, facturas_servicio.created_at) BETWEEN ? AND ?', [$inicioMes, $finMes])
            ->select('facturas_servicio.id', 'facturas_servicio.folio as folio', 'users.name as receptor_nombre',
                     'facturas_servicio.monto_neto', 'facturas_servicio.monto as monto_total',
                     DB::raw('COALESCE(facturas_servicio.pagada_at, facturas_servicio.periodo_inicio, facturas_servicio.created_at) as created_at'),
                     DB::raw("'Pago Suscripción' as tipo_doc"))
            ->get();

        // Merge all into one collection
        $todosIngresos = collect()
            ->merge($boletas)
            ->merge($facturas)
            ->merge($cobrosAgencia)
            ->merge($manuales)
            ->merge($payments)
            ->sortByDesc('created_at')
            ->values();

        $totalIngresos = $todosIngresos->sum('monto_total');

        // Calculate individual source totals
        $ingresoBoletas = $boletas->sum('monto_total');
        $ingresoFacturas = $facturas->sum('monto_total');
        $ingresoCobrosAgencia = $cobrosAgencia->sum('monto_total');
        $ingresoPayments = $payments->sum('monto_total');
        $ingresosManuales = $manuales->sum('monto_total');
        
        // Build detalleIngresos array for the view
        $detalleIngresos = collect();
        foreach ($boletas as $b) {
            $detalleIngresos->push(['fecha' => \Carbon\Carbon::parse($b->created_at)->format('d/m/Y'), 'fuente' => 'Boleta', 'descripcion' => 'Boleta #' . $b->folio . ' - ' . ($b->receptor_nombre ?? 'S/N'), 'neto' => (int)$b->monto_neto, 'iva' => (int)($b->monto_total - $b->monto_neto), 'total' => (int)$b->monto_total]);
        }
        foreach ($facturas as $f) {
            $detalleIngresos->push(['fecha' => \Carbon\Carbon::parse($f->created_at)->format('d/m/Y'), 'fuente' => 'Factura', 'descripcion' => 'Factura #' . $f->folio . ' - ' . ($f->receptor_nombre ?? 'S/N'), 'neto' => (int)$f->monto_neto, 'iva' => (int)($f->monto_total - $f->monto_neto), 'total' => (int)$f->monto_total]);
        }
        foreach ($cobrosAgencia as $c) {
            $detalleIngresos->push(['fecha' => \Carbon\Carbon::parse($c->created_at)->format('d/m/Y'), 'fuente' => 'Cobro Agencia', 'descripcion' => 'Cobro #' . ($c->folio ?? '-') . ' - ' . ($c->receptor_nombre ?? 'S/N'), 'neto' => (int)$c->monto_neto, 'iva' => (int)($c->monto_total - $c->monto_neto), 'total' => (int)$c->monto_total]);
        }
        foreach ($payments as $p) {
            $detalleIngresos->push(['fecha' => \Carbon\Carbon::parse($p->created_at)->format('d/m/Y'), 'fuente' => 'Suscripción', 'descripcion' => 'Pago suscripción - ' . ($p->receptor_nombre ?? 'S/N'), 'neto' => (int)$p->monto_neto, 'iva' => (int)($p->monto_total - $p->monto_neto), 'total' => (int)$p->monto_total]);
        }
        foreach ($manuales as $m) {
            $detalleIngresos->push(['fecha' => \Carbon\Carbon::parse($m->created_at)->format('d/m/Y'), 'fuente' => 'Manual', 'descripcion' => ($m->folio ?? '-') . ' - ' . ($m->receptor_nombre ?? 'S/N'), 'neto' => (int)$m->monto_neto, 'iva' => (int)($m->monto_total - $m->monto_neto), 'total' => (int)$m->monto_total]);
        }
        $detalleIngresos = $detalleIngresos->sortByDesc('fecha')->values()->all();
        
        // Centros de costo for the modal
        $centrosCosto = DB::table('centros_costo')->where('activo', true)->get();
        
        return view('finanzas.ingresos', compact('todosIngresos', 'totalIngresos', 'mes', 'anio', 'ingresoBoletas', 'ingresoFacturas', 'ingresoCobrosAgencia', 'ingresoPayments', 'ingresosManuales', 'detalleIngresos', 'centrosCosto'));
    }

    public function storeIngresoManual(Request $request)
    {
        $request->validate([
            'concepto' => 'required|string|max:255',
            'monto_neto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);

        $montoNeto = (int)$request->monto_neto;
        $montoIva = (int)round($montoNeto * 0.19);
        $montoTotal = $montoNeto + $montoIva;

        if ($request->has('exento') && $request->exento) {
            $montoIva = 0;
            $montoTotal = $montoNeto;
        }

        DB::table('ingresos_manuales')->insert([
            'concepto' => $request->concepto,
            'monto_neto' => $montoNeto,
            'monto_iva' => $montoIva,
            'monto_total' => $montoTotal,
            'fecha' => $request->fecha,
            'categoria' => $request->categoria ?? 'Otros',
            'cliente_nombre' => $request->cliente_nombre,
            'cliente_rut' => $request->cliente_rut,
            'numero_documento' => $request->numero_documento,
            'notas' => $request->notas,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('finanzas.ingresos')->with('success', 'Ingreso registrado exitosamente.');
    }

    // ==========================================
    // EGRESOS / FACTURAS DE COMPRA
    // ==========================================
    public function egresos(Request $request)
    {
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay()->toDateString();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $facturasCompra = DB::table('facturas_compra')
            ->leftJoin('categorias_gasto', 'facturas_compra.categoria_id', '=', 'categorias_gasto.id')
            ->leftJoin('centros_costo', 'facturas_compra.centro_costo_id', '=', 'centros_costo.id')
            ->whereBetween('facturas_compra.fecha_emision', [$inicioMes, $finMes])
            ->select('facturas_compra.*', 'categorias_gasto.nombre as categoria_nombre', 'categorias_gasto.color as categoria_color', 'centros_costo.nombre as centro_costo_nombre')
            ->orderByDesc('facturas_compra.fecha_emision')
            ->get();

        $categorias = DB::table('categorias_gasto')->where('activa', true)->get();
        $centrosCosto = DB::table('centros_costo')->where('activo', true)->get();

        $totalNeto = $facturasCompra->sum('monto_neto');
        $totalIva = $facturasCompra->sum('monto_iva');
        $totalBruto = $facturasCompra->sum('monto_total');

        $gastosOperativos = DB::table('gastos_operativos')
            ->leftJoin('categorias_gasto', 'gastos_operativos.categoria_id', '=', 'categorias_gasto.id')
            ->where('gastos_operativos.activo', true)
            ->select('gastos_operativos.*', 'categorias_gasto.nombre as categoria_nombre')
            ->get();

        return view('finanzas.egresos', compact(
            'facturasCompra', 'categorias', 'centrosCosto',
            'totalNeto', 'totalIva', 'totalBruto',
            'gastosOperativos', 'mes', 'anio'
        ));
    }

    public function storeFacturaCompra(Request $request)
    {
        $request->validate([
            'proveedor_nombre' => 'required|string|max:255',
            'numero_factura' => 'required|string|max:50',
            'fecha_emision' => 'required|date',
            'monto_neto' => 'required|numeric|min:0',
        ]);

        $montoNeto = (int)$request->monto_neto;
        $montoIva = (int)round($montoNeto * 0.19);
        $montoTotal = $montoNeto + $montoIva;

        if ($request->has('exento') && $request->exento) {
            $montoIva = 0;
            $montoTotal = $montoNeto;
        }

        $data = [
            'proveedor_nombre' => $request->proveedor_nombre,
            'proveedor_rut' => $request->proveedor_rut,
            'numero_factura' => $request->numero_factura,
            'fecha_emision' => $request->fecha_emision,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'monto_neto' => $montoNeto,
            'monto_iva' => $montoIva,
            'monto_total' => $montoTotal,
            'categoria_id' => $request->categoria_id,
            'centro_costo_id' => $request->centro_costo_id,
            'estado' => $request->estado ?? 'pendiente',
            'metodo_pago' => $request->metodo_pago,
            'notas' => $request->notas,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($request->hasFile('archivo_pdf')) {
            $path = $request->file('archivo_pdf')->store('facturas_compra', 'public');
            $data['archivo_pdf'] = $path;
        }

        DB::table('facturas_compra')->insert($data);

        return redirect()->route('finanzas.egresos')->with('success', 'Factura de compra registrada exitosamente.');
    }

    public function updateFacturaCompra(Request $request, $id)
    {
        $request->validate([
            'proveedor_nombre' => 'required|string|max:255',
            'numero_factura' => 'required|string|max:50',
            'fecha_emision' => 'required|date',
            'monto_neto' => 'required|numeric|min:0',
        ]);

        $montoNeto = (int)$request->monto_neto;
        $montoIva = (int)round($montoNeto * 0.19);
        $montoTotal = $montoNeto + $montoIva;

        if ($request->has('exento') && $request->exento) {
            $montoIva = 0;
            $montoTotal = $montoNeto;
        }

        $data = [
            'proveedor_nombre' => $request->proveedor_nombre,
            'proveedor_rut' => $request->proveedor_rut,
            'numero_factura' => $request->numero_factura,
            'fecha_emision' => $request->fecha_emision,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'monto_neto' => $montoNeto,
            'monto_iva' => $montoIva,
            'monto_total' => $montoTotal,
            'categoria_id' => $request->categoria_id,
            'centro_costo_id' => $request->centro_costo_id,
            'estado' => $request->estado ?? 'pendiente',
            'metodo_pago' => $request->metodo_pago,
            'notas' => $request->notas,
            'updated_at' => now(),
        ];

        if ($request->estado === 'pagada' && !$request->pagada_at) {
            $data['pagada_at'] = now();
        }

        if ($request->hasFile('archivo_pdf')) {
            $path = $request->file('archivo_pdf')->store('facturas_compra', 'public');
            $data['archivo_pdf'] = $path;
        }

        DB::table('facturas_compra')->where('id', $id)->update($data);

        return redirect()->route('finanzas.egresos')->with('success', 'Factura de compra actualizada.');
    }

    public function deleteFacturaCompra($id)
    {
        DB::table('facturas_compra')->where('id', $id)->delete();
        return redirect()->route('finanzas.egresos')->with('success', 'Factura de compra eliminada.');
    }

    // ==========================================
    // IVA MENSUAL
    // ==========================================
    public function iva(Request $request)
    {
        $adminIds = $this->getAdminUserIds();
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        // DÉBITO FISCAL - desglosado
        $debitoDetalle = [];

        $ivaBoletas = DB::table('boletas')
            ->where('status', 'emitida')
            ->whereIn('user_id', $adminIds)
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->select(DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_neto) as neto'), DB::raw('SUM(monto_iva) as iva'))
            ->first();
        $debitoDetalle[] = ['tipo' => 'Boletas Electrónicas', 'cantidad' => $ivaBoletas->cantidad, 'neto' => (int)$ivaBoletas->neto, 'iva' => (int)$ivaBoletas->iva];

        $ivaFacturasV = DB::table('facturas_emitidas')
            ->where('status', 'emitida')
            ->whereIn('user_id', $adminIds)
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->select(DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_neto) as neto'), DB::raw('SUM(monto_iva) as iva'))
            ->first();
        $debitoDetalle[] = ['tipo' => 'Facturas Electrónicas', 'cantidad' => $ivaFacturasV->cantidad, 'neto' => (int)$ivaFacturasV->neto, 'iva' => (int)$ivaFacturasV->iva];

        $ivaCobros = DB::table('agencia_cobros')
            ->where('estado', 'pagado')
            ->where('factura_estado', 'emitida')
            ->whereBetween('pagado_at', [$inicioMes, $finMes])
            ->select(DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto) as bruto'), DB::raw('SUM(ROUND(monto * 0.19 / 1.19)) as iva'))
            ->first();
        $debitoDetalle[] = ['tipo' => 'Facturas Agencia', 'cantidad' => $ivaCobros->cantidad, 'neto' => (int)($ivaCobros->bruto - $ivaCobros->iva), 'iva' => (int)$ivaCobros->iva];

        $ivaManual = DB::table('ingresos_manuales')
            ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->select(DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_neto) as neto'), DB::raw('SUM(monto_iva) as iva'))
            ->first();
        $debitoDetalle[] = ['tipo' => 'Ingresos Manuales', 'cantidad' => $ivaManual->cantidad, 'neto' => (int)$ivaManual->neto, 'iva' => (int)$ivaManual->iva];

        // Notas de crédito (reducen débito)
        $ivaNC = DB::table('boletas')
            ->where('status', 'emitida')
            ->whereIn('user_id', $adminIds)
            ->whereIn('tipodoc', [61])
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->select(DB::raw('COUNT(*) as cantidad'), DB::raw('COALESCE(SUM(monto_neto),0) as neto'), DB::raw('COALESCE(SUM(monto_iva),0) as iva'))
            ->first();
        if ($ivaNC->cantidad > 0) {
            $debitoDetalle[] = ['tipo' => 'Notas de Crédito (descuento)', 'cantidad' => $ivaNC->cantidad, 'neto' => -(int)$ivaNC->neto, 'iva' => -(int)$ivaNC->iva];
        }

        $totalDebito = collect($debitoDetalle)->sum('iva');

        // CRÉDITO FISCAL
        $creditoDetalle = DB::table('facturas_compra')
            ->leftJoin('categorias_gasto', 'facturas_compra.categoria_id', '=', 'categorias_gasto.id')
            ->whereIn('facturas_compra.estado', ['pendiente', 'pagada'])
            ->whereBetween('facturas_compra.fecha_emision', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->select('facturas_compra.proveedor_nombre', 'facturas_compra.numero_factura', 'facturas_compra.monto_neto', 'facturas_compra.monto_iva', 'facturas_compra.monto_total', 'categorias_gasto.nombre as categoria')
            ->orderBy('facturas_compra.fecha_emision')
            ->get();

        $totalCredito = $creditoDetalle->sum('monto_iva');

        // Remanente anterior
        $remanente = 0;
        $ivaPrevio = DB::table('iva_mensual')
            ->where('anio', $mes == 1 ? $anio - 1 : $anio)
            ->where('mes', $mes == 1 ? 12 : $mes - 1)
            ->first();
        if ($ivaPrevio) {
            $remanente = $ivaPrevio->remanente_siguiente;
        }

        $ivaPagar = max(0, $totalDebito - $totalCredito - $remanente);
        $remanenteSiguiente = max(0, ($totalCredito + $remanente) - $totalDebito);

        // Histórico IVA
        $historicoIva = DB::table('iva_mensual')->orderByDesc('anio')->orderByDesc('mes')->limit(12)->get();

        // Alias variables for the view
        $totalIvaDebito = $totalDebito;
        $totalIvaCredito = $totalCredito;
        $countBoletas = (int)($ivaBoletas->cantidad ?? 0);
        $countFacturas = (int)($ivaFacturasV->cantidad ?? 0);
        $countNC = (int)($ivaNC->cantidad ?? 0);
        $ivaBoletas = (int)($ivaBoletas->iva ?? 0);
        $ivaFacturas = (int)($ivaFacturasV->iva ?? 0);
        $ivaNC = (int)($ivaNC->iva ?? 0);
        $countCompras = $creditoDetalle->count();
        // Historial IVA con la forma que espera la vista (mes como texto + claves debito/credito/remanente).
        $historialIva = $historicoIva->map(function ($r) {
            return [
                'mes' => \Carbon\Carbon::create($r->anio, $r->mes, 1)->translatedFormat('F Y'),
                'debito' => (int) $r->debito_fiscal,
                'credito' => (int) $r->credito_fiscal,
                'remanente' => (int) $r->remanente_anterior,
            ];
        })->all();

        // ¿El IVA de este período ya fue registrado como pagado?
        $ivaRegistro = $historicoIva->first(fn ($r) => (int) $r->anio === (int) $anio && (int) $r->mes === (int) $mes);
        $ivaPagado = $ivaRegistro && !empty($ivaRegistro->pagado_at);
        $ivaPagadoAt = $ivaPagado ? \Carbon\Carbon::parse($ivaRegistro->pagado_at) : null;

        return view('finanzas.iva', compact(
            'mes', 'anio',
            'debitoDetalle', 'totalDebito', 'totalIvaDebito',
            'creditoDetalle', 'totalCredito', 'totalIvaCredito',
            'remanente', 'ivaPagar', 'remanenteSiguiente',
            'historicoIva', 'historialIva',
            'ivaBoletas', 'ivaFacturas', 'ivaNC',
            'countBoletas', 'countFacturas', 'countNC', 'countCompras',
            'ivaPagado', 'ivaPagadoAt'
        ));
    }

    public function cerrarIva(Request $request)
    {
        $adminIds = $this->getAdminUserIds();
        $mes = $request->mes;
        $anio = $request->anio;

        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        $totalDebito = DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereBetween('created_at', [$inicioMes, $finMes])->sum('monto_iva')
            + DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereBetween('created_at', [$inicioMes, $finMes])->sum('monto_iva')
            + DB::table('agencia_cobros')->where('estado', 'pagado')->where('factura_estado', 'emitida')->whereBetween('pagado_at', [$inicioMes, $finMes])->sum(DB::raw('ROUND(monto * 0.19 / 1.19)'))
            + DB::table('ingresos_manuales')->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])->sum('monto_iva');

        // Restar NC
        $ivaNC = DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereIn('tipodoc', [61])->whereBetween('created_at', [$inicioMes, $finMes])->sum('monto_iva');
        $totalDebito -= $ivaNC;

        $totalCredito = DB::table('facturas_compra')->whereIn('estado', ['pendiente', 'pagada'])->whereBetween('fecha_emision', [$inicioMes->toDateString(), $finMes->toDateString()])->sum('monto_iva');

        $remanente = 0;
        $ivaPrevio = DB::table('iva_mensual')->where('anio', $mes == 1 ? $anio - 1 : $anio)->where('mes', $mes == 1 ? 12 : $mes - 1)->first();
        if ($ivaPrevio) $remanente = $ivaPrevio->remanente_siguiente;

        $ivaPagar = max(0, $totalDebito - $totalCredito - $remanente);
        $remanenteSiguiente = max(0, ($totalCredito + $remanente) - $totalDebito);

        DB::table('iva_mensual')->updateOrInsert(
            ['anio' => $anio, 'mes' => $mes],
            [
                'debito_fiscal' => $totalDebito,
                'credito_fiscal' => $totalCredito,
                'remanente_anterior' => $remanente,
                'iva_a_pagar' => $ivaPagar,
                'remanente_siguiente' => $remanenteSiguiente,
                'estado' => 'cerrado',
                'cerrado_at' => now(),
                'updated_at' => now(),
            ]
        );

        return redirect()->route('finanzas.iva', ['mes' => $mes, 'anio' => $anio])->with('success', 'Período de IVA cerrado exitosamente.');
    }

    /**
     * Registra el PAGO del IVA de un período. Deja constancia en iva_mensual (pagado_at + estado
     * cerrado) usando el mismo cálculo que el resto del módulo. Una vez registrado, el recordatorio
     * de la campana y los correos automáticos dejan de aparecer para ese período.
     */
    public function registrarPagoIva(Request $request)
    {
        $data = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020|max:2100',
        ]);
        $mes = (int) $data['mes'];
        $anio = (int) $data['anio'];

        $calc = app(\App\Services\IvaCalculator::class)->paraPeriodo($mes, $anio);

        DB::table('iva_mensual')->updateOrInsert(
            ['anio' => $anio, 'mes' => $mes],
            [
                'debito_fiscal' => $calc['debito'],
                'credito_fiscal' => $calc['credito'],
                'remanente_anterior' => $calc['remanente'],
                'iva_a_pagar' => $calc['a_pagar'],
                'remanente_siguiente' => $calc['remanente_siguiente'],
                'estado' => 'cerrado',
                'pagado_at' => now(),
                'pagado_por' => auth()->id(),
                'cerrado_at' => now(),
                'updated_at' => now(),
            ]
        );

        $mesNombre = Carbon::create($anio, $mes, 1)->translatedFormat('F Y');
        return redirect()->route('finanzas.iva', ['mes' => $mes, 'anio' => $anio])
            ->with('success', "Pago de IVA de {$mesNombre} registrado. Ya no recibirás recordatorios de este período.");
    }

    // ==========================================
    // CONCILIACIÓN BANCARIA
    // ==========================================
    /**
     * Estado de cuenta SEGÚN FINANZAS: movimientos de caja documentados (ingresos cobrados/emitidos
     * y egresos pagados) hasta $hasta, ordenados por fecha asc. Es la base del saldo automático y de
     * la conciliación contra la cartola real del banco. (No incluye gastos operativos recurrentes,
     * que son plantillas sin fecha de cargo real.)
     * @return array<int,array{fecha:string,tipo:string,monto:int,descripcion:string,origen:string}>
     */
    private function movimientosFinanzas($adminIds, $hasta): array
    {
        $h = Carbon::parse($hasta)->endOfDay();
        $mov = [];

        // --- INGRESOS ---
        foreach (DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
                ->where('tipodoc', '!=', 61)->where('created_at', '<=', $h)
                ->select('folio', 'monto_total', 'created_at')->get() as $b) {
            $mov[] = ['fecha' => Carbon::parse($b->created_at)->toDateString(), 'tipo' => 'ingreso', 'monto' => (int) $b->monto_total, 'descripcion' => 'Boleta #' . $b->folio, 'origen' => 'Boleta'];
        }
        foreach (DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
                ->where('created_at', '<=', $h)->select('folio', 'monto_total', 'created_at')->get() as $f) {
            $mov[] = ['fecha' => Carbon::parse($f->created_at)->toDateString(), 'tipo' => 'ingreso', 'monto' => (int) $f->monto_total, 'descripcion' => 'Factura #' . $f->folio, 'origen' => 'Factura'];
        }
        foreach (DB::table('agencia_cobros')->where('estado', 'pagado')->whereNotNull('pagado_at')
                ->where('pagado_at', '<=', $h)->select('concepto', 'monto', 'pagado_at')->get() as $c) {
            $mov[] = ['fecha' => Carbon::parse($c->pagado_at)->toDateString(), 'tipo' => 'ingreso', 'monto' => (int) $c->monto, 'descripcion' => 'Cobro agencia: ' . ($c->concepto ?: 'servicio'), 'origen' => 'Agencia'];
        }
        foreach (DB::table('facturas_servicio')->where('estado', 'pagada')
                ->whereRaw('COALESCE(pagada_at, periodo_inicio, created_at) <= ?', [$h])
                ->select('monto', DB::raw('COALESCE(pagada_at, periodo_inicio, created_at) as f'))->get() as $s) {
            $mov[] = ['fecha' => Carbon::parse($s->f)->toDateString(), 'tipo' => 'ingreso', 'monto' => (int) $s->monto, 'descripcion' => 'Suscripción SaaS', 'origen' => 'SaaS'];
        }
        foreach (DB::table('ingresos_manuales')->where('fecha', '<=', $h->toDateString())
                ->select('concepto', 'monto_total', 'fecha')->get() as $im) {
            $mov[] = ['fecha' => Carbon::parse($im->fecha)->toDateString(), 'tipo' => 'ingreso', 'monto' => (int) $im->monto_total, 'descripcion' => $im->concepto ?: 'Ingreso manual', 'origen' => 'Manual'];
        }

        // --- EGRESOS ---
        foreach (DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
                ->where('tipodoc', 61)->where('created_at', '<=', $h)
                ->select('folio', 'monto_total', 'created_at')->get() as $nc) {
            $mov[] = ['fecha' => Carbon::parse($nc->created_at)->toDateString(), 'tipo' => 'egreso', 'monto' => (int) $nc->monto_total, 'descripcion' => 'Nota de crédito #' . $nc->folio, 'origen' => 'NC'];
        }
        foreach (DB::table('facturas_compra')->where('estado', 'pagada')
                ->whereRaw('COALESCE(pagada_at, fecha_emision) <= ?', [$h])
                ->select('proveedor_nombre', 'monto_total', DB::raw('COALESCE(pagada_at, fecha_emision) as f'))->get() as $fc) {
            $mov[] = ['fecha' => Carbon::parse($fc->f)->toDateString(), 'tipo' => 'egreso', 'monto' => (int) $fc->monto_total, 'descripcion' => 'Compra: ' . $fc->proveedor_nombre, 'origen' => 'Compra'];
        }

        usort($mov, fn ($a, $b) => $a['fecha'] <=> $b['fecha']);
        return $mov;
    }

    public function banco(Request $request)
    {
        $adminIds = $this->getAdminUserIds();
        $cuentas = DB::table('cuentas_banco')->where('activa', true)->get();
        $cuentaActiva = $cuentas->first();

        $mes = (int) $request->get('mes', now()->month);
        $anio = (int) $request->get('anio', now()->year);
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        // Estado de cuenta automático (hasta fin del mes consultado).
        $todos = $this->movimientosFinanzas($adminIds, $finMes);

        $saldoInicial = 0;
        foreach ($todos as $m) {
            if (Carbon::parse($m['fecha'])->lt($inicioMes)) {
                $saldoInicial += $m['tipo'] === 'ingreso' ? $m['monto'] : -$m['monto'];
            }
        }

        $saldoCorrido = $saldoInicial;
        $movimientosMes = [];
        $ingresosMes = 0;
        $egresosMes = 0;
        foreach ($todos as $m) {
            $fecha = Carbon::parse($m['fecha']);
            if ($fecha->lt($inicioMes) || $fecha->gt($finMes)) {
                continue;
            }
            $saldoCorrido += $m['tipo'] === 'ingreso' ? $m['monto'] : -$m['monto'];
            if ($m['tipo'] === 'ingreso') {
                $ingresosMes += $m['monto'];
            } else {
                $egresosMes += $m['monto'];
            }
            $m['saldo'] = $saldoCorrido;
            $movimientosMes[] = $m;
        }
        $movimientosMes = array_reverse($movimientosMes); // más recientes arriba
        $saldoActual = $saldoInicial + $ingresosMes - $egresosMes;

        // Saldo REAL de la cuenta: ancla informada por el usuario (o la cartola).
        $saldoCuenta = $cuentaActiva ? (int) $cuentaActiva->saldo_actual : null;
        $saldoCuentaFecha = $cuentaActiva && !empty($cuentaActiva->saldo_fecha) ? $cuentaActiva->saldo_fecha : null;

        // Un solo recorrido de movimientos hasta hoy: flujo neto total (referencial) y el ajuste
        // POSTERIOR al ancla para proyectar el saldo de la cuenta (saldo anclado).
        $flujoNetoFinanzas = 0;
        $ajusteDesdeAncla = 0;
        $movPostAncla = 0;
        $anclaDia = $saldoCuentaFecha ? Carbon::parse($saldoCuentaFecha)->endOfDay() : null;
        foreach ($this->movimientosFinanzas($adminIds, now()) as $m) {
            $delta = $m['tipo'] === 'ingreso' ? $m['monto'] : -$m['monto'];
            $flujoNetoFinanzas += $delta;
            if ($anclaDia && Carbon::parse($m['fecha'])->gt($anclaDia)) {
                $ajusteDesdeAncla += $delta;
                $movPostAncla++;
            }
        }
        // Saldo proyectado = ancla + movimientos nuevos. Null si nunca se fijó un saldo.
        $saldoProyectado = $saldoCuentaFecha !== null ? $saldoCuenta + $ajusteDesdeAncla : null;

        // Cartola real importada (conciliación) — flujo secundario.
        $cartola = collect();
        $statsCartola = ['total' => 0, 'conciliados' => 0, 'pendientes' => 0];
        if ($cuentaActiva) {
            $cartola = DB::table('movimientos_banco')->where('cuenta_id', $cuentaActiva->id)
                ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
                ->orderByDesc('fecha')->orderByDesc('id')->get();
            $statsCartola = [
                'total' => DB::table('movimientos_banco')->where('cuenta_id', $cuentaActiva->id)->count(),
                'conciliados' => DB::table('movimientos_banco')->where('cuenta_id', $cuentaActiva->id)->where('estado_conciliacion', 'conciliado')->count(),
                'pendientes' => DB::table('movimientos_banco')->where('cuenta_id', $cuentaActiva->id)->where('estado_conciliacion', 'pendiente')->count(),
            ];
        }

        return view('finanzas.banco', compact(
            'cuentas', 'cuentaActiva', 'mes', 'anio',
            'movimientosMes', 'saldoInicial', 'saldoActual', 'flujoNetoFinanzas', 'ingresosMes', 'egresosMes',
            'saldoCuenta', 'saldoCuentaFecha', 'saldoProyectado', 'ajusteDesdeAncla', 'movPostAncla', 'cartola', 'statsCartola'
        ));
    }

    /** Actualiza el saldo REAL de una cuenta bancaria (lo informa el usuario o sale de la cartola). */
    public function actualizarSaldoCuenta(Request $request, $id)
    {
        $data = $request->validate(['saldo_actual' => 'required|numeric']);
        DB::table('cuentas_banco')->where('id', $id)->update([
            'saldo_actual' => (int) round($data['saldo_actual']),
            'saldo_fecha' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('finanzas.banco')->with('success', 'Saldo de la cuenta actualizado.');
    }

    public function storeCuentaBanco(Request $request)
    {
        $request->validate([
            'banco' => 'required|string|max:100',
            'tipo_cuenta' => 'required|string|max:50',
            'numero_cuenta' => 'required|string|max:50',
            'titular' => 'required|string|max:255',
        ]);

        DB::table('cuentas_banco')->insert([
            'banco' => $request->banco,
            'tipo_cuenta' => $request->tipo_cuenta,
            'numero_cuenta' => $request->numero_cuenta,
            'titular' => $request->titular,
            'rut_titular' => $request->rut_titular,
            'saldo_actual' => $request->saldo_actual ?? 0,
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('finanzas.banco')->with('success', 'Cuenta bancaria registrada.');
    }

    public function importarCartola(Request $request)
    {
        $request->validate([
            'cuenta_id' => 'required|exists:cuentas_banco,id',
            'archivo' => 'required|file|mimes:csv,xlsx,xls,txt',
        ]);

        $file = $request->file('archivo');
        $cuentaId = $request->cuenta_id;
        $extension = $file->getClientOriginalExtension();

        $rows = [];

        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($file->getPathname(), 'r');
            $header = null;
            while (($line = fgetcsv($handle, 0, ';')) !== false) {
                if (!$header) {
                    $header = array_map('strtolower', array_map('trim', $line));
                    continue;
                }
                $row = array_combine($header, array_map('trim', $line));
                $rows[] = $row;
            }
            fclose($handle);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            // Use PhpSpreadsheet if available, otherwise fall back to CSV
            if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $header = null;
                foreach ($sheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $cells = [];
                    foreach ($cellIterator as $cell) {
                        $cells[] = trim($cell->getValue() ?? '');
                    }
                    if (!$header) {
                        $header = array_map('strtolower', $cells);
                        continue;
                    }
                    $rows[] = array_combine($header, $cells);
                }
            }
        }

        $importados = 0;
        $duplicados = 0;
        $fechaDesde = null;
        $fechaHasta = null;

        foreach ($rows as $row) {
            // Try to parse BCI format: fecha, descripcion, cargo, abono, saldo
            $fecha = $this->parseDate($row['fecha'] ?? $row['date'] ?? '');
            if (!$fecha) continue;

            $descripcion = $row['descripcion'] ?? $row['detalle'] ?? $row['glosa'] ?? '';
            $cargo = $this->parseAmount($row['cargo'] ?? $row['débito'] ?? $row['debito'] ?? '0');
            $abono = $this->parseAmount($row['abono'] ?? $row['crédito'] ?? $row['credito'] ?? $row['haber'] ?? '0');
            $saldo = $this->parseAmount($row['saldo'] ?? '');

            $tipo = $abono > 0 ? 'ingreso' : 'egreso';
            $monto = $abono > 0 ? $abono : $cargo;

            if ($monto == 0) continue;

            // Check duplicate
            $exists = DB::table('movimientos_banco')
                ->where('cuenta_id', $cuentaId)
                ->where('fecha', $fecha)
                ->where('monto', $monto)
                ->where('tipo', $tipo)
                ->where('descripcion', $descripcion)
                ->exists();

            if ($exists) {
                $duplicados++;
                continue;
            }

            if (!$fechaDesde || $fecha < $fechaDesde) $fechaDesde = $fecha;
            if (!$fechaHasta || $fecha > $fechaHasta) $fechaHasta = $fecha;

            DB::table('movimientos_banco')->insert([
                'cuenta_id' => $cuentaId,
                'fecha' => $fecha,
                'descripcion' => $descripcion,
                'referencia' => $row['n° operacion'] ?? $row['referencia'] ?? $row['numero'] ?? null,
                'tipo' => $tipo,
                'monto' => $monto,
                'saldo' => $saldo ?: null,
                'estado_conciliacion' => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $importados++;
        }

        // Update account balance
        if ($importados > 0) {
            $ultimoMov = DB::table('movimientos_banco')
                ->where('cuenta_id', $cuentaId)
                ->whereNotNull('saldo')
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->first();
            if ($ultimoMov) {
                DB::table('cuentas_banco')->where('id', $cuentaId)->update(['saldo_actual' => $ultimoMov->saldo]);
            }
        }

        // Log import
        DB::table('importaciones_cartola')->insert([
            'cuenta_id' => $cuentaId,
            'archivo_original' => $file->getClientOriginalName(),
            'total_movimientos' => $importados,
            'duplicados_omitidos' => $duplicados,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'importado_por' => auth()->id(),
            'created_at' => now(),
        ]);

        // Auto-match
        $matched = $this->autoMatchMovimientos($cuentaId);

        return redirect()->route('finanzas.banco', ['cuenta_id' => $cuentaId])
            ->with('success', "Importación completada: {$importados} movimientos importados, {$duplicados} duplicados omitidos, {$matched} conciliados automáticamente.");
    }

    public function conciliarMovimiento(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|in:conciliar,ignorar,pendiente',
        ]);

        $data = ['updated_at' => now()];

        if ($request->accion === 'conciliar') {
            $data['estado_conciliacion'] = 'conciliado';
            $data['conciliado_con_tipo'] = $request->tipo_referencia;
            $data['conciliado_con_id'] = $request->referencia_id;
            $data['conciliado_at'] = now();
        } elseif ($request->accion === 'ignorar') {
            $data['estado_conciliacion'] = 'ignorado';
        } else {
            $data['estado_conciliacion'] = 'pendiente';
            $data['conciliado_con_tipo'] = null;
            $data['conciliado_con_id'] = null;
            $data['conciliado_at'] = null;
        }

        DB::table('movimientos_banco')->where('id', $id)->update($data);

        return redirect()->back()->with('success', 'Movimiento actualizado.');
    }

    private function autoMatchMovimientos($cuentaId)
    {
        $matched = 0;
        $pendientes = DB::table('movimientos_banco')
            ->where('cuenta_id', $cuentaId)
            ->where('estado_conciliacion', 'pendiente')
            ->get();

        foreach ($pendientes as $mov) {
            // Try matching with agencia_cobros (by amount and date range)
            if ($mov->tipo === 'ingreso') {
                $cobro = DB::table('agencia_cobros')
                    ->where('estado', 'pagado')
                    ->where('monto', $mov->monto)
                    ->whereBetween('pagado_at', [
                        Carbon::parse($mov->fecha)->subDays(3),
                        Carbon::parse($mov->fecha)->addDays(3)
                    ])
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                          ->from('movimientos_banco as mb')
                          ->whereColumn('mb.conciliado_con_id', 'agencia_cobros.id')
                          ->where('mb.conciliado_con_tipo', 'agencia_cobro');
                    })
                    ->first();

                if ($cobro) {
                    DB::table('movimientos_banco')->where('id', $mov->id)->update([
                        'estado_conciliacion' => 'conciliado',
                        'conciliado_con_tipo' => 'agencia_cobro',
                        'conciliado_con_id' => $cobro->id,
                        'conciliado_at' => now(),
                    ]);
                    $matched++;
                    continue;
                }

                // Try matching with payments
                $payment = DB::table('payments')
                    ->where('status', 2)
                    ->where('amount', $mov->monto)
                    ->whereBetween('paid_at', [
                        Carbon::parse($mov->fecha)->subDays(3),
                        Carbon::parse($mov->fecha)->addDays(3)
                    ])
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                          ->from('movimientos_banco as mb')
                          ->whereColumn('mb.conciliado_con_id', 'payments.id')
                          ->where('mb.conciliado_con_tipo', 'payment');
                    })
                    ->first();

                if ($payment) {
                    DB::table('movimientos_banco')->where('id', $mov->id)->update([
                        'estado_conciliacion' => 'conciliado',
                        'conciliado_con_tipo' => 'payment',
                        'conciliado_con_id' => $payment->id,
                        'conciliado_at' => now(),
                    ]);
                    $matched++;
                    continue;
                }
            }

            // Try matching egresos with facturas_compra
            if ($mov->tipo === 'egreso') {
                $factura = DB::table('facturas_compra')
                    ->where('monto_total', $mov->monto)
                    ->whereIn('estado', ['pendiente', 'pagada'])
                    ->whereBetween('fecha_emision', [
                        Carbon::parse($mov->fecha)->subDays(30),
                        Carbon::parse($mov->fecha)->addDays(3)
                    ])
                    ->whereNull('movimiento_banco_id')
                    ->first();

                if ($factura) {
                    DB::table('movimientos_banco')->where('id', $mov->id)->update([
                        'estado_conciliacion' => 'conciliado',
                        'conciliado_con_tipo' => 'factura_compra',
                        'conciliado_con_id' => $factura->id,
                        'conciliado_at' => now(),
                    ]);
                    DB::table('facturas_compra')->where('id', $factura->id)->update([
                        'movimiento_banco_id' => $mov->id,
                        'estado' => 'pagada',
                        'pagada_at' => $mov->fecha,
                    ]);
                    $matched++;
                }
            }
        }

        return $matched;
    }

    // ==========================================
    // CUENTAS POR COBRAR
    // ==========================================
    public function cuentasCobrar(Request $request)
    {
        // Cobros pendientes de agencia
        $cobrosPendientes = DB::table('agencia_cobros')
            ->join('agencia_clientes', 'agencia_cobros.agencia_cliente_id', '=', 'agencia_clientes.id')
            ->whereIn('agencia_cobros.estado', ['pendiente', 'vencido'])
            ->select('agencia_cobros.*', 'agencia_clientes.nombre as cliente_nombre')
            ->orderBy('agencia_cobros.vence_at')
            ->get();

        // Facturas de servicio pendientes
        $facturasPendientes = DB::table('facturas_servicio')
            ->leftJoin('users', 'facturas_servicio.user_id', '=', 'users.id')
            ->where('facturas_servicio.estado', 'pendiente')
            ->select('facturas_servicio.*', 'users.name as cliente_nombre')
            ->get();

        // Aging report
        $hoy = now();
        $aging = [
            '0-30' => ['monto' => 0, 'cantidad' => 0],
            '31-60' => ['monto' => 0, 'cantidad' => 0],
            '61-90' => ['monto' => 0, 'cantidad' => 0],
            '90+' => ['monto' => 0, 'cantidad' => 0],
        ];

        foreach ($cobrosPendientes as $cobro) {
            $dias = $hoy->diffInDays(Carbon::parse($cobro->created_at));
            if ($dias <= 30) { $aging['0-30']['monto'] += $cobro->monto; $aging['0-30']['cantidad']++; }
            elseif ($dias <= 60) { $aging['31-60']['monto'] += $cobro->monto; $aging['31-60']['cantidad']++; }
            elseif ($dias <= 90) { $aging['61-90']['monto'] += $cobro->monto; $aging['61-90']['cantidad']++; }
            else { $aging['90+']['monto'] += $cobro->monto; $aging['90+']['cantidad']++; }
        }

        $totalPorCobrar = $cobrosPendientes->sum('monto') + $facturasPendientes->sum('monto');
        
        // Calculate additional variables the view needs
        $totalPendiente = $totalPorCobrar;
        $vencidasCobrar = $cobrosPendientes->filter(fn($c) => $c->vence_at && Carbon::parse($c->vence_at)->lt(now()));
        $porVencerCobrar = $cobrosPendientes->filter(fn($c) => !$c->vence_at || Carbon::parse($c->vence_at)->gte(now()));
        $totalVencido = $vencidasCobrar->sum('monto');
        $totalPorVencer = $porVencerCobrar->sum('monto');
        $countVencidas = $vencidasCobrar->count();
        $countPorVencer = $porVencerCobrar->count();
        
        // Cobrado este mes
        $cobradoMes = DB::table('agencia_cobros')
            ->where('estado', 'pagado')
            ->whereMonth('pagado_at', now()->month)
            ->whereYear('pagado_at', now()->year)
            ->sum('monto')
            + DB::table('facturas_servicio')
            ->where('estado', 'pagada')
            ->whereRaw('YEAR(COALESCE(pagada_at, periodo_inicio, created_at)) = ? AND MONTH(COALESCE(pagada_at, periodo_inicio, created_at)) = ?', [now()->year, now()->month])
            ->sum('monto');
        
        // Build cuentasCobrar array for the table
        $cuentasCobrar = collect();
        foreach ($cobrosPendientes as $c) {
            $dias = now()->diffInDays(Carbon::parse($c->created_at));
            $cuentasCobrar->push([
                'fecha' => Carbon::parse($c->created_at)->format('d/m/Y'),
                'tipo' => 'Cobro Agencia',
                'cliente' => $c->cliente_nombre ?? 'S/N',
                'descripcion' => $c->concepto ?? 'Cobro pendiente',
                'monto' => (int)$c->monto,
                'dias' => (int)$dias,
            ]);
        }
        foreach ($facturasPendientes as $f) {
            $dias = now()->diffInDays(Carbon::parse($f->created_at));
            $cuentasCobrar->push([
                'fecha' => Carbon::parse($f->created_at)->format('d/m/Y'),
                'tipo' => 'Factura Servicio',
                'cliente' => $f->cliente_nombre ?? 'S/N',
                'descripcion' => 'Factura #' . ($f->numero_factura ?? $f->id),
                'monto' => (int)$f->monto,
                'dias' => (int)$dias,
            ]);
        }
        $cuentasCobrar = $cuentasCobrar->sortByDesc('dias')->values()->all();
        
        // Add color and count to aging
        $agingColors = ['0-30' => '#10b981', '31-60' => '#f59e0b', '61-90' => '#ef4444', '90+' => '#991b1b'];
        foreach ($aging as $rango => &$datos) {
            $datos['color'] = $agingColors[$rango] ?? '#94a3b8';
            $datos['count'] = $datos['cantidad'];
        }
        unset($datos);
        
        return view('finanzas.cuentas-cobrar', compact('cobrosPendientes', 'facturasPendientes', 'aging', 'totalPorCobrar', 'totalPendiente', 'totalVencido', 'totalPorVencer', 'countVencidas', 'countPorVencer', 'cuentasCobrar', 'cobradoMes'));
    }

    // ==========================================
    // CUENTAS POR PAGAR
    // ==========================================
    public function cuentasPagar(Request $request)
    {
        $facturasPendientes = DB::table('facturas_compra')
            ->leftJoin('categorias_gasto', 'facturas_compra.categoria_id', '=', 'categorias_gasto.id')
            ->whereIn('facturas_compra.estado', ['pendiente', 'vencida'])
            ->select('facturas_compra.*', 'categorias_gasto.nombre as categoria_nombre')
            ->orderBy('facturas_compra.fecha_vencimiento')
            ->get();

        $gastosOperativos = DB::table('gastos_operativos')
            ->leftJoin('categorias_gasto', 'gastos_operativos.categoria_id', '=', 'categorias_gasto.id')
            ->where('gastos_operativos.activo', true)
            ->select('gastos_operativos.*', 'categorias_gasto.nombre as categoria_nombre')
            ->get();

        $hoy = now();
        $vencidas = $facturasPendientes->filter(fn($f) => $f->fecha_vencimiento && Carbon::parse($f->fecha_vencimiento)->lt($hoy));
        $porVencer7 = $facturasPendientes->filter(fn($f) => $f->fecha_vencimiento && Carbon::parse($f->fecha_vencimiento)->between($hoy, $hoy->copy()->addDays(7)));
        $porVencer30 = $facturasPendientes->filter(fn($f) => $f->fecha_vencimiento && Carbon::parse($f->fecha_vencimiento)->between($hoy->copy()->addDays(8), $hoy->copy()->addDays(30)));

        $totalPorPagar = $facturasPendientes->sum('monto_total') + $gastosOperativos->sum('monto');
        $totalVencidas = $vencidas->sum('monto_total');
        $totalProximas = $porVencer7->sum('monto_total');
        $countVencidas = $vencidas->count();
        $countProximas = $porVencer7->count();
        
        // Vencimientos próximos (next 30 days + vencidas)
        $vencimientosProximos = $facturasPendientes->filter(fn($f) => $f->fecha_vencimiento)->sortBy('fecha_vencimiento');
        
        return view('finanzas.cuentas-pagar', compact('facturasPendientes', 'gastosOperativos', 'vencidas', 'porVencer7', 'porVencer30', 'totalPorPagar', 'totalVencidas', 'totalProximas', 'countVencidas', 'countProximas', 'vencimientosProximos'));
    }

    // ==========================================
    // REPORTES
    // ==========================================
    public function reportes(Request $request)
    {
        $adminIds = $this->getAdminUserIds();
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);

        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();
        
        // Counts
        $countBoletas = DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereNotIn('tipodoc', [61])->whereBetween('created_at', [$inicioMes, $finMes])->count();
        $countFacturas = DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereBetween('created_at', [$inicioMes, $finMes])->count();
        $countNC = DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereIn('tipodoc', [61])->whereBetween('created_at', [$inicioMes, $finMes])->count();
        $countCompras = DB::table('facturas_compra')->whereIn('estado', ['pendiente', 'pagada'])->whereBetween('fecha_emision', [$inicioMes->toDateString(), $finMes->toDateString()])->count();
        
        // Ventas neto
        $ventasNeto = DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereNotIn('tipodoc', [61])->whereBetween('created_at', [$inicioMes, $finMes])->sum('monto_neto')
            + DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereBetween('created_at', [$inicioMes, $finMes])->sum('monto_neto');
        
        // Compras neto
        $comprasNeto = DB::table('facturas_compra')->whereIn('estado', ['pendiente', 'pagada'])->whereBetween('fecha_emision', [$inicioMes->toDateString(), $finMes->toDateString()])->sum('monto_neto');
        
        // IVA
        $ivaDebito = DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereBetween('created_at', [$inicioMes, $finMes])->sum('monto_iva')
            + DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereBetween('created_at', [$inicioMes, $finMes])->sum('monto_iva')
            + DB::table('facturas_servicio')->where('estado', 'pagada')->whereRaw('COALESCE(pagada_at, periodo_inicio, created_at) BETWEEN ? AND ?', [$inicioMes, $finMes])->sum('monto_iva');
        $ivaCredito = DB::table('facturas_compra')->whereIn('estado', ['pendiente', 'pagada'])->whereBetween('fecha_emision', [$inicioMes->toDateString(), $finMes->toDateString()])->sum('monto_iva');
        
        // Total ingresos
        $totalIngresos = $ventasNeto
            + DB::table('agencia_cobros')->where('estado', 'pagado')->whereBetween('pagado_at', [$inicioMes, $finMes])->sum('monto')
            + DB::table('facturas_servicio')->where('estado', 'pagada')->whereRaw('COALESCE(pagada_at, periodo_inicio, created_at) BETWEEN ? AND ?', [$inicioMes, $finMes])->sum('monto')
            + DB::table('ingresos_manuales')->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])->sum('monto_total');
        $totalEgresos = $comprasNeto + DB::table('gastos_operativos')->where('activo', true)->sum('monto');
        $utilidad = $totalIngresos - $totalEgresos;
        
        return view('finanzas.reportes', compact('mes', 'anio', 'countBoletas', 'countFacturas', 'countNC', 'countCompras', 'ventasNeto', 'comprasNeto', 'ivaDebito', 'ivaCredito', 'totalIngresos', 'totalEgresos', 'utilidad'));
    }

    public function exportarLibroVentas(Request $request)
    {
        $mes = $request->mes;
        $anio = $request->anio;
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        $boletas = DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereBetween('created_at', [$inicioMes, $finMes])
            ->select('folio', DB::raw("'Boleta' as tipo"), 'receptor_nombre', 'receptor_rut', 'monto_neto', DB::raw('monto_iva as iva'), 'monto_total', 'created_at as fecha')->get();

        $facturas = DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereBetween('created_at', [$inicioMes, $finMes])
            ->select('folio', DB::raw("'Factura' as tipo"), 'razon_social as receptor_nombre', 'rut_receptor as receptor_rut', 'monto_neto', DB::raw('monto_iva as iva'), 'monto_total', 'created_at as fecha')->get();

        $nc = DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)->whereIn('tipodoc', [61])->whereBetween('created_at', [$inicioMes, $finMes])
            ->select('folio', DB::raw("'Nota Crédito' as tipo"), 'receptor_nombre', 'receptor_rut', 'monto_neto', DB::raw('monto_iva as iva'), 'monto_total', 'created_at as fecha')->get();

        $datos = collect()->merge($boletas)->merge($facturas)->merge($nc)->sortBy('fecha');

        // Generate CSV
        $filename = "libro_ventas_{$anio}_{$mes}.csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];

        $callback = function () use ($datos) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel
            fputcsv($file, ['Folio', 'Tipo', 'Receptor', 'RUT', 'Neto', 'IVA', 'Total', 'Fecha'], ';');
            foreach ($datos as $d) {
                fputcsv($file, [(string)$d->folio, $d->tipo, $d->receptor_nombre, $d->receptor_rut ?? '', $d->monto_neto, $d->iva, $d->monto_total, Carbon::parse($d->fecha)->format('d/m/Y')], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportarLibroCompras(Request $request)
    {
        $mes = $request->mes;
        $anio = $request->anio;
        $inicioMes = Carbon::create($anio, $mes, 1)->toDateString();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $facturas = DB::table('facturas_compra')
            ->leftJoin('categorias_gasto', 'facturas_compra.categoria_id', '=', 'categorias_gasto.id')
            ->whereIn('facturas_compra.estado', ['pendiente', 'pagada'])
            ->whereBetween('facturas_compra.fecha_emision', [$inicioMes, $finMes])
            ->select('facturas_compra.*', 'categorias_gasto.nombre as categoria')
            ->orderBy('facturas_compra.fecha_emision')
            ->get();

        $filename = "libro_compras_{$anio}_{$mes}.csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];

        $callback = function () use ($facturas) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['N° Factura', 'Proveedor', 'RUT', 'Categoría', 'Neto', 'IVA', 'Total', 'Fecha Emisión', 'Estado'], ';');
            foreach ($facturas as $f) {
                fputcsv($file, [$f->numero_factura, $f->proveedor_nombre, $f->proveedor_rut ?? '', $f->categoria ?? '', $f->monto_neto, $f->monto_iva, $f->monto_total, Carbon::parse($f->fecha_emision)->format('d/m/Y'), $f->estado], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ==========================================
    // PRESUPUESTO
    // ==========================================
    public function presupuesto(Request $request)
    {
        $anio = $request->get('anio', now()->year);

        $categorias = DB::table('categorias_gasto')->where('activa', true)->get();

        $presupuestos = DB::table('presupuestos')
            ->where('anio', $anio)
            ->get()
            ->groupBy('categoria_id');

        // Calculate real spending per category per month
        foreach ($categorias as $cat) {
            $cat->meses = [];
            for ($m = 1; $m <= 12; $m++) {
                $inicioMes = Carbon::create($anio, $m, 1)->toDateString();
                $finMes = Carbon::create($anio, $m, 1)->endOfMonth()->toDateString();

                $real = DB::table('facturas_compra')
                    ->where('categoria_id', $cat->id)
                    ->whereIn('estado', ['pendiente', 'pagada'])
                    ->whereBetween('fecha_emision', [$inicioMes, $finMes])
                    ->sum('monto_total');

                $pres = $presupuestos->get($cat->id, collect())->firstWhere('mes', $m);

                $cat->meses[$m] = [
                    'presupuestado' => $pres ? $pres->monto_presupuestado : 0,
                    'real' => (int)$real,
                    'desviacion' => $pres && $pres->monto_presupuestado > 0
                        ? round(($real - $pres->monto_presupuestado) / $pres->monto_presupuestado * 100, 1)
                        : 0,
                ];
            }
            // Valor por defecto para el editor del modal: presupuesto del mes en curso.
            $cat->presupuesto_mensual = $cat->meses[(int) now()->month]['presupuestado'] ?? 0;
        }

        // KPIs
        // Filtro de mes en curso sobre facturas_servicio (fecha = COALESCE pagada_at/periodo/created).
        $finServMesActual = function ($q) {
            return $q->where('estado', 'pagada')
                ->whereRaw('YEAR(COALESCE(pagada_at, periodo_inicio, created_at)) = ? AND MONTH(COALESCE(pagada_at, periodo_inicio, created_at)) = ?', [now()->year, now()->month]);
        };

        // MRR = ingreso recurrente del SaaS del mes en CLP (NO planes.precio en UF ni payments en UF).
        $mrr = (int) $finServMesActual(DB::table('facturas_servicio'))->sum('monto');

        $clientesActivos = (int)DB::table('suscripciones')->where('estado', 'activa')->count();

        $ingMes = (int) $finServMesActual(DB::table('facturas_servicio'))->sum('monto')
            + (int) DB::table('agencia_cobros')->where('estado', 'pagado')->whereMonth('pagado_at', now()->month)->whereYear('pagado_at', now()->year)->sum('monto');
        $egrMes = DB::table('facturas_compra')->whereIn('estado', ['pendiente', 'pagada'])->whereMonth('fecha_emision', now()->month)->whereYear('fecha_emision', now()->year)->sum('monto_total')
            + DB::table('gastos_operativos')->where('activo', true)->sum('monto');
        $margenOperacional = $ingMes > 0 ? round(($ingMes - $egrMes) / $ingMes * 100, 1) : 0;

        $countPagos = $finServMesActual(DB::table('facturas_servicio'))->count();
        $ticketPromedio = $countPagos > 0 ? round($ingMes / $countPagos) : 0;
        
        // Presupuesto vs Real for current month
        $mesActual = now()->month;
        $presupuestoItems = [];
        foreach ($categorias as $cat) {
            $pres = $cat->meses[$mesActual]['presupuestado'] ?? 0;
            $real = $cat->meses[$mesActual]['real'] ?? 0;
            $presupuestoItems[] = [
                'categoria' => $cat->nombre,
                'presupuesto' => $pres,
                'real' => $real,
            ];
        }
        
        // Flujo de caja proyectado (next 3 months)
        $flujoCaja = [];
        for ($i = 0; $i < 3; $i++) {
            $futuro = now()->addMonths($i);
            $flujoCaja[] = [
                'periodo' => $futuro->translatedFormat('F Y'),
                'ingresos' => $mrr,
                'egresos' => (int)$egrMes,
                'saldo' => $mrr - (int)$egrMes,
            ];
        }
        
        return view('finanzas.presupuesto', compact('categorias', 'anio', 'mrr', 'clientesActivos', 'margenOperacional', 'ticketPromedio', 'presupuestoItems', 'flujoCaja'));
    }

    public function storePresupuesto(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
            'presupuesto' => 'required|array',
        ]);

        // El editor define un monto mensual por categoría; se aplica a los 12 meses del año.
        foreach ($request->presupuesto as $catId => $monto) {
            if ($monto === null || $monto === '') continue;
            for ($mes = 1; $mes <= 12; $mes++) {
                DB::table('presupuestos')->updateOrInsert(
                    ['anio' => $request->anio, 'mes' => $mes, 'categoria_id' => (int) $catId],
                    ['monto_presupuestado' => (int) $monto, 'updated_at' => now()]
                );
            }
        }

        return redirect()->route('finanzas.presupuesto', ['anio' => $request->anio])->with('success', 'Presupuesto guardado para todo el año ' . $request->anio . '.');
    }

    // ==========================================
    // GASTOS OPERATIVOS
    // ==========================================
    public function storeGastoOperativo(Request $request)
    {
        $request->validate([
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
        ]);

        DB::table('gastos_operativos')->insert([
            'concepto' => $request->concepto,
            'monto' => (int)$request->monto,
            'categoria_id' => $request->categoria_id,
            'centro_costo_id' => $request->centro_costo_id,
            'dia_pago' => $request->dia_pago ?? 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('finanzas.egresos')->with('success', 'Gasto operativo registrado.');
    }

    public function toggleGastoOperativo($id)
    {
        $gasto = DB::table('gastos_operativos')->where('id', $id)->first();
        DB::table('gastos_operativos')->where('id', $id)->update(['activo' => !$gasto->activo, 'updated_at' => now()]);
        return redirect()->route('finanzas.egresos')->with('success', 'Gasto operativo actualizado.');
    }

    // ==========================================
    // CATEGORÍAS
    // ==========================================
    public function storeCategoria(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:100']);
        DB::table('categorias_gasto')->insert([
            'nombre' => $request->nombre,
            'color' => $request->color ?? '#6366f1',
            'icono' => $request->icono,
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Categoría creada.');
    }

    public function storeCentroCosto(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:100']);
        DB::table('centros_costo')->insert([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Centro de costo creado.');
    }

    // ==========================================
    // HELPERS
    // ==========================================
    private function parseDate($val)
    {
        if (!$val) return null;
        // Try common Chilean date formats
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'm/d/Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $val)->toDateString();
            } catch (\Exception $e) {
                continue;
            }
        }
        try { return Carbon::parse($val)->toDateString(); } catch (\Exception $e) { return null; }
    }

    private function parseAmount($val)
    {
        if (!$val || $val === '' || $val === '-') return 0;
        $val = str_replace(['$', '.', ' '], '', $val);
        $val = str_replace(',', '.', $val);
        return abs((int)round((float)$val));
    }

    public function centrosCosto()
    {
        $centrosCosto = \DB::table("centros_costo")->get();
        // Calculate totals for each centro
        foreach ($centrosCosto as $cc) {
            $cc->total_ingresos = \DB::table("ingresos_manuales")->where("centro_costo_id", $cc->id)->whereMonth("fecha", now()->month)->whereYear("fecha", now()->year)->sum("monto_total");
            $cc->total_egresos = \DB::table("facturas_compra")->where("centro_costo_id", $cc->id)->whereMonth("fecha_emision", now()->month)->whereYear("fecha_emision", now()->year)->sum("monto_total");
        }
        return view("finanzas.centros-costo", compact("centrosCosto"));
    }

    public function storeCentroCostoPage(Request $request)
    {
        \DB::table("centros_costo")->insert([
            "nombre" => $request->nombre,
            "descripcion" => $request->descripcion,
            "color" => $request->color ?? "#3b82f6",
            "presupuesto_mensual" => $request->presupuesto_mensual ?? 0,
            "activo" => true,
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        return redirect()->route("finanzas.centros-costo")->with("success", "Centro de costo creado exitosamente");
    }

    public function matchMovimiento(Request $request)
    {
        $movId = $request->movimiento_id;
        $tipoMatch = $request->tipo_match;
        $matchDesc = $request->match_descripcion;
        $refId = $request->referencia_id;

        if ($tipoMatch !== "manual" && $refId) {
            $matchDesc = ucfirst(str_replace("_", " ", $tipoMatch)) . " #" . $refId;
        }

        \DB::table("movimientos_bancarios")->where("id", $movId)->update([
            "estado_conciliacion" => "conciliado",
            "match_tipo" => $tipoMatch,
            "match_referencia_id" => $refId,
            "match_descripcion" => $matchDesc,
            "updated_at" => now(),
        ]);

        return redirect()->back()->with("success", "Movimiento conciliado exitosamente");
    }

    public function exportarF29(Request $request)
    {
        $mes = $request->mes ?? now()->month;
        $anio = $request->anio ?? now()->year;
        // For now return a simple text response - PDF generation can be added later
        $ivaDebito = \DB::table("boletas")->where("status", "emitida")->whereMonth("created_at", $mes)->whereYear("created_at", $anio)->sum("iva")
            + \DB::table("facturas_emitidas")->where("status", "emitida")->whereMonth("created_at", $mes)->whereYear("created_at", $anio)->sum("iva");
        $ivaCredito = \DB::table("facturas_compra")->whereMonth("fecha_emision", $mes)->whereYear("fecha_emision", $anio)->sum("monto_iva");
        $ivaPagar = max(0, $ivaDebito - $ivaCredito);

        $html = "<h1>Borrador Formulario 29 - " . $mes . "/" . $anio . "</h1>";
        $html .= "<p>IVA Débito Fiscal: $" . number_format($ivaDebito, 0, ",", ".") . "</p>";
        $html .= "<p>IVA Crédito Fiscal: $" . number_format($ivaCredito, 0, ",", ".") . "</p>";
        $html .= "<p><strong>IVA a Pagar: $" . number_format($ivaPagar, 0, ",", ".") . "</strong></p>";
        return response($html)->header("Content-Type", "text/html");
    }

    public function exportarEstadoResultados(Request $request)
    {
        $mes = $request->mes ?? now()->month;
        $anio = $request->anio ?? now()->year;
        $html = "<h1>Estado de Resultados - " . $mes . "/" . $anio . "</h1>";
        $html .= "<p>Reporte en desarrollo</p>";
        return response($html)->header("Content-Type", "text/html");
    }

    public function marcarPagada($id)
    {
        \DB::table("facturas_compra")->where("id", $id)->update([
            "estado" => "pagada",
            "pagada_at" => now(),
            "updated_at" => now(),
        ]);
        return redirect()->back()->with("success", "Factura marcada como pagada");
    }

    /**
     * Cambia el estado de una factura de compra entre 'pendiente' y 'pagada' (toggle desde Egresos).
     * Al marcar 'pagada' fija pagada_at; al volver a 'pendiente' lo limpia.
     */
    public function cambiarEstadoFacturaCompra(Request $request, $id)
    {
        $data = $request->validate(['estado' => 'required|in:pendiente,pagada']);
        DB::table('facturas_compra')->where('id', $id)->update([
            'estado' => $data['estado'],
            'pagada_at' => $data['estado'] === 'pagada' ? now() : null,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', "Factura marcada como {$data['estado']}.");
    }

    /** Importa manualmente las facturas de compra recibidas en Lioren (botón en Egresos). */
    public function importarComprasLioren()
    {
        $r = app(\App\Services\ComprasLiorenImporter::class)->importar();
        if (!$r['ok']) {
            return redirect()->back()->with('error', $r['msg'] ?? 'Error importando facturas de Lioren.');
        }
        $msg = "Sincronización Lioren: {$r['nuevas']} factura(s) de compra nueva(s)";
        $msg .= $r['omitidas'] ? ", {$r['omitidas']} ya estaban registradas." : '.';
        return redirect()->back()->with('success', $msg);
    }

    /** Marca varias facturas de compra como pagadas (ej. cargos automáticos del banco). */
    public function marcarPagadasBulk(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No seleccionaste ninguna factura.');
        }
        $n = DB::table('facturas_compra')->whereIn('id', $ids)->where('estado', 'pendiente')->update([
            'estado'      => 'pagada',
            'pagada_at'   => now(),
            'metodo_pago' => 'Cargo automático',
            'updated_at'  => now(),
        ]);
        return redirect()->back()->with('success', "{$n} factura(s) marcada(s) como pagada(s).");
    }

}
