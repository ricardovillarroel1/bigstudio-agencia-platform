<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class DemoController extends Controller
{
    /**
     * Demo access page - password gate
     */
    public function login()
    {
        if (session('demo_authenticated')) {
            return redirect()->route('demo.dashboard');
        }
        return view('demo.login');
    }

    /**
     * Verify demo password
     */
    public function authenticate(Request $request)
    {
        $request->validate(['password' => 'required']);
        
        // Clave de acceso demo (configurable)
        $demoPassword = config('app.demo_password', 'demo2026');
        
        if ($request->password === $demoPassword) {
            session(['demo_authenticated' => true]);
            return redirect()->route('demo.dashboard');
        }
        
        return back()->withErrors(['password' => 'Clave de acceso incorrecta.']);
    }

    /**
     * Logout demo
     */
    public function logout()
    {
        session()->forget('demo_authenticated');
        return redirect()->route('demo.login');
    }

    /**
     * Dashboard
     */
    public function dashboard()
    {
        return view('demo.dashboard');
    }

    /**
     * Planes disponibles
     */
    public function planes()
    {
        $planes = collect([
            (object)[
                'id' => 1,
                'nombre' => 'PLAN INICIA 0.8+IVA',
                'precio' => 0.8,
                'monthly_order_limit' => 200,
                'descripcion' => 'Ideal para negocios que están comenzando con pocas ventas mensuales.',
                'features' => ['Hasta 200 documentos/ciclo', 'Boletas y Facturas', 'Notas de Crédito', 'Soporte por chat'],
            ],
            (object)[
                'id' => 2,
                'nombre' => 'PLAN AVANZA 1 UF +IVA',
                'precio' => 1.0,
                'monthly_order_limit' => 600,
                'descripcion' => 'Para negocios en crecimiento con volumen moderado de ventas.',
                'features' => ['Hasta 600 documentos/ciclo', 'Boletas y Facturas', 'Notas de Crédito', 'Soporte prioritario', 'Inventario sincronizado'],
            ],
            (object)[
                'id' => 3,
                'nombre' => 'PLAN PRO 1.7 UF +IVA',
                'precio' => 1.7,
                'monthly_order_limit' => 2000,
                'descripcion' => 'Para negocios consolidados con alto volumen de ventas.',
                'features' => ['Hasta 2.000 documentos/ciclo', 'Boletas y Facturas', 'Notas de Crédito', 'Soporte prioritario', 'Inventario sincronizado', 'Correo masivo'],
            ],
        ]);

        return view('demo.planes', compact('planes'));
    }

    /**
     * Planes activos
     */
    public function planesActivos()
    {
        $suscripcion = (object)[
            'id' => 1,
            'estado' => 'activa',
            'fecha_inicio' => Carbon::now()->subDays(15),
            'fecha_fin' => Carbon::now()->addDays(15),
            'proximo_pago' => Carbon::now()->addDays(15),
            'plan' => (object)[
                'nombre' => 'PLAN PRO 1.7 UF +IVA',
                'precio' => 1.7,
                'monthly_order_limit' => 2000,
            ],
        ];

        $documentosEmitidos = [
            'boletas' => 142,
            'facturas' => 38,
            'notas_credito' => 7,
            'total' => 187,
            'ciclo_inicio' => $suscripcion->fecha_inicio,
            'ciclo_fin' => $suscripcion->fecha_fin,
        ];

        $pagos = collect([
            (object)[
                'created_at' => Carbon::now()->subDays(15),
                'concepto' => 'Suscripción PLAN PRO',
                'plan' => (object)['nombre' => 'PLAN PRO 1.7 UF +IVA'],
                'suscripcion' => (object)['plan' => (object)['nombre' => 'PLAN PRO 1.7 UF +IVA']],
                'periodo_inicio' => Carbon::now()->subDays(15)->format('Y-m-d'),
                'periodo_fin' => Carbon::now()->addDays(15)->format('Y-m-d'),
                'monto' => 68500,
                'amount' => 68500,
                'status' => 'pagado',
                'estado' => 'pagado',
            ],
            (object)[
                'created_at' => Carbon::now()->subDays(45),
                'concepto' => 'Suscripción PLAN PRO',
                'plan' => (object)['nombre' => 'PLAN PRO 1.7 UF +IVA'],
                'suscripcion' => (object)['plan' => (object)['nombre' => 'PLAN PRO 1.7 UF +IVA']],
                'periodo_inicio' => Carbon::now()->subDays(45)->format('Y-m-d'),
                'periodo_fin' => Carbon::now()->subDays(15)->format('Y-m-d'),
                'monto' => 67200,
                'amount' => 67200,
                'status' => 'pagado',
                'estado' => 'pagado',
            ],
        ]);

        $valorUF = 38500.00;

        return view('demo.planes-activos', compact('suscripcion', 'pagos', 'documentosEmitidos', 'valorUF'));
    }

    /**
     * Documentos emitidos
     */
    public function documentosEmitidos(Request $request)
    {
        $documentos = collect([
            (object)['id' => 1, 'source' => 'boleta', 'tipodoc' => 39, 'folio' => 6480, 'receptor_nombre' => 'María González', 'receptor_rut' => '12.345.678-9', 'monto_total' => 45990, 'status' => 'emitida', 'shopify_order_number' => '2670', 'shopify_order_id' => '5001', 'created_at' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s')],
            (object)['id' => 2, 'source' => 'boleta', 'tipodoc' => 39, 'folio' => 6479, 'receptor_nombre' => 'Pedro Soto', 'receptor_rut' => '11.222.333-4', 'monto_total' => 128500, 'status' => 'emitida', 'shopify_order_number' => '2669', 'shopify_order_id' => '5002', 'created_at' => Carbon::now()->subHours(5)->format('Y-m-d H:i:s')],
            (object)['id' => 3, 'source' => 'factura', 'tipodoc' => 33, 'folio' => 245, 'receptor_nombre' => 'Comercial Andina SpA', 'receptor_rut' => '76.543.210-K', 'monto_total' => 892000, 'status' => 'emitida', 'shopify_order_number' => '2668', 'shopify_order_id' => '5003', 'created_at' => Carbon::now()->subHours(8)->format('Y-m-d H:i:s')],
            (object)['id' => 4, 'source' => 'nota_credito', 'tipodoc' => 61, 'folio' => 128, 'receptor_nombre' => 'Ana Muñoz', 'receptor_rut' => '15.678.901-2', 'monto_total' => 35990, 'status' => 'emitida', 'shopify_order_number' => '2665', 'shopify_order_id' => '5004', 'created_at' => Carbon::now()->subDay()->format('Y-m-d H:i:s')],
            (object)['id' => 5, 'source' => 'boleta', 'tipodoc' => 39, 'folio' => 6478, 'receptor_nombre' => 'Carlos Fuentes', 'receptor_rut' => '9.876.543-1', 'monto_total' => 67890, 'status' => 'emitida', 'shopify_order_number' => '2667', 'shopify_order_id' => '5005', 'created_at' => Carbon::now()->subDay()->subHours(3)->format('Y-m-d H:i:s')],
            (object)['id' => 6, 'source' => 'boleta', 'tipodoc' => 39, 'folio' => 6477, 'receptor_nombre' => 'Laura Díaz', 'receptor_rut' => '14.567.890-3', 'monto_total' => 234500, 'status' => 'emitida', 'shopify_order_number' => '2666', 'shopify_order_id' => '5006', 'created_at' => Carbon::now()->subDays(2)->format('Y-m-d H:i:s')],
            (object)['id' => 7, 'source' => 'factura', 'tipodoc' => 33, 'folio' => 244, 'receptor_nombre' => 'Importadora del Sur Ltda', 'receptor_rut' => '77.888.999-0', 'monto_total' => 1450000, 'status' => 'emitida', 'shopify_order_number' => '2664', 'shopify_order_id' => '5007', 'created_at' => Carbon::now()->subDays(2)->subHours(5)->format('Y-m-d H:i:s')],
            (object)['id' => 8, 'source' => 'boleta', 'tipodoc' => 39, 'folio' => 6476, 'receptor_nombre' => 'Roberto Araya', 'receptor_rut' => '16.789.012-4', 'monto_total' => 89990, 'status' => 'emitida', 'shopify_order_number' => '2663', 'shopify_order_id' => '5008', 'created_at' => Carbon::now()->subDays(3)->format('Y-m-d H:i:s')],
            (object)['id' => 9, 'source' => 'boleta', 'tipodoc' => 39, 'folio' => 6475, 'receptor_nombre' => 'Francisca Rojas', 'receptor_rut' => '13.456.789-5', 'monto_total' => 156700, 'status' => 'emitida', 'shopify_order_number' => '2662', 'shopify_order_id' => '5009', 'created_at' => Carbon::now()->subDays(3)->subHours(7)->format('Y-m-d H:i:s')],
            (object)['id' => 10, 'source' => 'nota_credito', 'tipodoc' => 61, 'folio' => 127, 'receptor_nombre' => 'Comercial Andina SpA', 'receptor_rut' => '76.543.210-K', 'monto_total' => 178500, 'status' => 'emitida', 'shopify_order_number' => '2660', 'shopify_order_id' => '5010', 'created_at' => Carbon::now()->subDays(4)->format('Y-m-d H:i:s')],
        ]);

        // Filtro por tipo
        if ($request->filled('tipo')) {
            $tipo = $request->tipo;
            if ($tipo === 'boleta') $documentos = $documentos->filter(fn($d) => $d->tipodoc == 39);
            elseif ($tipo === 'factura') $documentos = $documentos->filter(fn($d) => $d->tipodoc == 33);
            elseif ($tipo === 'nota_credito') $documentos = $documentos->filter(fn($d) => $d->tipodoc == 61);
        }

        $stats = [
            'total' => $documentos->count(),
            'boletas' => $documentos->filter(fn($d) => $d->tipodoc == 39)->count(),
            'facturas' => $documentos->filter(fn($d) => $d->tipodoc == 33)->count(),
            'notas_credito' => $documentos->filter(fn($d) => $d->tipodoc == 61)->count(),
            'monto_total' => $documentos->sum('monto_total'),
        ];

        $cicloInfo = [
            'inicio' => Carbon::now()->subDays(15),
            'fin' => Carbon::now()->addDays(15),
            'emitidos' => 187,
            'limite' => 2000,
            'disponibles' => 1813,
            'porcentaje' => 9,
            'plan' => 'PLAN PRO 1.7 UF +IVA',
        ];

        return view('demo.documentos-emitidos', compact('documentos', 'stats', 'cicloInfo'));
    }

    /**
     * Pedidos
     */
    public function pedidos()
    {
        return view('demo.pedidos');
    }

    /**
     * Facturas (del servicio al cliente)
     */
    public function facturas()
    {
        $facturas = collect([
            (object)[
                'id' => 1,
                'folio' => 'FS-0045',
                'fecha' => Carbon::now()->subDays(15),
                'created_at' => Carbon::now()->subDays(15),
                'concepto' => 'Suscripción PLAN PRO 1.7 UF +IVA',
                'plan' => (object)['nombre' => 'PLAN PRO 1.7 UF +IVA'],
                'suscripcion' => (object)['plan' => (object)['nombre' => 'PLAN PRO 1.7 UF +IVA']],
                'monto' => 1.7,
                'moneda' => 'UF',
                'total_clp' => 65450,
                'neto_clp' => 55000,
                'iva_clp' => 10450,
                'status' => 'pagada',
                'estado' => 'pagada',
                'periodo_inicio' => Carbon::now()->subDays(15)->format('Y-m-d'),
                'periodo_fin' => Carbon::now()->addDays(15)->format('Y-m-d'),
            ],
            (object)[
                'id' => 2,
                'folio' => 'FS-0038',
                'fecha' => Carbon::now()->subDays(45),
                'created_at' => Carbon::now()->subDays(45),
                'concepto' => 'Suscripción PLAN PRO 1.7 UF +IVA',
                'plan' => (object)['nombre' => 'PLAN PRO 1.7 UF +IVA'],
                'suscripcion' => (object)['plan' => (object)['nombre' => 'PLAN PRO 1.7 UF +IVA']],
                'monto' => 1.7,
                'moneda' => 'UF',
                'total_clp' => 64800,
                'neto_clp' => 54454,
                'iva_clp' => 10346,
                'status' => 'pagada',
                'estado' => 'pagada',
                'periodo_inicio' => Carbon::now()->subDays(45)->format('Y-m-d'),
                'periodo_fin' => Carbon::now()->subDays(15)->format('Y-m-d'),
            ],
        ]);

        return view('demo.facturas', compact('facturas'));
    }

    /**
     * Facturas de servicio
     */
    public function facturasServicio()
    {
        return view('demo.facturas-servicio');
    }

    /**
     * Cobros pendientes
     */
    public function cobrosPendientes()
    {
        return view('demo.cobros-pendientes');
    }

    /**
     * Estados de solicitud
     */
    public function estadosSolicitud()
    {
        return view('demo.estados-solicitud');
    }

    /**
     * Inventario
     */
    public function inventario()
    {
        return view('demo.inventario');
    }

    /**
     * Chats
     */
    public function chats()
    {
        return view('demo.chats');
    }
}
