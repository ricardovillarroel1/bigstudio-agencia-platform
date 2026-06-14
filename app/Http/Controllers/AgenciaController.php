<?php

namespace App\Http\Controllers;

use App\Models\AgenciaCliente;
use App\Models\AgenciaServicio;
use App\Models\AgenciaClienteServicio;
use App\Models\AgenciaSuscripcion;
use App\Models\AgenciaSuscripcionItem;
use App\Models\AgenciaCobro;
use App\Models\IntegracionConfig;
use App\Models\AgenciaCorreo;
use App\Models\AgenciaCotizacion;
use App\Models\AgenciaCotizacionItem;
use App\Models\AgenciaTarea;
use App\Models\AgenciaTareaComparticion;
use App\Models\AgenciaTareaComentario;
use App\Models\AgenciaTareaArchivo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AgenciaController extends Controller
{
    // ==========================================
    // DASHBOARD
    // ==========================================
    public function dashboard(Request $request)
    {
        $totalClientes = AgenciaCliente::where('estado', 'activo')->count();
        $totalServicios = AgenciaServicio::where('activo', true)->count();
        $suscripcionesActivas = AgenciaSuscripcion::where('estado', 'activa')->count();
        $cobrosPendientes = AgenciaCobro::where('estado', 'pendiente')->count();
        $tareasPendientes = AgenciaTarea::whereIn('estado', AgenciaTarea::ESTADOS_PENDIENTES)->count();
        $ingresosMes = AgenciaCobro::where('estado', 'pagado')
            ->whereMonth('pagado_at', now()->month)
            ->whereYear('pagado_at', now()->year)
            ->sum('monto');
        
        // Date filter for dashboard
        $periodo = $request->input('periodo', 'mes');
        $ingresosFiltro = 0;
        $labelPeriodo = 'Este Mes';
        
        $ingresosQuery = AgenciaCobro::where('estado', 'pagado');
        if ($periodo === 'dia') {
            $fecha = $request->input('fecha', date('Y-m-d'));
            $ingresosQuery->whereDate('pagado_at', $fecha);
            $labelPeriodo = 'Dia: ' . \Carbon\Carbon::parse($fecha)->format('d/m/Y');
        } elseif ($periodo === 'mes') {
            $mes = $request->input('mes', date('Y-m'));
            $parts = explode('-', $mes);
            $ingresosQuery->whereYear('pagado_at', $parts[0])->whereMonth('pagado_at', $parts[1]);
            $labelPeriodo = 'Mes: ' . \Carbon\Carbon::parse($mes . '-01')->translatedFormat('F Y');
        } elseif ($periodo === 'anio') {
            $anio = $request->input('anio', date('Y'));
            $ingresosQuery->whereYear('pagado_at', $anio);
            $labelPeriodo = 'Año: ' . $anio;
        }
        $ingresosFiltro = $ingresosQuery->sum('monto');
        $cobrosDelPeriodo = (clone $ingresosQuery)->count();
        
        $proximosCobros = AgenciaSuscripcion::where('estado', 'activa')
            ->where('proximo_cobro', '<=', now()->addDays(7))
            ->with('cliente')
            ->orderBy('proximo_cobro')
            ->take(5)
            ->get();
        
        // Paginated ultimos cobros (5 per page)
        $ultimosCobros = AgenciaCobro::with('cliente')
            ->orderByDesc('created_at')
            ->paginate(5, ['*'], 'page');
        
        // ===== Stats onboardings =====
        $onboardingsEnProgreso = \App\Models\AgenciaOnboardingProyecto::where('estado', 'en_progreso')->count();
        $onboardingsNoIniciados = \App\Models\AgenciaOnboardingProyecto::where('estado', 'no_iniciado')->count();
        $onboardingsCompletados30d = \App\Models\AgenciaOnboardingProyecto::where('estado', 'completado')->where('fecha_completado', '>=', now()->subDays(30))->count();
        $onboardingsRecientes = \App\Models\AgenciaOnboardingProyecto::with(['cliente', 'plantilla'])->whereIn('estado', ['no_iniciado', 'en_progreso'])->orderByDesc('updated_at')->take(4)->get();

        return view('agencia.dashboard', compact(
            'totalClientes', 'totalServicios', 'suscripcionesActivas',
            'cobrosPendientes', 'tareasPendientes', 'ingresosMes', 'proximosCobros', 'ultimosCobros',
            'ingresosFiltro', 'labelPeriodo', 'cobrosDelPeriodo',
            'onboardingsEnProgreso', 'onboardingsNoIniciados', 'onboardingsCompletados30d', 'onboardingsRecientes'
        ));
    }
    // ==========================================
    // CLIENTES
    // ==========================================

    public function clientes(Request $request)
    {
        $query = AgenciaCliente::query();
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%")
                  ->orWhere('rut', 'like', "%{$buscar}%")
                  ->orWhere('razon_social', 'like', "%{$buscar}%");
            });
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        $clientes = $query->withCount(['servicios', 'suscripciones' => function($q) {
            $q->where('estado', 'activa');
        }, 'cobros' => function($q) {
            $q->where('estado', 'pendiente');
        }, 'tareas as tareas_pendientes_count' => function($q) {
            $q->whereIn('estado', \App\Models\AgenciaTarea::ESTADOS_PENDIENTES);
        }])->orderByDesc('created_at')->paginate(20);

        return view('agencia.clientes.index', compact('clientes'));
    }

    public function clienteCreate()
    {
        return view('agencia.clientes.create');
    }

    public function clienteStore(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'proyecto' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'rut' => 'nullable|string|max:20',
            'razon_social' => 'nullable|string|max:255',
            'giro' => 'nullable|string|max:255',
            'direccion_fiscal' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'comuna' => 'nullable|string|max:100',
            'notas' => 'nullable|string',
        ]);

        AgenciaCliente::create($request->all());
        return redirect()->route('agencia.clientes')->with('success', 'Cliente creado exitosamente.');
    }

    public function clienteEdit(AgenciaCliente $cliente)
    {
        $cliente->load(['servicios.servicio', 'suscripciones', 'cobros' => function($q) {
            $q->orderByDesc('created_at')->take(10);
        }]);
        $serviciosDisponibles = AgenciaServicio::where('activo', true)->get();
        return view('agencia.clientes.edit', compact('cliente', 'serviciosDisponibles'));
    }

    public function clienteUpdate(Request $request, AgenciaCliente $cliente)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'proyecto' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'rut' => 'nullable|string|max:20',
            'razon_social' => 'nullable|string|max:255',
            'giro' => 'nullable|string|max:255',
            'direccion_fiscal' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'comuna' => 'nullable|string|max:100',
            'notas' => 'nullable|string',
            'estado' => 'nullable|in:activo,inactivo',
        ]);

        $cliente->update($request->all());
        return redirect()->route('agencia.clientes.edit', $cliente)->with('success', 'Cliente actualizado.');
    }

    public function clienteDelete(AgenciaCliente $cliente)
    {
        $cliente->delete();
        return redirect()->route('agencia.clientes')->with('success', 'Cliente eliminado.');
    }

    /**
     * Ver detalle de un cliente (Task 3 - fix address display)
     */
    public function clienteVer(AgenciaCliente $cliente)
    {
        $cliente->load(['servicios.servicio', 'suscripciones' => function($q) {
            $q->where('estado', 'activa');
        }, 'cobros' => function($q) {
            $q->where('estado', 'pendiente');
        }]);
        
        return response()->json([
            'nombre' => $cliente->nombre,
            'razon_social' => $cliente->razon_social,
            'rut' => $cliente->rut,
            'giro' => $cliente->giro,
            'email' => $cliente->email,
            'telefono' => $cliente->telefono,
            'direccion_fiscal' => $cliente->direccion_fiscal,
            'comuna' => $cliente->comuna,
            'ciudad' => $cliente->ciudad,
            'region' => $cliente->region,
            'estado' => $cliente->estado,
            'notas' => $cliente->notas,
            'servicios_count' => $cliente->servicios->count(),
            'suscripciones_count' => $cliente->suscripciones->count(),
            'cobros_count' => $cliente->cobros->count(),
            'created_at' => $cliente->created_at?->format('d/m/Y'),
        ]);
    }

    // ==========================================
    // SERVICIOS (Catálogo)
    // ==========================================
    public function servicios()
    {
        $servicios = AgenciaServicio::withCount('clienteServicios')->orderByDesc('created_at')->get();
        $valorUF = $this->obtenerValorUF();
        return view('agencia.servicios.index', compact('servicios', 'valorUF'));
    }

    public function servicioStore(Request $request)
    {
        $moneda = $request->input('moneda', 'CLP');
        if ($moneda === 'UF') {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio_uf' => 'required|numeric|min:0',
                'periodicidad' => 'required|in:mensual,trimestral,semestral,anual,unico',
            ]);
            $valorUF = $this->obtenerValorUF();
            $data = $request->all();
            $data['precio'] = intval(round($request->precio_uf * $valorUF));
            $data['moneda'] = 'UF';
            AgenciaServicio::create($data);
        } else {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'periodicidad' => 'required|in:mensual,trimestral,semestral,anual,unico',
            ]);
            $data = $request->all();
            $data['moneda'] = 'CLP';
            AgenciaServicio::create($data);
        }
        return redirect()->route('agencia.servicios')->with('success', 'Servicio creado exitosamente.');
    }

    public function servicioUpdate(Request $request, AgenciaServicio $servicio)
    {
        $moneda = $request->input('moneda', 'CLP');
        if ($moneda === 'UF') {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio_uf' => 'required|numeric|min:0',
                'periodicidad' => 'required|in:mensual,trimestral,semestral,anual,unico',
            ]);
            $valorUF = $this->obtenerValorUF();
            $data = $request->all();
            $data['precio'] = intval(round($request->precio_uf * $valorUF));
            $data['moneda'] = 'UF';
            $servicio->update($data);
        } else {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'periodicidad' => 'required|in:mensual,trimestral,semestral,anual,unico',
            ]);
            $data = $request->all();
            $data['moneda'] = 'CLP';
            $data['precio_uf'] = null;
            $servicio->update($data);
        }
        return redirect()->route('agencia.servicios')->with('success', 'Servicio actualizado.');
    }

    public function servicioToggle(AgenciaServicio $servicio)
    {
        $servicio->update(['activo' => !$servicio->activo]);
        return redirect()->route('agencia.servicios')->with('success', 'Estado del servicio actualizado.');
    }

    public function servicioDelete(AgenciaServicio $servicio)
    {
        $servicio->delete();
        return redirect()->route('agencia.servicios')->with('success', 'Servicio eliminado.');
    }

    // ==========================================
    // ASIGNACIONES (Cliente-Servicio)
    // ==========================================
    public function asignacionStore(Request $request)
    {
        $request->validate([
            'agencia_cliente_id' => 'required|exists:agencia_clientes,id',
            'agencia_servicio_id' => 'required|exists:agencia_servicios,id',
            'precio_acordado' => 'required|numeric|min:0',
            'inversion_publicidad' => 'nullable|numeric|min:0',
            'plataforma_publicidad' => 'nullable|string|max:100',
            'notas_internas' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
        ]);

        $servicio = AgenciaServicio::find($request->agencia_servicio_id);
        $data = $request->all();
        if (empty($data['fecha_inicio'])) {
            $data['fecha_inicio'] = now()->toDateString();
        }

        $asignacion = AgenciaClienteServicio::create($data);

        return redirect()->route('agencia.clientes.edit', $request->agencia_cliente_id)
            ->with('success', "Servicio '{$servicio->nombre}' asignado al cliente.");
    }

    public function asignacionUpdate(Request $request, AgenciaClienteServicio $asignacion)
    {
        $request->validate([
            'precio_acordado' => 'required|numeric|min:0',
            'inversion_publicidad' => 'nullable|numeric|min:0',
            'plataforma_publicidad' => 'nullable|string|max:100',
            'notas_internas' => 'nullable|string',
            'estado' => 'required|in:activo,pausado,cancelado',
        ]);

        $asignacion->update($request->all());
        return redirect()->route('agencia.clientes.edit', $asignacion->agencia_cliente_id)
            ->with('success', 'Asignación actualizada.');
    }

    public function asignacionDelete(AgenciaClienteServicio $asignacion)
    {
        $clienteId = $asignacion->agencia_cliente_id;
        $asignacion->delete();
        return redirect()->route('agencia.clientes.edit', $clienteId)
            ->with('success', 'Asignación eliminada.');
    }

    // ==========================================
    // SUSCRIPCIONES / PLANES
    // ==========================================
    public function suscripciones(Request $request)
    {
        $query = AgenciaSuscripcion::with(['cliente', 'clienteServicio.servicio', 'items']);
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('cliente_id')) {
            $query->where('agencia_cliente_id', $request->cliente_id);
        }
        $suscripciones = $query->orderByDesc('created_at')->paginate(20);
        $clientes = AgenciaCliente::where('estado', 'activo')->orderBy('nombre')->get();
        $serviciosCliente = AgenciaClienteServicio::with(['cliente', 'servicio'])
            ->where('estado', 'activo')
            ->get();

        $servicios = AgenciaServicio::where('activo', true)->orderBy("nombre")->get();
        return view('agencia.suscripciones.index', compact('suscripciones', 'clientes', 'serviciosCliente', 'servicios'));
    }

    public function suscripcionStore(Request $request)
    {
        $request->validate([
            'agencia_cliente_id' => 'required|exists:agencia_clientes,id',
            'monto' => 'required|numeric|min:1',
            'periodicidad' => 'required|in:mensual,trimestral,semestral,anual',
            'fecha_inicio' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string|max:500',
            'items.*.monto_neto' => 'required|numeric|min:0',
        ]);

        $periodicidadDias = [
            'mensual' => 30,
            'trimestral' => 90,
            'semestral' => 180,
            'anual' => 365,
        ];
        $dias = $periodicidadDias[$request->periodicidad];
        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio);

        // Build concepto from items
        $conceptos = [];
        $totalNeto = 0;
        foreach ($request->items as $item) {
            if (!empty($item['descripcion']) && ($item['monto_neto'] ?? 0) > 0) {
                $conceptos[] = $item['descripcion'];
                $totalNeto += intval($item['monto_neto']);
            }
        }
        $concepto = implode(' + ', $conceptos) ?: 'Servicios de agencia';
        $montoTotal = $totalNeto + intval(round($totalNeto * 0.19));

        $suscripcion = AgenciaSuscripcion::create([
            'agencia_cliente_id' => $request->agencia_cliente_id,
            'concepto' => $concepto,
            'descripcion' => null,
            'monto' => $montoTotal,
            'periodicidad' => $request->periodicidad,
            'estado' => 'activa',
            'fecha_inicio' => $fechaInicio,
            "proximo_cobro" => $fechaInicio->copy()->addMonth()->startOfMonth(),
            'facturacion_automatica' => $request->boolean('facturacion_automatica', true),
            'dias_anticipacion_factura' => $request->input('dias_anticipacion_factura', 5),
        ]);

        // Save individual items
        foreach ($request->items as $item) {
            if (!empty($item['descripcion']) && ($item['monto_neto'] ?? 0) > 0) {
                AgenciaSuscripcionItem::create([
                    'agencia_suscripcion_id' => $suscripcion->id,
                    'agencia_servicio_id' => !empty($item['servicio_id']) ? $item['servicio_id'] : null,
                    'descripcion' => $item['descripcion'],
                    'monto_neto' => intval($item['monto_neto']),
                ]);
            }
        }

        // === Generar cobro pendiente inmediato al crear suscripcion ===
        $msg = 'Suscripcion creada con ' . count($conceptos) . ' servicio(s).';
        try {
            $cobro = AgenciaCobro::create([
                'agencia_cliente_id' => $request->agencia_cliente_id,
                'agencia_suscripcion_id' => $suscripcion->id,
                'concepto' => $concepto,
                'monto' => $montoTotal,
                'estado' => 'pendiente',
                'vence_at' => $fechaInicio,
            ]);

            // Emitir factura Lioren si facturacion automatica esta activa
            if ($request->boolean('facturacion_automatica', true)) {
                try {
                    $this->emitirFacturaAgencia($cobro);
                    $cobro->refresh();
                    if ($cobro->factura_estado === 'emitida') {
                        $msg .= ' Factura N ' . $cobro->lioren_folio . ' emitida.';
                    }
                } catch (\Exception $e) {
                    \Log::error('Error emitiendo factura en suscripcion store: ' . $e->getMessage());
                    $msg .= ' (Factura no emitida: ' . mb_substr($e->getMessage(), 0, 80) . ')';
                }
            }

            // Enviar correo de cobro al cliente
            try {
                $this->enviarCorreoCobro($cobro);
                $msg .= ' Correo de cobro enviado.';
            } catch (\Exception $e) {
                \Log::error('Error enviando correo cobro suscripcion: ' . $e->getMessage());
            }

            // Marcar factura del ciclo como emitida
            $suscripcion->update(['factura_ciclo_emitida' => true]);
        } catch (\Exception $e) {
            \Log::error('Error generando cobro para suscripcion: ' . $e->getMessage());
            $msg .= ' (Error al generar cobro: ' . mb_substr($e->getMessage(), 0, 80) . ')';
        }

        return redirect()->route('agencia.suscripciones')->with('success', $msg);
    }

    public function suscripcionCancelar(AgenciaSuscripcion $suscripcion)
    {
        $suscripcion->update(['estado' => 'cancelada']);
        
        // Try to emit NC for the last cobro of this subscription that has a factura
        $ultimoCobro = AgenciaCobro::where('agencia_suscripcion_id', $suscripcion->id)
            ->where('factura_estado', 'emitida')
            ->where('estado', 'pagado')
            ->orderByDesc('created_at')
            ->first();
        
        $ncMsg = '';
        if ($ultimoCobro) {
            try {
                $this->emitirNotaCreditoAgencia($ultimoCobro, 'Anulacion de suscripcion');
                $ultimoCobro->update(['estado' => 'anulado']);
                $ncMsg = ' Se emitio Nota de Credito para el ultimo cobro.';
            } catch (\Exception $e) {
                $ncMsg = ' No se pudo emitir NC: ' . $e->getMessage();
                \Log::error('NC suscripcion error: ' . $e->getMessage());
            }
        }
        
        return redirect()->route('agencia.suscripciones')->with('success', 'Suscripcion cancelada.' . $ncMsg);
    }

    public function suscripcionReactivar(AgenciaSuscripcion $suscripcion)
    {
        $periodicidadDias = [
            'mensual' => 30, 'trimestral' => 90, 'semestral' => 180, 'anual' => 365,
        ];
        $dias = $periodicidadDias[$suscripcion->periodicidad] ?? 30;

        $suscripcion->update([
            'estado' => 'activa',
            'fecha_inicio' => now(),
            "proximo_cobro" => now()->addMonth()->startOfMonth(),
            'reminder_sent' => false,
            'factura_ciclo_emitida' => false,
        ]);
        return redirect()->route('agencia.suscripciones')->with('success', 'Suscripción reactivada.');
    }

    /**
     * Pausar / Reanudar una suscripcion (toggle activa <-> pausada).
     * Pausada: el comando de facturacion automatica la ignora (solo factura 'activa').
     * No toca facturas ni cobros ya emitidos.
     */
    public function suscripcionPausar(AgenciaSuscripcion $suscripcion)
    {
        if ($suscripcion->estado === 'pausada') {
            // Reanudar: si el proximo_cobro quedo en el pasado, moverlo al proximo mes.
            $proximo = $suscripcion->proximo_cobro;
            if (!$proximo || $proximo->isPast()) {
                $proximo = now()->addMonth()->startOfMonth();
            }
            $suscripcion->update(['estado' => 'activa', 'proximo_cobro' => $proximo]);
            return redirect()->route('agencia.suscripciones')->with('success', 'Suscripción reanudada.');
        }

        if ($suscripcion->estado === 'activa') {
            $suscripcion->update(['estado' => 'pausada']);
            return redirect()->route('agencia.suscripciones')->with('success', 'Suscripción pausada. No se generaran cobros automaticos hasta reanudarla.');
        }

        return redirect()->route('agencia.suscripciones')->with('error', 'Solo se puede pausar o reanudar una suscripcion activa o pausada.');
    }

    /**
     * Eliminar una suscripcion. Si NO tiene cobros asociados se borra definitivamente;
     * si tiene historial de cobros se marca como 'cancelada' (se conserva el historial).
     * No emite Nota de Credito (eliminar != reembolsar).
     */
    public function suscripcionEliminar(AgenciaSuscripcion $suscripcion)
    {
        $tieneCobros = AgenciaCobro::where('agencia_suscripcion_id', $suscripcion->id)->exists();

        if ($tieneCobros) {
            $suscripcion->update(['estado' => 'cancelada']);
            return redirect()->route('agencia.suscripciones')->with('success', 'Suscripción dada de baja. Tenia historial de cobros, por lo que se conservo como cancelada.');
        }

        $suscripcion->items()->delete();
        $suscripcion->delete();
        return redirect()->route('agencia.suscripciones')->with('success', 'Suscripción eliminada definitivamente.');
    }

    /**
     * Confirmar pago de suscripcion (manual - transferencia)
     * Marca el ultimo cobro pendiente como pagado, emite factura y envia correo de confirmacion
     */
    public function suscripcionConfirmarPago(AgenciaSuscripcion $suscripcion, Request $request)
    {
        $metodo = $request->input("metodo_pago", "transferencia");
        
        // Find the latest pending cobro for this suscripcion
        $cobro = AgenciaCobro::where("agencia_suscripcion_id", $suscripcion->id)
            ->where("estado", "pendiente")
            ->orderByDesc("created_at")
            ->first();
        
        if (!$cobro) {
            return redirect()->route("agencia.suscripciones")->with("error", "No hay cobros pendientes para esta suscripcion.");
        }
        
        // Mark as paid
        $cobro->update([
            "estado" => "pagado",
            "pagado_at" => now(),
            "metodo_pago" => $metodo,
        ]);
        
        // Emit factura if facturacion_automatica is enabled
        $facturaMsg = "";
        if ($suscripcion->facturacion_automatica) {
            try {
                $this->emitirFacturaAgencia($cobro);
                $cobro->refresh();
                if ($cobro->factura_estado === "emitida") {
                    $facturaMsg = " Factura emitida (Folio #{$cobro->lioren_folio}).";
                }
            } catch (\Exception $e) {
                $facturaMsg = " Error al emitir factura: " . $e->getMessage();
                \Log::error("Error factura suscripcion pago: " . $e->getMessage());
            }
        }
        
        // Send confirmation email
        try {
            $cliente = $cobro->cliente;
            if ($cliente && $cliente->email) {
                $montoFormateado = "$" . number_format($cobro->monto, 0, ",", ".");
                $metodoPagoTexto = $metodo === "flow" ? "Flow (Pago en linea)" : "Transferencia bancaria";
                
                $contenidoHtml = "
                <div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;\">
                    <div style=\"background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;\">
                        <img src=\"https://bigstudio.cl/wp-content/uploads/2024/06/Mesa-de-trabajo-1-copia-3.png\" alt=\"Big Studio\" style=\"width: 80px; height: auto; margin-bottom: 15px;\">
                        <p style=\"color: #FFC107; margin: 0; font-size: 13px; font-weight: bold; letter-spacing: 2px;\">AGENCIA DE MARKETING DIGITAL</p>
                        <div style=\"width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;\"></div>
                    </div>
                    <div style=\"background: #FFC107; padding: 14px 20px; text-align: center;\">
                        <p style=\"color: #0A0A0A; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;\">&#10003; Pago Confirmado</p>
                    </div>
                    <div style=\"padding: 30px 30px 20px; background: #0A0A0A;\">
                        <p style=\"font-size: 15px; color: #FFFFFF; margin: 0 0 15px;\">Hola <strong style=\"color: #FFC107;\">{$cliente->nombre}</strong>,</p>
                        <p style=\"font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;\">Tu pago ha sido confirmado exitosamente.</p>
                        <div style=\"background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;\">
                            <table style=\"width: 100%; border-collapse: collapse;\">
                                <tr>
                                    <td style=\"padding: 10px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;\">Concepto</td>
                                    <td style=\"padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;\">{$cobro->concepto}</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 10px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;\">Monto</td>
                                    <td style=\"padding: 10px 0; font-weight: bold; text-align: right; font-size: 18px; color: #FFC107; border-bottom: 1px solid #222222;\">{$montoFormateado} CLP</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 10px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;\">Metodo de Pago</td>
                                    <td style=\"padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;\">{$metodoPagoTexto}</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 10px 0; color: #888888; font-size: 13px;\">Fecha</td>
                                    <td style=\"padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF;\">" . now()->format("d/m/Y") . "</td>
                                </tr>
                            </table>
                        </div>";
                
                // Attach PDF if factura was emitted
                $pdfData = null;
                $pdfFilename = null;
                $cobro->refresh();
                if ($cobro->factura_estado === "emitida" && $cobro->lioren_pdf_url) {
                    $pdfData = base64_decode($cobro->lioren_pdf_url);
                    $pdfFilename = "Factura_Folio_{$cobro->lioren_folio}.pdf";
                    $contenidoHtml .= "
                        <div style=\"background: #111111; border-left: 4px solid #FFC107; padding: 12px 15px; margin: 0 0 15px; border-radius: 0 6px 6px 0;\">
                            <p style=\"margin: 0; color: #FFC107; font-size: 13px; font-weight: bold;\">&#128196; Factura adjunta: Folio #{$cobro->lioren_folio}</p>
                        </div>";
                }
                
                $contenidoHtml .= "
                        <p style=\"font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;\">Si tienes consultas, contactanos a hola@bigstudio.cl o por WhatsApp.</p>
                    </div>
                    <div style=\"height: 2px; background: #FFC107; margin: 0 30px;\"></div>
                    <div style=\"background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;\">
                        <p style=\"color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;\">Equipo Big Studio - Agencia de Marketing Digital</p>
                        <p style=\"color: #FFC107; font-size: 12px; margin: 0 0 5px;\">hola@bigstudio.cl</p>
                        <p style=\"color: #555555; font-size: 11px; margin: 12px 0 0;\">Este es un correo automatico del sistema de servicios de agencia.</p>
                    </div>
                </div>";
                
                \Mail::html($contenidoHtml, function ($message) use ($cliente, $cobro, $pdfData, $pdfFilename) {
                    $message->to($cliente->email, $cliente->nombre)
                            ->from(config("mail.from.address"), "Agencia BigStudio")->subject("Pago Confirmado - " . $cobro->concepto . " - Agencia BigStudio");
                    if ($pdfData && $pdfFilename) {
                        $message->attachData($pdfData, $pdfFilename, ["mime" => "application/pdf"]);
                    }
                });
                
                // Log the email
                AgenciaCorreo::create([
                    "agencia_cliente_id" => $cliente->id,
                    "tipo" => "confirmacion_pago_suscripcion",
                    "asunto" => "Pago Confirmado - " . $cobro->concepto,
                    "contenido" => "Confirmacion de pago por {$montoFormateado} via {$metodoPagoTexto}",
                    "estado" => "enviado",
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Error enviando correo confirmacion pago suscripcion: " . $e->getMessage());
        }
        // Avanzar suscripcion al 1 del siguiente mes
        $proximoCobro = now()->addMonth()->startOfMonth();
        $suscripcion->update([
            "proximo_cobro" => $proximoCobro,
            "estado" => "activa",
            "factura_ciclo_emitida" => false,
            "reminder_sent" => false,
        ]);
        $suscripcion->resetReminders();
        $suscripcion->refresh();





















        return redirect()->route("agencia.suscripciones")->with("success", "Pago confirmado ({$metodo}). Nuevo cobro pendiente generado para el proximo ciclo." . $facturaMsg);
    }

    public function suscripcionRevertirPago(AgenciaSuscripcion $suscripcion)
    {
        $cobro = AgenciaCobro::where('agencia_suscripcion_id', $suscripcion->id)
            ->where('estado', 'pagado')
            ->orderByDesc('created_at')
            ->first();

        if (!$cobro) {
            return redirect()->route('agencia.suscripciones')->with('error', 'No hay cobros pagados para revertir.');
        }

        $cobro->update([
            'estado' => 'pendiente',
            'pagado_at' => null,
            'metodo_pago' => null,
        ]);

        return redirect()->route('agencia.suscripciones')->with('success', 'Cobro revertido a pendiente. Confirme el pago cuando lo reciba.');
    }

    // ==========================================
    // COBROS
    // ==========================================
    public function cobros(Request $request)
    {
        $query = AgenciaCobro::with(['cliente', 'suscripcion']);
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('cliente_id')) {
            $query->where('agencia_cliente_id', $request->cliente_id);
        }
        // Date filters
        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            if ($periodo === 'dia') {
                $fecha = $request->input('fecha', date('Y-m-d'));
                $query->whereDate('created_at', $fecha);
            } elseif ($periodo === 'mes') {
                $mes = $request->input('mes', date('Y-m'));
                $parts = explode('-', $mes);
                $query->whereYear('created_at', $parts[0])->whereMonth('created_at', $parts[1]);
            } elseif ($periodo === 'anio') {
                $anio = $request->input('anio', date('Y'));
                $query->whereYear('created_at', $anio);
            }
        }
        $cobros = $query->orderByDesc('created_at')->paginate(5)->appends($request->query());
        $clientes = AgenciaCliente::where('estado', 'activo')->orderBy('nombre')->get();
        $totalPendiente = AgenciaCobro::where('estado', 'pendiente')->sum('monto');
        $totalPagadoMes = AgenciaCobro::where('estado', 'pagado')
            ->whereMonth('pagado_at', now()->month)
            ->whereYear('pagado_at', now()->year)
            ->sum('monto');
        // Period totals
        $totalPagadoPeriodo = 0;
        if ($request->filled('periodo')) {
            $pQuery = AgenciaCobro::where('estado', 'pagado');
            $periodo = $request->periodo;
            if ($periodo === 'dia') {
                $fecha = $request->input('fecha', date('Y-m-d'));
                $pQuery->whereDate('pagado_at', $fecha);
            } elseif ($periodo === 'mes') {
                $mes = $request->input('mes', date('Y-m'));
                $parts = explode('-', $mes);
                $pQuery->whereYear('pagado_at', $parts[0])->whereMonth('pagado_at', $parts[1]);
            } elseif ($periodo === 'anio') {
                $anio = $request->input('anio', date('Y'));
                $pQuery->whereYear('pagado_at', $anio);
            }
            $totalPagadoPeriodo = $pQuery->sum('monto');
        }
        $servicios = AgenciaServicio::where('activo', true)->orderBy('nombre')->get();
        $valorUF = $this->obtenerValorUF();
        return view('agencia.cobros.index', compact('cobros', 'clientes', 'totalPendiente', 'totalPagadoMes', 'totalPagadoPeriodo', 'servicios', 'valorUF'));
    }
    public function cobroStore(Request $request)
    {
        $request->validate([
            'agencia_cliente_id' => 'required|exists:agencia_clientes,id',
            'servicios_ids' => 'nullable|array',
            'servicios_ids.*' => 'exists:agencia_servicios,id',
            'concepto' => 'nullable|string|max:500',
            'monto_neto' => 'nullable|numeric|min:0',
            'monto' => 'nullable|numeric|min:0',
            'vence_at' => 'nullable|date',
            'enviar_correo' => 'nullable|boolean',
            'num_cuotas' => 'nullable|integer|min:1|max:24',
            'intervalo_dias' => 'nullable|integer|min:1|max:365',
        ]);

        $concepto = $request->concepto;
        $montoNeto = $request->monto_neto ?: ($request->monto ?: 0);

        // Handle multiple services
        if ($request->servicios_ids && count($request->servicios_ids) > 0) {
            $totalNeto = 0;
            $nombres = [];
            $valorUF = $this->obtenerValorUF();

            foreach ($request->servicios_ids as $servicioId) {
                $servicio = AgenciaServicio::find($servicioId);
                if ($servicio) {
                    $nombres[] = $servicio->nombre;
                    if ($servicio->moneda === 'UF') {
                        $totalNeto += intval(round($servicio->precio_uf * $valorUF));
                    } else {
                        $totalNeto += $servicio->precio;
                    }
                }
            }

            if (!$concepto) {
                $concepto = implode(' + ', $nombres);
            }
            $montoNeto = $totalNeto;
        }

        // Agregar IVA (19%)
        $iva = intval(round($montoNeto * 0.19));
        $montoTotal = $montoNeto + $iva;

        $numCuotas = max(1, intval($request->num_cuotas ?: 1));
        $intervaloDias = max(1, intval($request->intervalo_dias ?: 30));
        $fechaBase = $request->vence_at ? \Carbon\Carbon::parse($request->vence_at) : now();

        if ($numCuotas === 1) {
            $cobro = AgenciaCobro::create([
                'agencia_cliente_id' => $request->agencia_cliente_id,
                'concepto' => $concepto,
                'monto' => $montoTotal,
                'estado' => 'pendiente',
                'vence_at' => $fechaBase,
            ]);
            $this->emitirFacturaAgencia($cobro);
            $cobro->refresh();
            if ($request->boolean('enviar_correo')) {
                $this->enviarCorreoCobro($cobro);
            }
            $msg = 'Cobro creado exitosamente.';
            if ($cobro->factura_estado === 'emitida') {
                $msg .= ' Factura N° ' . $cobro->lioren_folio . ' emitida.';
            } elseif ($cobro->factura_estado === 'error') {
                $msg .= ' (Factura no emitida: ' . mb_substr($cobro->factura_error, 0, 100) . ')';
            }
        } else {
            $montoPorCuota = intval(floor($montoTotal / $numCuotas));
            $residuo = $montoTotal - ($montoPorCuota * $numCuotas);
            $grupoCuotas = \Illuminate\Support\Str::uuid()->toString();
            $cobrosCreados = [];

            for ($i = 1; $i <= $numCuotas; $i++) {
                $montoCuota = $montoPorCuota;
                if ($i === $numCuotas) {
                    $montoCuota += $residuo;
                }
                $fechaVence = $fechaBase->copy()->addDays(($i - 1) * $intervaloDias);
                $conceptoCuota = $concepto . ' - Cuota ' . $i . '/' . $numCuotas;

                $cobro = AgenciaCobro::create([
                    'agencia_cliente_id' => $request->agencia_cliente_id,
                    'concepto' => $conceptoCuota,
                    'cuota_numero' => $i,
                    'cuota_total' => $numCuotas,
                    'grupo_cuotas' => $grupoCuotas,
                    'monto' => $montoCuota,
                    'estado' => 'pendiente',
                    'vence_at' => $fechaVence,
                ]);
                $this->emitirFacturaAgencia($cobro);
                $cobro->refresh();
                $cobrosCreados[] = $cobro;
            }

            if ($request->boolean('enviar_correo')) {
                foreach ($cobrosCreados as $c) {
                    $this->enviarCorreoCobro($c);
                }
            }
            $msg = $numCuotas . ' cuotas creadas exitosamente de $' . number_format($montoPorCuota, 0, ',', '.') . ' c/u (Total: $' . number_format($montoTotal, 0, ',', '.') . ').';
        }

        return redirect()->route('agencia.cobros')->with('success', $msg);
    }

    public function cobroMarcarPagado(Request $request, AgenciaCobro $cobro)
    {
        $request->validate([
            'metodo_pago' => 'required|in:transferencia,flow,otro',
            'notas_admin' => 'nullable|string',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $data = [
            'estado' => 'pagado',
            'metodo_pago' => $request->metodo_pago,
            'notas_admin' => $request->notas_admin,
            'pagado_at' => now(),
        ];

        if ($request->hasFile('comprobante')) {
            $file = $request->file('comprobante');
            $path = $file->store('comprobantes-agencia', 'public');
            $data['comprobante_path'] = $path;
            $data['comprobante_original_name'] = $file->getClientOriginalName();
        }

        $cobro->update($data);

        // Renovar suscripción si corresponde
        if ($cobro->agencia_suscripcion_id) {
            $suscripcion = $cobro->suscripcion;
            if ($suscripcion) {
                $periodicidadDias = [
                    'mensual' => 30, 'trimestral' => 90, 'semestral' => 180, 'anual' => 365,
                ];
                $dias = $periodicidadDias[$suscripcion->periodicidad] ?? 30;
                $suscripcion->update([
                    "proximo_cobro" => now()->addMonth()->startOfMonth(),
                    'estado' => 'activa',
                ]);
                $suscripcion->resetReminders();
            }
        }

        // Emitir factura automaticamente al marcar como pagado
        try {
            $this->emitirFacturaAgencia($cobro);
        } catch (\Exception $e) {
            \Log::error('Error emitiendo factura al marcar pagado', ['cobro_id' => $cobro->id, 'error' => $e->getMessage()]);
        }

        // Send payment confirmation email (with PDF attached if available)
        try {
            $cobro->refresh(); // Refresh to get factura data
            $this->enviarCorreoConfirmacionPago($cobro);
        } catch (\Exception $e) {
            \Log::error('Error enviando correo confirmacion pago', ['cobro_id' => $cobro->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('agencia.cobros')->with('success', 'Cobro marcado como pagado. Correo de confirmacion enviado al cliente.');
    }

    public function cobroAnular(Request $request, AgenciaCobro $cobro)
    {
        $motivo = $request->input('motivo_anulacion', 'Sin motivo especificado');
        $teniaFactura = ($cobro->factura_estado === 'emitida' && $cobro->lioren_folio);

        $cobro->update([
            'estado' => 'anulado',
            'notas_admin' => ($cobro->notas_admin ? $cobro->notas_admin . ' | ' : '') . 'Anulado: ' . $motivo,
        ]);

        $msg = 'Cobro anulado.';

        // Si el cobro tenia factura emitida en Lioren, emitir Nota de Credito automaticamente.
        // Un DTE ya emitido al SII no se puede "borrar" -- requiere NC tipo 61.
        if ($teniaFactura) {
            try {
                $ncResult = $this->emitirNotaCreditoAgencia($cobro, $motivo);
                if ($ncResult) {
                    $msg .= ' Nota de Credito emitida en Lioren (Folio: ' . ($ncResult['folio'] ?? 'N/A') . ').';
                    Log::info('NC emitida al anular cobro', ['cobro_id' => $cobro->id, 'folio_original' => $cobro->lioren_folio, 'folio_nc' => $ncResult['folio'] ?? null]);
                } else {
                    $msg .= ' ATENCION: el cobro tenia factura (folio ' . $cobro->lioren_folio . ') pero no se pudo emitir la Nota de Credito automaticamente. Anularla manualmente en Lioren.';
                    Log::warning('Anulacion sin NC automatica', ['cobro_id' => $cobro->id, 'folio' => $cobro->lioren_folio]);
                }
            } catch (\Exception $e) {
                Log::error('Error emitiendo NC al anular cobro: ' . $e->getMessage(), ['cobro_id' => $cobro->id]);
                $msg .= ' Error al emitir NC en Lioren: ' . $e->getMessage();
            }
        }

        return redirect()->route('agencia.cobros')->with('success', $msg);
    }

    /**
     * Anular un pago ya marcado como pagado (Task 1 + Task 5)
     * Revierte el estado a 'pendiente' o 'anulado' y opcionalmente emite Nota de Crédito
     */
    public function cobroAnularPago(Request $request, AgenciaCobro $cobro)
    {
        if ($cobro->estado !== 'pagado') {
            return redirect()->route('agencia.cobros')->with('error', 'Solo se pueden anular pagos de cobros con estado "pagado".');
        }

        $motivo = $request->input('motivo_anulacion', 'Sin motivo especificado');
        $nuevoEstado = $request->input('nuevo_estado', 'anulado'); // 'pendiente' o 'anulado'
        $emitirNC = $request->boolean('emitir_nota_credito');

        // Guardar info del pago anterior
        $infoPagoAnterior = 'Pago anulado el ' . now()->format('d/m/Y H:i') . '. Método: ' . ($cobro->metodo_pago ?? 'N/A') . '. Motivo: ' . $motivo;

        $cobro->update([
            'estado' => $nuevoEstado,
            'metodo_pago' => null,
            'pagado_at' => null,
            'notas_admin' => ($cobro->notas_admin ? $cobro->notas_admin . ' | ' : '') . $infoPagoAnterior,
        ]);

        $msg = 'Pago anulado exitosamente. Estado cambiado a "' . $nuevoEstado . '".';

        // Emitir Nota de Crédito si se solicita y hay factura emitida
        if ($emitirNC && $cobro->factura_estado === 'emitida' && $cobro->lioren_folio) {
            $ncResult = $this->emitirNotaCreditoAgencia($cobro, $motivo);
            if ($ncResult) {
                $msg .= ' Nota de Crédito emitida (Folio: ' . ($ncResult['folio'] ?? 'N/A') . ').';
            } else {
                $msg .= ' Error al emitir Nota de Crédito. Revise los logs.';
            }
        }

        return redirect()->route('agencia.cobros')->with('success', $msg);
    }

    /**
     * Emitir Nota de Crédito (tipo 61) para un cobro de agencia (Task 2)
     */
    private function emitirNotaCreditoAgencia(AgenciaCobro $cobro, $motivo = 'Anulación de pago')
    {
        try {
            $cliente = $cobro->cliente;
            if (!$cliente || !$cliente->rut || !$cliente->razon_social) {
                Log::warning('NC Agencia: cliente sin datos fiscales', ['cobro_id' => $cobro->id]);
                return null;
            }

            $config = IntegracionConfig::where('user_id', 4)->first();
            if (!$config || !$config->lioren_api_key) {
                Log::error('NC Agencia: No se encontró API key de Lioren para Big Studio');
                return null;
            }

            $apiKey = $config->lioren_api_key;

            // Sanitizar RUT
            $rut = strtoupper(trim(str_replace(['.', ' '], '', $cliente->rut)));
            if (strpos($rut, '-') === false && strlen($rut) > 1) {
                $rut = substr($rut, 0, -1) . '-' . substr($rut, -1);
            }

            $comunaId = $this->obtenerComunaIdAgencia($cliente->comuna, $apiKey);
            $ciudadId = $this->obtenerCiudadIdAgencia($cliente->ciudad ?? $cliente->comuna, $apiKey);

            $montoNeto = round($cobro->monto / 1.19);

            $receptorNCAg = [
                'rut' => $rut,
                'rs' => mb_substr(trim($cliente->razon_social), 0, 100),
                'giro' => mb_substr(trim($cliente->giro ?: 'Servicios'), 0, 80),
                'direccion' => mb_substr(trim($cliente->direccion_fiscal ?: 'Sin direccion fiscal'), 0, 50),
                'comuna' => $comunaId ?: 317,
                'ciudad' => $ciudadId ?: 32,
                'email' => $cliente->email,
            ];
            $detallesNCAg = [[
                'codigo' => 'NC-AGN-' . $cobro->id,
                'nombre' => mb_substr('NC: ' . ($cobro->concepto ?: 'Servicio de Agencia'), 0, 80),
                'cantidad' => 1,
                'precio' => $montoNeto,
                'unidad' => 'UN',
                'exento' => false,
            ]];
            $referenciaNCAg = [
                'tipodoc' => '33',
                'folio' => (string) $cobro->lioren_folio,
                'fecha' => $cobro->created_at->format('Y-m-d'),
                'razon' => 1, // 1 = Anula documento de referencia
                'glosa' => mb_substr($motivo . ' - Anulación Factura Folio #' . $cobro->lioren_folio, 0, 90),
                'codigo' => 1,
            ];

            // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $result = app(\App\Services\LiorenService::class)->emitirNotaCredito($apiKey, $detallesNCAg, $receptorNCAg, $referenciaNCAg, 'Nota de Crédito - ' . $motivo . ' - Cobro #' . $cobro->id);

            if ($result['ok']) {
                
                // Update cobro with NC info
                $cobro->update([
                    'notas_admin' => ($cobro->notas_admin ? $cobro->notas_admin . ' | ' : '') . 
                        'NC Folio #' . ($result['folio'] ?? 'N/A') . ' emitida el ' . now()->format('d/m/Y'),
                ]);

                Log::info('Nota de Crédito agencia emitida', [
                    'cobro_id' => $cobro->id,
                    'nc_folio' => $result['folio'] ?? 'N/A',
                    'factura_original_folio' => $cobro->lioren_folio,
                ]);

                return $result;
            } else {
                Log::error('Error emitiendo NC agencia', [
                    'cobro_id' => $cobro->id,
                    'status' => $result['status'] ?? null,
                    'error' => $result['error'] ?? '',
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción emitiendo NC agencia: ' . $e->getMessage());
            return null;
        }
    }

    public function cobroEnviarCorreo(AgenciaCobro $cobro)
    {
        $this->enviarCorreoCobro($cobro);
        return redirect()->route('agencia.cobros')->with('success', 'Correo enviado al cliente.');
    }

    public function cobroVerComprobante(AgenciaCobro $cobro)
    {
        if (!$cobro->comprobante_path) {
            abort(404, 'No hay comprobante adjunto.');
        }
        return response()->file(storage_path('app/public/' . $cobro->comprobante_path));
    }

    // ==========================================
    // FLOW PAYMENT (para cobros de agencia)
    // ==========================================
    /**
     * Recargo % aplicado a pagos vía Flow para que la comisión de la pasarela
     * no se descuente del valor del servicio (config flow.recargo_pct).
     */
    private function montoConRecargoFlow($monto): int
    {
        $pct = (float) config('flow.recargo_pct', 0);
        return (int) round($monto * (1 + $pct / 100));
    }

    public function crearPagoFlow(AgenciaCobro $cobro)
    {
        try {
            $cliente = $cobro->cliente;
            $apiKey = config('flow.api_key');
            $secretKey = config('flow.secret_key');
            $apiUrl = config('flow.api_url');

            $params = [
                'apiKey' => $apiKey,
                'commerceOrder' => 'AGN-' . $cobro->id . '-' . time(),
                'subject' => $cobro->concepto,
                'currency' => 'CLP',
                'amount' => $this->montoConRecargoFlow($cobro->monto),
                'email' => $cliente->email,
                'urlConfirmation' => route('agencia.flow.confirmation'),
                'urlReturn' => route('agencia.flow.return'),
                'optional' => 'cobro_id:' . $cobro->id,
            ];

            // Firmar
            ksort($params);
            $toSign = '';
            foreach ($params as $key => $value) {
                $toSign .= $key . $value;
            }
            $params['s'] = hash_hmac('sha256', $toSign, $secretKey);

            $response = Http::withoutVerifying()->asForm()->post("{$apiUrl}/payment/create", $params);

            if ($response->successful()) {
                $data = $response->json();
                $cobro->update(['flow_token' => $data['token']]);
                $paymentUrl = $data['url'] . '?token=' . $data['token'];

                return redirect()->route('agencia.cobros')->with('success', "Link de pago Flow generado. URL: {$paymentUrl}");
            }

            Log::error('Error creando pago Flow agencia', ['response' => $response->body()]);
            return redirect()->route('agencia.cobros')->with('error', 'Error al crear pago en Flow.');
        } catch (\Exception $e) {
            Log::error('Error Flow agencia: ' . $e->getMessage());
            return redirect()->route('agencia.cobros')->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function flowReturn(Request $request)
    {
        $token = $request->input('token');
        if ($token) {
            $this->processFlowPayment($token);
        }
        return view('agencia.flow-success');
    }

    public function flowConfirmation(Request $request)
    {
        $token = $request->input('token');
        if ($token) {
            $this->processFlowPayment($token);
        }
        return response('OK', 200);
    }

    private function processFlowPayment($token)
    {
        try {
            $apiKey = config('flow.api_key');
            $secretKey = config('flow.secret_key');
            $apiUrl = config('flow.api_url');

            $params = ['apiKey' => $apiKey, 'token' => $token];
            ksort($params);
            $toSign = '';
            foreach ($params as $key => $value) {
                $toSign .= $key . $value;
            }
            $params['s'] = hash_hmac('sha256', $toSign, $secretKey);

            $response = Http::withoutVerifying()->asForm()->get("{$apiUrl}/payment/getStatus", $params);

            if (!$response->successful()) return;

            $paymentStatus = $response->json();
            if (($paymentStatus['status'] ?? 0) != 2) return; // 2 = pagado

            $optional = $paymentStatus['optional'] ?? '';
            parse_str(str_replace(['|', ':'], ['&', '='], $optional), $optionalData);
            $cobroId = $optionalData['cobro_id'] ?? null;

            if (!$cobroId) return;

            $cobro = AgenciaCobro::find($cobroId);
            if (!$cobro || $cobro->estado === 'pagado') return;

            $cobro->update([
                'estado' => 'pagado',
                'metodo_pago' => 'flow',
                'flow_token' => $token,
                'pagado_at' => now(),
            ]);

            // Renovar suscripción
            if ($cobro->agencia_suscripcion_id) {
                $suscripcion = $cobro->suscripcion;
                if ($suscripcion) {
                    $periodicidadDias = [
                        'mensual' => 30, 'trimestral' => 90, 'semestral' => 180, 'anual' => 365,
                    ];
                    $dias = $periodicidadDias[$suscripcion->periodicidad] ?? 30;
                    $suscripcion->update([
                        "proximo_cobro" => now()->addMonth()->startOfMonth(),
                        'estado' => 'activa',
                    ]);
                    $suscripcion->resetReminders();
                }
            }

            Log::info('Pago Flow agencia procesado', ['cobro_id' => $cobro->id, 'monto' => $cobro->monto]);
            
            // Emitir factura automaticamente
            if ($cobro->agencia_suscripcion_id) {
                $suscripcion = $cobro->suscripcion;
                if ($suscripcion && $suscripcion->facturacion_automatica) {
                    try {
                        $this->emitirFacturaAgencia($cobro);
                    } catch (\Exception $fe) {
                        Log::error('Error emitiendo factura en pago Flow agencia: ' . $fe->getMessage());
                    }
                }
            } else {
                // Cobro directo (no suscripcion) - emitir factura si tiene cliente con datos fiscales
                try {
                    $this->emitirFacturaAgencia($cobro);
                } catch (\Exception $fe) {
                    Log::error('Error emitiendo factura en pago Flow agencia: ' . $fe->getMessage());
                }
            }
            
            // Enviar correo de confirmacion de pago
            try {
                $this->enviarCorreoConfirmacionPago($cobro);
            } catch (\Exception $ce) {
                Log::error('Error enviando correo confirmacion pago Flow agencia: ' . $ce->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Error procesando pago Flow agencia: ' . $e->getMessage());
        }
    }

    // ==========================================
    // FACTURACIÓN AUTOMÁTICA (Lioren DTE)
    // ==========================================
    public function emitirFacturaAgencia(AgenciaCobro $cobro)
    {
        // =====================================================
        // PROTECCIÓN ANTI-DUPLICADOS CON LOCK ATÓMICO DE BD
        // Usa SELECT ... FOR UPDATE para garantizar que solo
        // un proceso pueda emitir factura para el mismo cobro
        // =====================================================
        return DB::transaction(function () use ($cobro) {
            // Re-leer el cobro con lock exclusivo para evitar race conditions
            $cobroLocked = AgenciaCobro::lockForUpdate()->find($cobro->id);
            if (!$cobroLocked) {
                Log::warning('Factura agencia: cobro no encontrado', ['cobro_id' => $cobro->id]);
                return null;
            }

            // Verificar estado con datos frescos (bloqueados)
            if (in_array($cobroLocked->factura_estado, ['emitida', 'emitiendo'])) {
                Log::info('Factura agencia: omitida por duplicado (lock)', [
                    'cobro_id' => $cobroLocked->id,
                    'factura_estado' => $cobroLocked->factura_estado,
                    'lioren_folio' => $cobroLocked->lioren_folio,
                ]);
                return null;
            }

            // Marcar como 'emitiendo' DENTRO de la transacción (atómico)
            $cobroLocked->update(['factura_estado' => 'emitiendo']);

            try {
                $cliente = $cobroLocked->cliente;
                if (!$cliente || !$cliente->rut || !$cliente->razon_social) {
                    $cobroLocked->update([
                        'factura_estado' => 'error',
                        'factura_error' => 'Cliente sin datos de facturación (RUT o Razón Social faltante)',
                    ]);
                    Log::warning('Factura agencia: cliente sin datos fiscales', ['cobro_id' => $cobroLocked->id]);
                    return null;
                }

                // Obtener API key de Big Studio (user_id=4)
                $config = IntegracionConfig::where('user_id', 4)->first();
                if (!$config || !$config->lioren_api_key) {
                    $cobroLocked->update([
                        'factura_estado' => 'error',
                        'factura_error' => 'No se encontró la API key de Lioren para Big Studio',
                    ]);
                    return null;
                }

                $apiKey = $config->lioren_api_key;

                // Sanitizar RUT
                $rut = strtoupper(trim(str_replace(['.', ' '], '', $cliente->rut)));
                if (strpos($rut, '-') === false && strlen($rut) > 1) {
                    $rut = substr($rut, 0, -1) . '-' . substr($rut, -1);
                }

                // Obtener IDs de localización
                $comunaId = $this->obtenerComunaIdAgencia($cliente->comuna, $apiKey);
                $ciudadId = $this->obtenerCiudadIdAgencia($cliente->ciudad ?? $cliente->comuna, $apiKey);

                // Calcular monto neto (el monto del cobro es bruto con IVA)
                $montoNeto = round($cobroLocked->monto / 1.19);

                $receptorAgencia = [
                    'rut' => $rut,
                    'rs' => mb_substr(trim($cliente->razon_social), 0, 100),
                    'giro' => mb_substr(trim($cliente->giro ?: 'Servicios'), 0, 80),
                    'direccion' => mb_substr(trim($cliente->direccion_fiscal ?: 'Sin direccion fiscal'), 0, 50),
                    'comuna' => $comunaId ?: 317,
                    'ciudad' => $ciudadId ?: 32,
                    'email' => $cliente->email,
                ];
                $detallesAgencia = [[
                    'codigo' => 'AGN-' . $cobroLocked->id,
                    'nombre' => mb_substr($cobroLocked->concepto ?: 'Servicio de Agencia', 0, 80),
                    'cantidad' => 1,
                    'precio' => $montoNeto,
                    'unidad' => 'UN',
                    'exento' => false,
                ]];

                // Si pagó vía Flow, el cliente pagó el recargo de pasarela: debe ir en la factura
                // para que el documento cuadre con el monto realmente pagado.
                if ($cobroLocked->metodo_pago === 'flow') {
                    $recargoBruto = $this->montoConRecargoFlow($cobroLocked->monto) - (int) $cobroLocked->monto;
                    if ($recargoBruto > 0) {
                        $detallesAgencia[] = [
                            'codigo' => 'RECARGO-FLOW',
                            'nombre' => 'Recargo pago electrónico (' . rtrim(rtrim(number_format((float) config('flow.recargo_pct', 0), 2, ',', ''), '0'), ',') . '%)',
                            'cantidad' => 1,
                            'precio' => (int) round($recargoBruto / 1.19),
                            'unidad' => 'UN',
                            'exento' => false,
                        ];
                    }
                }

                // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
                $result = app(\App\Services\LiorenService::class)->emitirFactura(
                    $apiKey,
                    $detallesAgencia,
                    $receptorAgencia,
                    'Cobro Agencia #' . $cobroLocked->id . ' - ' . ($cobroLocked->concepto ?? 'Servicio de Agencia'),
                    ['servicio' => 3]
                );

                if ($result['ok']) {
                    $cobroLocked->update([
                        'factura_estado' => 'emitida',
                        'factura_error' => null,
                        'lioren_folio' => $result['folio'] ?? null,
                        'lioren_tipo_doc' => '33',
                        'lioren_pdf_url' => $result['pdf'] ?? null,
                        'lioren_xml_url' => $result['xml'] ?? null,
                    ]);

                    Log::info('Factura agencia emitida', [
                        'cobro_id' => $cobroLocked->id,
                        'folio' => $result['folio'] ?? 'N/A',
                        'cliente' => $cliente->razon_social,
                    ]);

                    return $result;
                } else {
                    $errorBody = $result['error'] ?? '';
                    $cobroLocked->update([
                        'factura_estado' => 'error',
                        'factura_error' => mb_substr($errorBody, 0, 500),
                    ]);
                    Log::error('Error emitiendo factura agencia', [
                        'cobro_id' => $cobroLocked->id,
                        'status' => $result['status'] ?? null,
                        'error' => $errorBody,
                    ]);
                    return null;
                }
            } catch (\Exception $e) {
                $cobroLocked->update([
                    'factura_estado' => 'error',
                    'factura_error' => mb_substr($e->getMessage(), 0, 500),
                ]);
                Log::error('Excepción emitiendo factura agencia: ' . $e->getMessage());
                return null;
            }
        }); // Fin DB::transaction
    }

    private function obtenerComunaIdAgencia($nombreComuna, $apiKey)
    {
        if (!$nombreComuna) return 317; // Viña del Mar default

        $diccionario = $this->getDiccionarioComunasAgencia();
        $nombreNorm = $this->quitarAcentosAgencia(mb_strtoupper(trim($nombreComuna)));

        if (isset($diccionario[$nombreNorm])) {
            return $diccionario[$nombreNorm];
        }

        // Buscar parcial
        foreach ($diccionario as $key => $id) {
            if (strpos($key, $nombreNorm) !== false || strpos($nombreNorm, $key) !== false) {
                return $id;
            }
        }

        return 317;
    }

    private function obtenerCiudadIdAgencia($nombreCiudad, $apiKey)
    {
        if (!$nombreCiudad) return 32;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get('https://www.lioren.cl/api/ciudades');

            if ($response->successful()) {
                $ciudades = $response->json();
                $nombreNorm = $this->quitarAcentosAgencia(mb_strtoupper(trim($nombreCiudad)));

                foreach ($ciudades as $ciudad) {
                    $ciudadNorm = $this->quitarAcentosAgencia(mb_strtoupper(trim($ciudad['nombre'] ?? '')));
                    if ($ciudadNorm === $nombreNorm) {
                        return $ciudad['id'];
                    }
                }

                // Buscar parcial
                foreach ($ciudades as $ciudad) {
                    $ciudadNorm = $this->quitarAcentosAgencia(mb_strtoupper(trim($ciudad['nombre'] ?? '')));
                    if (strpos($ciudadNorm, $nombreNorm) !== false || strpos($nombreNorm, $ciudadNorm) !== false) {
                        return $ciudad['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo ciudades Lioren: ' . $e->getMessage());
        }

        return 32; // Valparaíso default
    }

    private function quitarAcentosAgencia($string)
    {
        $search  = ['Á','É','Í','Ó','Ú','á','é','í','ó','ú','Ñ','ñ','Ü','ü'];
        $replace = ['A','E','I','O','U','a','e','i','o','u','N','n','U','u'];
        return str_replace($search, $replace, $string);
    }

    private function getDiccionarioComunasAgencia()
    {
        return [
            'ARICA' => 1, 'CAMARONES' => 2, 'PUTRE' => 3, 'GENERAL LAGOS' => 4,
            'IQUIQUE' => 5, 'ALTO HOSPICIO' => 6, 'POZO ALMONTE' => 7, 'CAMINA' => 8,
            'COLCHANE' => 9, 'HUARA' => 10, 'PICA' => 11, 'ANTOFAGASTA' => 12,
            'MEJILLONES' => 13, 'SIERRA GORDA' => 14, 'TALTAL' => 15, 'CALAMA' => 16,
            'OLLAGUE' => 17, 'SAN PEDRO DE ATACAMA' => 18, 'TOCOPILLA' => 19,
            'MARIA ELENA' => 20, 'COPIAPO' => 21, 'CALDERA' => 22, 'TIERRA AMARILLA' => 23,
            'CHANARAL' => 24, 'DIEGO DE ALMAGRO' => 25, 'VALLENAR' => 26,
            'ALTO DEL CARMEN' => 27, 'FREIRINA' => 28, 'HUASCO' => 29,
            'LA SERENA' => 30, 'COQUIMBO' => 31, 'ANDACOLLO' => 32, 'LA HIGUERA' => 33,
            'PAIGUANO' => 34, 'VICUNA' => 35, 'ILLAPEL' => 36, 'CANELA' => 37,
            'LOS VILOS' => 38, 'SALAMANCA' => 39, 'OVALLE' => 40, 'COMBARBALA' => 41,
            'MONTE PATRIA' => 42, 'PUNITAQUI' => 43, 'RIO HURTADO' => 44,
            'VALPARAISO' => 45, 'CASABLANCA' => 46, 'CONCON' => 47, 'JUAN FERNANDEZ' => 48,
            'PUCHUNCAVI' => 49, 'QUINTERO' => 50, 'VINA DEL MAR' => 51,
            'ISLA DE PASCUA' => 52, 'LOS ANDES' => 53, 'CALLE LARGA' => 54,
            'RINCONADA' => 55, 'SAN ESTEBAN' => 56, 'LA LIGUA' => 57, 'CABILDO' => 58,
            'PAPUDO' => 59, 'PETORCA' => 60, 'ZAPALLAR' => 61, 'QUILLOTA' => 62,
            'CALERA' => 63, 'HIJUELAS' => 64, 'LA CRUZ' => 65, 'NOGALES' => 66,
            'SAN ANTONIO' => 67, 'ALGARROBO' => 68, 'CARTAGENA' => 69,
            'EL QUISCO' => 70, 'EL TABO' => 71, 'SANTO DOMINGO' => 72,
            'SAN FELIPE' => 73, 'CATEMU' => 74, 'LLAILLAY' => 75, 'PANQUEHUE' => 76,
            'PUTAENDO' => 77, 'SANTA MARIA' => 78, 'LIMACHE' => 79, 'OLMUE' => 80,
            'VILLA ALEMANA' => 81, 'QUILPUE' => 82,
            'RANCAGUA' => 83, 'CODEGUA' => 84, 'COINCO' => 85, 'COLTAUCO' => 86,
            'DONIHUE' => 87, 'GRANEROS' => 88, 'LAS CABRAS' => 89, 'MACHALI' => 90,
            'MALLOA' => 91, 'MOSTAZAL' => 92, 'OLIVAR' => 93, 'PEUMO' => 94,
            'PICHIDEGUA' => 95, 'QUINTA DE TILCOCO' => 96, 'RENGO' => 97,
            'REQUINOA' => 98, 'SAN VICENTE' => 99, 'PICHILEMU' => 100,
            'LA ESTRELLA' => 101, 'LITUECHE' => 102, 'MARCHIHUE' => 103,
            'NAVIDAD' => 104, 'PAREDONES' => 105, 'SAN FERNANDO' => 106,
            'CHEPICA' => 107, 'CHIMBARONGO' => 108, 'LOLOL' => 109, 'NANCAGUA' => 110,
            'PALMILLA' => 111, 'PERALILLO' => 112, 'PLACILLA' => 113,
            'PUMANQUE' => 114, 'SANTA CRUZ' => 115,
            'TALCA' => 116, 'CONSTITUCION' => 117, 'CUREPTO' => 118,
            'EMPEDRADO' => 119, 'MAULE' => 120, 'PELARCO' => 121, 'PENCAHUE' => 122,
            'RIO CLARO' => 123, 'SAN CLEMENTE' => 124, 'SAN RAFAEL' => 125,
            'CAUQUENES' => 126, 'CHANCO' => 127, 'PELLUHUE' => 128,
            'CURICO' => 129, 'HUALANE' => 130, 'LICANTEN' => 131, 'MOLINA' => 132,
            'RAUCO' => 133, 'ROMERAL' => 134, 'SAGRADA FAMILIA' => 135,
            'TENO' => 136, 'VICHUQUEN' => 137, 'LINARES' => 138, 'COLBUN' => 139,
            'LONGAVI' => 140, 'PARRAL' => 141, 'RETIRO' => 142, 'SAN JAVIER' => 143,
            'VILLA ALEGRE' => 144, 'YERBAS BUENAS' => 145,
            'CHILLAN' => 146, 'BULNES' => 147, 'COBQUECURA' => 148, 'COELEMU' => 149,
            'COIHUECO' => 150, 'CHILLAN VIEJO' => 151, 'EL CARMEN' => 152,
            'NINHUE' => 153, 'NIQUEN' => 154, 'PEMUCO' => 155, 'PINTO' => 156,
            'PORTEZUELO' => 157, 'QUILLON' => 158, 'QUIRIHUE' => 159,
            'RANQUIL' => 160, 'SAN CARLOS' => 161, 'SAN FABIAN' => 162,
            'SAN IGNACIO' => 163, 'SAN NICOLAS' => 164, 'TREGUACO' => 165,
            'YUNGAY' => 166,
            'CONCEPCION' => 167, 'CORONEL' => 168, 'CHIGUAYANTE' => 169,
            'FLORIDA' => 170, 'HUALQUI' => 171, 'LOTA' => 172, 'PENCO' => 173,
            'SAN PEDRO DE LA PAZ' => 174, 'SANTA JUANA' => 175, 'TALCAHUANO' => 176,
            'TOME' => 177, 'HUALPEN' => 178, 'LEBU' => 179, 'ARAUCO' => 180,
            'CANETE' => 181, 'CONTULMO' => 182, 'CURANILAHUE' => 183,
            'LOS ALAMOS' => 184, 'TIRUA' => 185, 'LOS ANGELES' => 186,
            'ANTUCO' => 187, 'CABRERO' => 188, 'LAJA' => 189, 'MULCHEN' => 190,
            'NACIMIENTO' => 191, 'NEGRETE' => 192, 'QUILACO' => 193,
            'QUILLECO' => 194, 'SAN ROSENDO' => 195, 'SANTA BARBARA' => 196,
            'TUCAPEL' => 197, 'YUMBEL' => 198, 'ALTO BIOBIO' => 199,
            'TEMUCO' => 200, 'CARAHUE' => 201, 'CUNCO' => 202, 'CURARREHUE' => 203,
            'FREIRE' => 204, 'GALVARINO' => 205, 'GORBEA' => 206,
            'LAUTARO' => 207, 'LONCOCHE' => 208, 'MELIPEUCO' => 209,
            'NUEVA IMPERIAL' => 210, 'PADRE LAS CASAS' => 211, 'PERQUENCO' => 212,
            'PITRUFQUEN' => 213, 'PUCON' => 214, 'SAAVEDRA' => 215,
            'TEODORO SCHMIDT' => 216, 'TOLTEN' => 217, 'VILCUN' => 218,
            'VILLARRICA' => 219, 'CHOLCHOL' => 220, 'ANGOL' => 221,
            'COLLIPULLI' => 222, 'CURACAUTIN' => 223, 'ERCILLA' => 224,
            'LONQUIMAY' => 225, 'LOS SAUCES' => 226, 'LUMACO' => 227,
            'PUREN' => 228, 'RENAICO' => 229, 'TRAIGUEN' => 230,
            'VICTORIA' => 231,
            'VALDIVIA' => 232, 'CORRAL' => 233, 'LANCO' => 234, 'LOS LAGOS' => 235,
            'MAFIL' => 236, 'MARIQUINA' => 237, 'PAILLACO' => 238,
            'PANGUIPULLI' => 239, 'LA UNION' => 240, 'FUTRONO' => 241,
            'LAGO RANCO' => 242, 'RIO BUENO' => 243,
            'PUERTO MONTT' => 244, 'CALBUCO' => 245, 'COCHAMO' => 246,
            'FRESIA' => 247, 'FRUTILLAR' => 248, 'LOS MUERMOS' => 249,
            'LLANQUIHUE' => 250, 'MAULLIN' => 251, 'PUERTO VARAS' => 252,
            'CASTRO' => 253, 'ANCUD' => 254, 'CHONCHI' => 255, 'CURACO DE VELEZ' => 256,
            'DALCAHUE' => 257, 'PUQUELDON' => 258, 'QUEILEN' => 259,
            'QUELLON' => 260, 'QUEMCHI' => 261, 'QUINCHAO' => 262,
            'OSORNO' => 263, 'ENTRE LAGOS' => 264, 'PUYEHUE' => 265,
            'PUERTO OCTAY' => 266, 'PURRANQUE' => 267, 'RIO NEGRO' => 268,
            'SAN JUAN DE LA COSTA' => 269, 'SAN PABLO' => 270,
            'CHAITEN' => 271, 'FUTALEUFU' => 272, 'HUALAIHUE' => 273, 'PALENA' => 274,
            'COIHAIQUE' => 275, 'LAGO VERDE' => 276, 'AISEN' => 277,
            'CISNES' => 278, 'GUAITECAS' => 279, 'COCHRANE' => 280,
            'OHIGGINS' => 281, 'TORTEL' => 282, 'CHILE CHICO' => 283,
            'RIO IBANEZ' => 284,
            'PUNTA ARENAS' => 285, 'LAGUNA BLANCA' => 286, 'RIO VERDE' => 287,
            'SAN GREGORIO' => 288, 'CABO DE HORNOS' => 289, 'ANTARTICA' => 290,
            'PORVENIR' => 291, 'PRIMAVERA' => 292, 'TIMAUKEL' => 293,
            'NATALES' => 294, 'TORRES DEL PAINE' => 295,
            'SANTIAGO' => 296, 'CERRILLOS' => 297, 'CERRO NAVIA' => 298,
            'CONCHALI' => 299, 'EL BOSQUE' => 300, 'ESTACION CENTRAL' => 301,
            'HUECHURABA' => 302, 'INDEPENDENCIA' => 303, 'LA CISTERNA' => 304,
            'LA FLORIDA' => 305, 'LA GRANJA' => 306, 'LA PINTANA' => 307,
            'LA REINA' => 308, 'LAS CONDES' => 309, 'LO BARNECHEA' => 310,
            'LO ESPEJO' => 311, 'LO PRADO' => 312, 'MACUL' => 313,
            'MAIPU' => 314, 'NUNOA' => 315, 'PEDRO AGUIRRE CERDA' => 316,
            'PENALOLEN' => 317, 'PROVIDENCIA' => 318, 'PUDAHUEL' => 319,
            'QUILICURA' => 320, 'QUINTA NORMAL' => 321, 'RECOLETA' => 322,
            'RENCA' => 323, 'SAN JOAQUIN' => 324, 'SAN MIGUEL' => 325,
            'SAN RAMON' => 326, 'VITACURA' => 327, 'PUENTE ALTO' => 328,
            'PIRQUE' => 329, 'SAN JOSE DE MAIPO' => 330, 'COLINA' => 331,
            'LAMPA' => 332, 'TILTIL' => 333, 'SAN BERNARDO' => 334,
            'BUIN' => 335, 'CALERA DE TANGO' => 336, 'PAINE' => 337,
            'MELIPILLA' => 338, 'ALHUE' => 339, 'CURACAVI' => 340,
            'MARIA PINTO' => 341, 'SAN PEDRO' => 342, 'TALAGANTE' => 343,
            'EL MONTE' => 344, 'ISLA DE MAIPO' => 345, 'PADRE HURTADO' => 346,
            'PENAFLOR' => 347,
            // Variantes comunes
            'VIÑA DEL MAR' => 51, 'VINA DEL MAR' => 51,
            'NUNOA' => 315, 'ÑUÑOA' => 315,
            'PENALOLEN' => 317, 'PEÑALOLEN' => 317, 'PEÑALOLÉN' => 317,
            'MAIPU' => 314, 'MAIPÚ' => 314,
            'CONCON' => 47, 'CONCÓN' => 47,
            'COPIAPO' => 21, 'COPIAPÓ' => 21,
            'CURICO' => 129, 'CURICÓ' => 129,
            'CHILLAN' => 146, 'CHILLÁN' => 146,
            'CONCEPCION' => 167, 'CONCEPCIÓN' => 167,
            'VALPARAISO' => 45, 'VALPARAÍSO' => 45,
            'PUCON' => 214, 'PUCÓN' => 214,
            'TEMUCO' => 200, 'TEMÚCO' => 200,
            'PENAFLOR' => 347, 'PEÑAFLOR' => 347,
            'CANETE' => 181, 'CAÑETE' => 181,
            'VICUNA' => 35, 'VICUÑA' => 35,
            'LAS CONDES' => 309, 'PROVIDENCIA' => 318, 'SANTIAGO CENTRO' => 296,
        ];
    }

    // ==========================================
    // CORREOS
    // ==========================================
    public function enviarCorreoCobro(AgenciaCobro $cobro, $incluirLinkFlow = true)
    {
        try {
            $cliente = $cobro->cliente;
            if (!$cliente || !$cliente->email) {
                Log::warning('No se puede enviar correo: cliente sin email', ['cobro_id' => $cobro->id]);
                return false;
            }

            // Generar link de pago Flow si no existe
            $flowPaymentUrl = null;
            if ($incluirLinkFlow && !$cobro->flow_token) {
                $flowPaymentUrl = $this->generarLinkFlowSilencioso($cobro);
            } elseif ($cobro->flow_token) {
                $env = config('flow.environment');
                $baseUrl = $env === 'production' ? 'https://www.flow.cl/app/web/pay.php' : 'https://sandbox.flow.cl/app/web/pay.php';
                $flowPaymentUrl = $baseUrl . '?token=' . $cobro->flow_token;
            }

            // Calcular desglose IVA para el correo
            $montoTotal = $cobro->monto;
            $montoNeto = intval(round($montoTotal / 1.19));
            $montoIVA = $montoTotal - $montoNeto;
            $montoFormateado = '$' . number_format($montoTotal, 0, ',', '.');
            $netoFormateado = '$' . number_format($montoNeto, 0, ',', '.');
            $ivaFormateado = '$' . number_format($montoIVA, 0, ',', '.');
            $asunto = "Cobro Pendiente - {$cobro->concepto} - Big Studio Agencia";

            $ctaFlow = '';
            if ($flowPaymentUrl) {
                $ctaFlow = "
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='{$flowPaymentUrl}' style='background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>
                            PAGAR CON TARJETA
                        </a>
                    </div>
                    <p style='font-size: 12px; color: #888888; text-align: center; margin: 0 0 15px;'>También puedes pagar por transferencia bancaria y enviarnos el comprobante por WhatsApp.</p>
                ";
            }

            $vencimiento = '';
            if ($cobro->vence_at) {
                $vencimiento = "
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Vencimiento</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FF9800;'>{$cobro->vence_at->format('d/m/Y')}</td>
                            </tr>";
            }

            $contenidoHtml = "
            <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
                <!-- Header oscuro con branding Big Studio -->
                <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                    <img src='https://integration-conector.bigstudio.cl/images/bigstudio_logo_email_2x.png' alt='Big Studio' width='120' height='120' style='display: block; margin: 0 auto 8px;'>
                    <p style='color: #FFC107; margin: 0; font-size: 13px; font-weight: bold; letter-spacing: 2px;'>AGENCIA DE MARKETING DIGITAL</p>
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
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Monto Neto</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$netoFormateado} CLP</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>IVA (19%)</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$ivaFormateado} CLP</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Total a Pagar</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 18px; color: #FFC107; border-bottom: 1px solid #222222;'>{$montoFormateado} CLP</td>
                            </tr>
                            {$vencimiento}
                        </table>
                    </div>
                    {$ctaFlow}
                    <!-- Datos de transferencia -->
                    <div style='background: #1A1A1A; border-left: 4px solid #FFC107; padding: 15px 20px; margin: 0 0 20px; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0 0 10px; font-weight: bold; color: #FFC107; font-size: 14px;'>Datos para transferencia:</p>
                        <p style='margin: 0; color: #AAAAAA; font-size: 13px; line-height: 1.8;'>
                            Banco: Banco Bci<br>
                            Tipo: Cuenta Corriente<br>
                            Nombre: Big Studio<br>
                            RUT: 78.153.109-K<br>
                            N&deg; Cuenta: 97580848<br>
                            Email: hola@bigstudio.cl
                        </p>
                    </div>
                    <!-- Info factura si existe -->
                    " . ($cobro->factura_estado === 'emitida' && $cobro->lioren_folio ? "
                    <div style='background: #111111; border-left: 4px solid #FFC107; padding: 15px 20px; margin: 0 0 20px; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0 0 5px; font-weight: bold; color: #FFC107; font-size: 14px;'>Factura Electr&oacute;nica Adjunta</p>
                        <p style='margin: 0; color: #AAAAAA; font-size: 13px;'>Folio N&deg; {$cobro->lioren_folio} - Adjunta en este correo como PDF.</p>
                    </div>" : '') . "
                    <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si tienes consultas, contáctanos a hola@bigstudio.cl o por WhatsApp.</p>
                </div>
                <!-- Separador dorado -->
                <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
                <!-- Footer oscuro -->
                <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
                    <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Big Studio - Agencia de Marketing Digital</p>
                    <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'><a href='mailto:hola@bigstudio.cl' style='color: #FFC107; text-decoration: none;'>hola@bigstudio.cl</a></p>
                    <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Este es un correo automático. Si tienes consultas, contáctanos por el chat interno o responde a este correo.</p>
                </div>
            </div>";

            // Preparar adjunto de factura si existe
            $pdfData = null;
            $folioFactura = $cobro->lioren_folio;
            if ($cobro->factura_estado === 'emitida' && $cobro->lioren_pdf_url) {
                $pdfData = base64_decode($cobro->lioren_pdf_url);
            }

            Mail::html($contenidoHtml, function ($message) use ($cliente, $asunto, $pdfData, $folioFactura) {
                $message->from(config("mail.from.address"), "Agencia BigStudio")
                    ->to($cliente->email)
                    ->subject($asunto);
                if ($pdfData) {
                    $message->attachData($pdfData, 'Factura_' . $folioFactura . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            Log::info('Correo de cobro agencia enviado', ['cobro_id' => $cobro->id, 'email' => $cliente->email, 'factura_adjunta' => $pdfData ? 'SI' : 'NO']);
            return true;
        } catch (\Exception $e) {
            Log::error("Error enviando correo agencia: " . $e->getMessage());
            return false;
        }
    }

    private function generarLinkFlowSilencioso(AgenciaCobro $cobro)
    {
        try {
            $cliente = $cobro->cliente;
            if (!$cliente->email) return null;

            $apiKey = config('flow.api_key');
            $secretKey = config('flow.secret_key');
            $apiUrl = config('flow.api_url');

            $params = [
                'apiKey' => $apiKey,
                'commerceOrder' => 'AGN-' . $cobro->id . '-' . time(),
                'subject' => $cobro->concepto,
                'currency' => 'CLP',
                'amount' => $this->montoConRecargoFlow($cobro->monto),
                'email' => $cliente->email,
                'urlConfirmation' => route('agencia.flow.confirmation'),
                'urlReturn' => route('agencia.flow.return'),
                'optional' => 'cobro_id:' . $cobro->id,
            ];

            ksort($params);
            $toSign = '';
            foreach ($params as $key => $value) {
                $toSign .= $key . $value;
            }
            $params['s'] = hash_hmac('sha256', $toSign, $secretKey);

            $response = Http::withoutVerifying()->asForm()->post("{$apiUrl}/payment/create", $params);

            if ($response->successful()) {
                $data = $response->json();
                $cobro->update(['flow_token' => $data['token']]);
                return $data['url'] . '?token=' . $data['token'];
            }
        } catch (\Exception $e) {
            Log::error('Error generando link Flow silencioso: ' . $e->getMessage());
        }
        return null;
    }

    private function enviarCorreoConfirmacionPago(AgenciaCobro $cobro)
    {
        try {
            $cliente = $cobro->cliente;
            if (!$cliente || !$cliente->email) return false;
            
            $montoFormateado = "$" . number_format($cobro->monto, 0, ",", ".");
            $metodoPago = ucfirst($cobro->metodo_pago ?: "Transferencia bancaria");
            if ($metodoPago === "Flow") $metodoPago = "Pago en linea (Flow)";
            if ($metodoPago === "Transferencia") $metodoPago = "Transferencia bancaria";
            $fechaPago = $cobro->pagado_at ? \Carbon\Carbon::parse($cobro->pagado_at)->format("d/m/Y") : now()->format("d/m/Y");
            $asunto = "Pago Confirmado - {$cobro->concepto} - Big Studio";
            
            $cuotaInfo = "";
            if ($cobro->agencia_suscripcion_id) {
                $suscripcion = $cobro->suscripcion;
                if ($suscripcion) {
                    $cuotaInfo = '
                            <tr>
                                <td style="padding: 12px 15px; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;">Suscripcion</td>
                                <td style="padding: 12px 15px; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;">' . $suscripcion->servicio_nombre . '</td>
                            </tr>';
                }
            }
            
            $facturaHtml = "";
            $pdfData = null;
            $pdfFilename = null;
            $cobro->refresh();
            if ($cobro->factura_estado === "emitida" && $cobro->lioren_pdf_url) {
                $pdfData = base64_decode($cobro->lioren_pdf_url);
                $pdfFilename = "Factura_Folio_{$cobro->lioren_folio}.pdf";
                $facturaHtml = '
                    <div style="background: #111111; border-left: 4px solid #FFC107; padding: 12px 15px; margin: 0 0 15px; border-radius: 0 6px 6px 0;">
                        <p style="margin: 0; color: #FFC107; font-size: 13px; font-weight: bold;">&#128196; Factura adjunta: Folio #' . $cobro->lioren_folio . '</p>
                    </div>';
            }
            
            $logoUrl = "https://bigstudio.cl/wp-content/uploads/2024/06/Mesa-de-trabajo-1-copia-3.png";
            
            $contenidoHtml = '
            <div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;">
                <div style="background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;">
                    <img src="' . $logoUrl . '" alt="Big Studio" style="width: 80px; height: auto; margin-bottom: 15px;">
                    <p style="color: #FFC107; margin: 0; font-size: 13px; font-weight: bold; letter-spacing: 2px;">AGENCIA DE MARKETING DIGITAL</p>
                    <div style="width: 60px; height: 2px; background: #FFC107; margin: 14px auto 0;"></div>
                </div>
                <div style="background: #FFC107; padding: 14px 20px; text-align: center;">
                    <p style="color: #0A0A0A; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;">&#10003; Pago Confirmado</p>
                </div>
                <div style="padding: 30px 30px 20px; background: #0A0A0A;">
                    <p style="font-size: 15px; color: #FFFFFF; margin: 0 0 8px;">Hola <strong style="color: #FFC107;">' . $cliente->nombre . '</strong>,</p>
                    <p style="font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 25px;">Tu pago ha sido confirmado exitosamente.</p>
                    <div style="background: #111111; border-radius: 8px; overflow: hidden; margin: 0 0 20px; border: 1px solid #222222;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 12px 15px; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;">Concepto</td>
                                <td style="padding: 12px 15px; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;">' . $cobro->concepto . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 15px; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;">Monto</td>
                                <td style="padding: 12px 15px; font-weight: bold; text-align: right; font-size: 18px; color: #FFC107; border-bottom: 1px solid #222222;">' . $montoFormateado . ' CLP</td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 15px; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;">Metodo de Pago</td>
                                <td style="padding: 12px 15px; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;">' . $metodoPago . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 15px; color: #888888; font-size: 13px;">Fecha</td>
                                <td style="padding: 12px 15px; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF;">' . $fechaPago . '</td>
                            </tr>
                            ' . $cuotaInfo . '
                        </table>
                    </div>
                    ' . $facturaHtml . '
                    <p style="font-size: 13px; color: #888888; text-align: center; margin: 15px 0 0;">Si tienes consultas, contactanos a <a href="mailto:hola@bigstudio.cl" style="color: #FFC107; text-decoration: none;">hola@bigstudio.cl</a> o por WhatsApp.</p>
                </div>
                <div style="background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;">
                    <p style="color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;">Equipo Big Studio - Agencia de Marketing Digital</p>
                    <p style="color: #FFC107; font-size: 12px; margin: 0 0 5px;"><a href="mailto:hola@bigstudio.cl" style="color: #FFC107; text-decoration: none;">hola@bigstudio.cl</a></p>
                    <p style="color: #555555; font-size: 11px; margin: 12px 0 0;">Este es un correo automatico del sistema de servicios de agencia.</p>
                </div>
            </div>';
            
            Mail::html($contenidoHtml, function ($message) use ($cliente, $asunto, $pdfData, $pdfFilename) {
                $message->from(config("mail.from.address"), "Agencia BigStudio")
                    ->to($cliente->email)
                    ->subject($asunto);
                if ($pdfData && $pdfFilename) {
                    $message->attachData($pdfData, $pdfFilename, ["mime" => "application/pdf"]);
                }
            });
            
            AgenciaCorreo::create([
                "agencia_cliente_id" => $cobro->agencia_cliente_id,
                "tipo" => "confirmacion_pago",
                "asunto" => $asunto,
                "contenido" => "Confirmacion de pago: {$cobro->concepto} - {$montoFormateado}",
                "estado" => "enviado",
                "enviado_at" => now(),
            ]);
            
            \Log::info("Correo confirmacion pago enviado", ["cobro_id" => $cobro->id, "email" => $cliente->email]);
            return true;
        } catch (\Exception $e) {
            \Log::error("Error enviando correo confirmacion pago: " . $e->getMessage());
            return false;
        }
    }
    
    public function correos(Request $request)
    {
        $query = \App\Models\AgenciaCorreo::orderByDesc('created_at');
        if ($request->filled('cliente_id')) {
            $cliente = AgenciaCliente::find($request->cliente_id);
            if ($cliente) {
                $query->where('destinatario_email', $cliente->email);
            }
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        $correos = $query->paginate(20)->appends($request->query());
        $clientes = AgenciaCliente::where('estado', 'activo')->orderBy('nombre')->get();
        return view('agencia.correos.index', compact('correos', 'clientes'));
    }
    public function correoEnviar(Request $request)
    {
        $request->validate([
            'asunto' => 'required|string|max:500',
            'contenido' => 'required|string',
            'destinatario_email' => 'required|email',
            'adjuntos.*' => 'nullable|file|max:10240',
        ]);

        $email = $request->destinatario_email;
        $nombre = $request->destinatario_nombre ?? 'Estimado/a';
        $asunto = $request->asunto;
        $vistaPrevia = $request->vista_previa ?? '';
        $contenidoTexto = $request->contenido;
        $clienteId = $request->agencia_cliente_id;

        // Convertir saltos de línea en párrafos HTML
        $parrafos = array_filter(explode("\n", $contenidoTexto));
        $contenidoHtmlBody = '';
        foreach ($parrafos as $p) {
            $p = trim($p);
            if ($p) {
                $contenidoHtmlBody .= "<p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 15px;'>" . e($p) . "</p>";
            }
        }

        // Construir el HTML del correo con branding de agencia
        $contenidoHtml = "
        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
            <!-- Header con logo -->
            <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                <img src='https://integration-conector.bigstudio.cl/images/bigstudio_logo_email_2x.png' alt='Big Studio' width='120' height='120' style='display: block; margin: 0 auto 8px;'>
                <p style='color: #FFC107; margin: 0; font-size: 13px; font-weight: bold; letter-spacing: 2px;'>AGENCIA DE MARKETING DIGITAL</p>
                <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
            </div>
            <!-- Contenido -->
            <div style='padding: 30px 30px 20px;'>
                <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 20px;'>Hola <strong style='color: #FFC107;'>{$nombre}</strong>,</p>
                {$contenidoHtmlBody}
            </div>
            <!-- Firma corporativa -->
            <div style='padding: 0 30px 20px;'>
                <div style='border-top: 1px solid #222222; padding-top: 20px;'>
                    <table style='width: 100%;'>
                        <tr>
                            <td style='width: 70px; vertical-align: top; padding-right: 15px;'>
                                <img src='https://integration-conector.bigstudio.cl/images/bigstudio_logo_email_2x.png' alt='Big Studio' width='60' height='60' style='border-radius: 8px;'>
                            </td>
                            <td style='vertical-align: top;'>
                                <p style='margin: 0 0 3px; font-weight: bold; color: #FFFFFF; font-size: 14px;'>Big Studio</p>
                                <p style='margin: 0 0 3px; color: #FFC107; font-size: 12px; font-weight: bold;'>Equipo Big Studio - Agencia de Marketing Digital</p>
                                <p style='margin: 0 0 2px; color: #888888; font-size: 11px;'>hola@bigstudio.cl</p>
                                <p style='margin: 0 0 2px; color: #888888; font-size: 11px;'>www.bigstudio.cl</p>
                                <p style='margin: 0; color: #888888; font-size: 11px;'>Santiago, Chile</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <!-- Separador dorado -->
            <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
            <!-- Footer -->
            <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
                <p style='color: #555555; font-size: 10px; margin: 0;'>Este correo fue enviado por Big Studio - Agencia de Marketing Digital. Si no esperabas este mensaje, puedes ignorarlo.</p>
            </div>
        </div>";

        // Procesar adjuntos
        $adjuntosInfo = [];
        $adjuntosArchivos = [];
        if ($request->hasFile('adjuntos')) {
            foreach ($request->file('adjuntos') as $archivo) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $path = $archivo->store('agencia_correos_adjuntos', 'public');
                $adjuntosInfo[] = [
                    'nombre' => $nombreOriginal,
                    'path' => $path,
                    'mime' => $archivo->getMimeType(),
                    'size' => $archivo->getSize(),
                ];
                $adjuntosArchivos[] = [
                    'path' => storage_path('app/public/' . $path),
                    'nombre' => $nombreOriginal,
                    'mime' => $archivo->getMimeType(),
                ];
            }
        }

        // Guardar en BD
        $correo = AgenciaCorreo::create([
            'agencia_cliente_id' => $clienteId,
            'destinatario_email' => $email,
            'destinatario_nombre' => $nombre,
            'asunto' => $asunto,
            'vista_previa' => $vistaPrevia,
            'contenido' => $contenidoTexto,
            'adjuntos' => $adjuntosInfo,
            'estado' => 'borrador',
        ]);

        // Enviar correo
        try {
            Mail::html($contenidoHtml, function ($message) use ($email, $asunto, $adjuntosArchivos) {
                $message->from(config("mail.from.address"), "Agencia BigStudio")
                    ->to($email)->subject($asunto);
                foreach ($adjuntosArchivos as $adj) {
                    $message->attach($adj['path'], [
                        'as' => $adj['nombre'],
                        'mime' => $adj['mime'],
                    ]);
                }
            });

            $correo->update([
                'estado' => 'enviado',
                'enviado_at' => now(),
            ]);

            Log::info('Correo corporativo agencia enviado', ['correo_id' => $correo->id, 'email' => $email]);
            return redirect()->route('agencia.correos')->with('success', 'Correo enviado exitosamente a ' . $email);
        } catch (\Exception $e) {
            $correo->update([
                'estado' => 'error',
                'error_mensaje' => $e->getMessage(),
            ]);
            Log::error('Error enviando correo corporativo: ' . $e->getMessage());
            return redirect()->route('agencia.correos')->with('error', 'Error al enviar: ' . $e->getMessage());
        }
    }

    // ==========================================
    // COTIZACIONES
    // ==========================================
    public function cotizaciones(Request $request)
    {
        $query = AgenciaCotizacion::with('items');
        
        if ($request->filled('cliente_id')) {
            $cliente = AgenciaCliente::find($request->cliente_id);
            if ($cliente) {
                $query->where('cliente_nombre', 'like', '%' . $cliente->nombre . '%');
            }
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        
        $cotizaciones = $query->orderByDesc('created_at')->paginate(5)->appends($request->query());
        $clientes = AgenciaCliente::where('estado', 'activo')->orderBy('nombre')->get();
        $servicios = AgenciaServicio::where('activo', true)->orderBy('nombre')->get();
        $proximoNumero = (AgenciaCotizacion::max('numero') ?? 10000) + 1;
        $valorUF = $this->obtenerValorUF();
        return view('agencia.cotizaciones.index', compact('cotizaciones', 'clientes', 'servicios', 'proximoNumero', 'valorUF'));
    }
    public function cotizacionStore(Request $request)
    {
        $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'cliente_email' => 'required|email',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string|max:500',
            'pdf_complemento' => 'nullable|file|mimes:pdf|max:5120',
            'valida_hasta' => 'nullable|date',
        ]);

        // Calcular totales
        $subtotalNeto = 0;
        $itemsData = [];
        foreach ($request->items as $item) {
            $cant = max(1, intval($item['cantidad'] ?? 1));
            $precioNeto = max(0, intval($item['precio_neto'] ?? 0));
            $totalNeto = $cant * $precioNeto;
            $subtotalNeto += $totalNeto;
            $itemsData[] = [
                'agencia_servicio_id' => ($item['servicio_id'] ?? null) ?: null,
                'codigo' => $item['codigo'] ?? null,
                'descripcion' => $item['descripcion'],
                'cantidad' => $cant,
                'precio_unitario_neto' => $precioNeto,
                'total_neto' => $totalNeto,
            ];
        }

        $descPct = floatval($request->descuento_porcentaje ?? 0);
        $descMonto = intval(round($subtotalNeto * $descPct / 100));
        $totalNeto = $subtotalNeto - $descMonto;
        $iva = intval(round($totalNeto * 0.19));
        $total = $totalNeto + $iva;

        $numero = (AgenciaCotizacion::max('numero') ?? 9999) + 1;

        $cotizacion = AgenciaCotizacion::create([
            'numero' => $numero,
            'agencia_cliente_id' => $request->agencia_cliente_id ?: null,
            'cliente_nombre' => $request->cliente_nombre,
            'cliente_rut' => $request->cliente_rut,
            'cliente_email' => $request->cliente_email,
            'cliente_telefono' => $request->cliente_telefono,
            'cliente_direccion' => $request->cliente_direccion,
            'cliente_giro' => $request->cliente_giro,
            'subtotal_neto' => $subtotalNeto,
            'descuento_porcentaje' => $descPct,
            'descuento_monto' => $descMonto,
            'total_neto' => $totalNeto,
            'iva' => $iva,
            'total' => $total,
            'notas' => $request->notas,
            'estado' => 'borrador',
            // Respeta la fecha "válida hasta" ingresada manualmente; si viene vacía usa +7 días por defecto.
            'valida_hasta' => $request->filled('valida_hasta') ? $request->valida_hasta : now()->addDays(7),
        ]);

        foreach ($itemsData as $itemData) {
            $cotizacion->items()->create($itemData);
        }

        // PDF complemento opcional (detalle del plan, etc.): se guarda en disco public y
        // se adjunta al correo de la cotizacion. Se persiste ANTES de enviar el correo.
        if ($request->hasFile('pdf_complemento')) {
            $path = $request->file('pdf_complemento')->store('cotizaciones_complementos', 'public');
            $cotizacion->update(['pdf_complemento_path' => $path]);
        }

        Log::info('Cotizacion creada', ['numero' => $numero, 'total' => $total]);

        // El boton "Crear y Enviar por Correo" manda enviar_correo=1; ademas se acepta accion=enviar (legacy/API).
        if ($request->accion === 'enviar' || $request->boolean('enviar_correo')) {
            return $this->enviarCotizacionEmail($cotizacion);
        }

        return redirect()->route('agencia.cotizaciones')->with('success', 'Cotizacion #' . $numero . ' guardada como borrador.');
    }

    public function cotizacionEnviar(AgenciaCotizacion $cotizacion)
    {
        return $this->enviarCotizacionEmail($cotizacion);
    }

    /**
     * Envia el correo de cotizacion al cliente.
     * - Email HTML renderizado desde resources/views/emails/agencia/cotizacion.blade.php
     * - PDF adjunto generado con el template BigStudio
     * - Boton de pago Flow con look brand
     */
    private function enviarCotizacionEmail(AgenciaCotizacion $cotizacion)
    {
        $cotizacion->load('items');

        // Generar link de pago Flow (puede ser null si falla la API)
        $flowUrl = $this->generarFlowCotizacion($cotizacion);

        // Renderizar email HTML desde Blade
        $contenidoHtml = view('emails.agencia.cotizacion', compact('cotizacion', 'flowUrl'))->render();

        try {
            // Generar PDF de la cotizacion para adjuntar
            $pdfHtml = $this->generarCotizacionPdfHtml($cotizacion);
            $pdfData = null;
            try {
                $tmpHtml = tempnam(sys_get_temp_dir(), 'cot_') . '.html';
                $tmpPdf  = tempnam(sys_get_temp_dir(), 'cot_') . '.pdf';
                file_put_contents($tmpHtml, $pdfHtml);
                exec('wkhtmltopdf --quiet --page-size Letter --margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 ' . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf) . ' 2>&1', $output, $returnCode);
                if ($returnCode === 0 && file_exists($tmpPdf)) {
                    $pdfData = file_get_contents($tmpPdf);
                } else {
                    \Log::warning('wkhtmltopdf fallo para cotizacion', ['numero' => $cotizacion->numero, 'output' => implode("\n", $output)]);
                }
                @unlink($tmpHtml);
                @unlink($tmpPdf);
            } catch (\Exception $e) {
                \Log::warning('No se pudo generar PDF de cotizacion', ['numero' => $cotizacion->numero, 'error' => $e->getMessage()]);
            }

            Mail::html($contenidoHtml, function ($message) use ($cotizacion, $pdfData) {
                $message->from(config('mail.from.address'), 'Big Studio')
                    ->to($cotizacion->cliente_email, $cotizacion->cliente_nombre)
                    ->subject('Cotización #' . $cotizacion->numero . ' - Big Studio');
                if ($pdfData) {
                    $message->attachData($pdfData, 'Cotizacion_' . $cotizacion->numero . '_BigStudio.pdf', ['mime' => 'application/pdf']);
                }
                // Adjuntar PDF complemento si existe (detalle del plan, etc.)
                if ($cotizacion->pdf_complemento_path && \Storage::disk('public')->exists($cotizacion->pdf_complemento_path)) {
                    $message->attach(\Storage::disk('public')->path($cotizacion->pdf_complemento_path), [
                        'as'   => 'Detalle_' . $cotizacion->numero . '_BigStudio.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            $cotizacion->update([
                'estado' => 'enviada',
                'enviada_at' => now(),
            ]);

            Log::info('Cotizacion enviada', ['numero' => $cotizacion->numero, 'email' => $cotizacion->cliente_email]);
            return redirect()->route('agencia.cotizaciones')->with('success', 'Cotizacion #' . $cotizacion->numero . ' enviada a ' . $cotizacion->cliente_email);
        } catch (\Exception $e) {
            Log::error('Error enviando cotizacion: ' . $e->getMessage());
            return redirect()->route('agencia.cotizaciones')->with('error', 'Error al enviar: ' . $e->getMessage());
        }
    }

    /**
     * @deprecated Implementacion vieja del email de cotizacion. Reemplazada por el Blade
     * `resources/views/emails/agencia/cotizacion.blade.php`. Se conserva solo de referencia.
     */
    private function enviarCotizacionEmailLegacy(AgenciaCotizacion $cotizacion)
    {
        $cotizacion->load('items');
        $flowUrl = $this->generarFlowCotizacion($cotizacion);
        $itemsHtml = '';
        foreach ($cotizacion->items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding: 10px; border-bottom: 1px solid #222; color: #BBBBBB; font-size: 13px;'>" . e($item->codigo ?: '-') . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #222; color: #BBBBBB; font-size: 13px;'>" . e($item->descripcion) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #222; color: #BBBBBB; font-size: 13px; text-align: center;'>" . $item->cantidad . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #222; color: #BBBBBB; font-size: 13px; text-align: right;'>$" . number_format($item->precio_unitario_neto, 0, ',', '.') . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #222; color: #FFFFFF; font-size: 13px; text-align: right; font-weight: bold;'>$" . number_format($item->total_neto, 0, ',', '.') . "</td>
            </tr>";
        }

        $descuentoHtml = '';
        if ($cotizacion->descuento_porcentaje > 0) {
            $descuentoHtml = "<tr><td colspan='4' style='padding: 8px 10px; text-align: right; color: #FF6B6B; font-size: 13px;'>Descuento (" . $cotizacion->descuento_porcentaje . "%):</td><td style='padding: 8px 10px; text-align: right; color: #FF6B6B; font-size: 13px;'>-$" . number_format($cotizacion->descuento_monto, 0, ',', '.') . "</td></tr>";
        }

        $flowBtnHtml = '';
        if ($flowUrl) {
            $flowBtnHtml = "<div style='text-align: center; margin: 25px 0;'>
                <a href='{$flowUrl}' style='display: inline-block; background: #FFC107; color: #000; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 15px; letter-spacing: 1px;'>PAGAR CON TARJETA</a>
            </div>";
        }

        $contenidoHtml = "
        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 650px; margin: 0 auto; background: #0A0A0A;'>
            <!-- Header -->
            <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                <img src='https://integration-conector.bigstudio.cl/images/bigstudio_logo_email_2x.png' alt='Big Studio' width='120' height='120' style='display: block; margin: 0 auto 8px;'>
                <p style='color: #FFC107; margin: 0; font-size: 13px; font-weight: bold; letter-spacing: 2px;'>AGENCIA DE MARKETING DIGITAL</p>
            </div>
            <!-- Cotizacion header -->
            <div style='background: linear-gradient(135deg, #FFC107, #FF9800); padding: 15px 30px; text-align: center;'>
                <h2 style='color: #000; margin: 0; font-size: 20px; letter-spacing: 2px;'>COTIZACION #{$cotizacion->numero}</h2>
            </div>
            <!-- Datos cliente -->
            <div style='padding: 25px 30px 15px;'>
                <table style='width: 100%;'>
                    <tr>
                        <td style='vertical-align: top; width: 50%;'>
                            <p style='color: #FFC107; font-size: 11px; font-weight: bold; margin: 0 0 5px; letter-spacing: 1px;'>CLIENTE</p>
                            <p style='color: #FFFFFF; font-size: 14px; margin: 0 0 3px; font-weight: bold;'>" . e($cotizacion->cliente_nombre) . "</p>
                            <p style='color: #BBBBBB; font-size: 12px; margin: 0 0 2px;'>" . e($cotizacion->cliente_rut ?: '') . "</p>
                            <p style='color: #BBBBBB; font-size: 12px; margin: 0 0 2px;'>" . e($cotizacion->cliente_email) . "</p>
                            <p style='color: #BBBBBB; font-size: 12px; margin: 0;'>" . e($cotizacion->cliente_direccion ?: '') . "</p>
                        </td>
                        <td style='vertical-align: top; width: 50%; text-align: right;'>
                            <p style='color: #FFC107; font-size: 11px; font-weight: bold; margin: 0 0 5px; letter-spacing: 1px;'>DETALLE</p>
                            <p style='color: #BBBBBB; font-size: 12px; margin: 0 0 2px;'>Fecha: " . now()->format('d/m/Y') . "</p>
                            <p style='color: #BBBBBB; font-size: 12px; margin: 0 0 2px;'>Valido hasta: <strong style='color: #FF9800;'>" . $cotizacion->valida_hasta->format('d/m/Y') . "</strong></p>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- Tabla de items -->
            <div style='padding: 0 30px;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr style='background: #16213e;'>
                            <th style='padding: 10px; color: #FFC107; font-size: 11px; text-align: left; letter-spacing: 1px;'>CODIGO</th>
                            <th style='padding: 10px; color: #FFC107; font-size: 11px; text-align: left; letter-spacing: 1px;'>DESCRIPCION</th>
                            <th style='padding: 10px; color: #FFC107; font-size: 11px; text-align: center; letter-spacing: 1px;'>CANT.</th>
                            <th style='padding: 10px; color: #FFC107; font-size: 11px; text-align: right; letter-spacing: 1px;'>P. NETO</th>
                            <th style='padding: 10px; color: #FFC107; font-size: 11px; text-align: right; letter-spacing: 1px;'>TOTAL NETO</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr><td colspan='4' style='padding: 8px 10px; text-align: right; color: #BBBBBB; font-size: 13px;'>Subtotal Neto:</td><td style='padding: 8px 10px; text-align: right; color: #FFFFFF; font-size: 13px;'>$" . number_format($cotizacion->subtotal_neto, 0, ',', '.') . "</td></tr>
                        {$descuentoHtml}
                        <tr><td colspan='4' style='padding: 8px 10px; text-align: right; color: #BBBBBB; font-size: 13px;'>Total Neto:</td><td style='padding: 8px 10px; text-align: right; color: #FFFFFF; font-size: 13px; font-weight: bold;'>$" . number_format($cotizacion->total_neto, 0, ',', '.') . "</td></tr>
                        <tr><td colspan='4' style='padding: 8px 10px; text-align: right; color: #BBBBBB; font-size: 13px;'>IVA (19%):</td><td style='padding: 8px 10px; text-align: right; color: #FFFFFF; font-size: 13px;'>$" . number_format($cotizacion->iva, 0, ',', '.') . "</td></tr>
                        <tr style='background: #16213e;'><td colspan='4' style='padding: 12px 10px; text-align: right; color: #FFC107; font-size: 16px; font-weight: bold;'>TOTAL:</td><td style='padding: 12px 10px; text-align: right; color: #FFC107; font-size: 16px; font-weight: bold;'>$" . number_format($cotizacion->total, 0, ',', '.') . "</td></tr>
                    </tfoot>
                </table>
            </div>
            <!-- Boton de pago Flow -->
            {$flowBtnHtml}
            <!-- Datos de transferencia -->
            <div style='padding: 20px 30px;'>
                <div style='background: #16213e; border-radius: 8px; padding: 15px 20px;'>
                    <p style='color: #FFC107; font-size: 12px; font-weight: bold; margin: 0 0 10px; letter-spacing: 1px;'>DATOS PARA TRANSFERENCIA</p>
                    <table style='width: 100%;'>
                        <tr><td style='color: #888; font-size: 12px; padding: 2px 0;'>Banco:</td><td style='color: #FFF; font-size: 12px; padding: 2px 0;'>Banco Bci</td></tr>
                        <tr><td style='color: #888; font-size: 12px; padding: 2px 0;'>Tipo Cuenta:</td><td style='color: #FFF; font-size: 12px; padding: 2px 0;'>Cuenta Corriente</td></tr>
                        <tr><td style='color: #888; font-size: 12px; padding: 2px 0;'>Numero de Cuenta:</td><td style='color: #FFF; font-size: 12px; padding: 2px 0;'>97580848</td></tr>
                        <tr><td style='color: #888; font-size: 12px; padding: 2px 0;'>RUT:</td><td style='color: #FFF; font-size: 12px; padding: 2px 0;'>78.153.109-K</td></tr>
                        <tr><td style='color: #888; font-size: 12px; padding: 2px 0;'>Nombre:</td><td style='color: #FFF; font-size: 12px; padding: 2px 0;'>Big Studio</td></tr>
                        <tr><td style='color: #888; font-size: 12px; padding: 2px 0;'>Email:</td><td style='color: #FFF; font-size: 12px; padding: 2px 0;'>hola@bigstudio.cl</td></tr>
                    </table>
                </div>
            </div>
            <!-- Validez -->
            <div style='padding: 0 30px 15px; text-align: center;'>
                <p style='color: #FF9800; font-size: 12px; margin: 0;'><strong>Esta cotizacion tiene una validez de 7 dias</strong> (hasta el " . $cotizacion->valida_hasta->format('d/m/Y') . ")</p>
            </div>
            <!-- Notas -->"
            . ($cotizacion->notas ? "<div style='padding: 0 30px 15px;'><p style='color: #888; font-size: 12px; margin: 0;'><strong>Notas:</strong> " . e($cotizacion->notas) . "</p></div>" : '') .
            "<!-- Firma -->
            <div style='padding: 0 30px 20px;'>
                <div style='border-top: 1px solid #222; padding-top: 20px;'>
                    <table style='width: 100%;'>
                        <tr>
                            <td style='width: 70px; vertical-align: top; padding-right: 15px;'>
                                <img src='https://integration-conector.bigstudio.cl/images/bigstudio_logo_email_2x.png' alt='Big Studio' width='60' height='60' style='border-radius: 8px;'>
                            </td>
                            <td style='vertical-align: top;'>
                                <p style='margin: 0 0 3px; font-weight: bold; color: #FFF; font-size: 14px;'>Big Studio</p>
                                <p style='margin: 0 0 3px; color: #FFC107; font-size: 12px; font-weight: bold;'>Equipo Big Studio - Agencia de Marketing Digital</p>
                                <p style='margin: 0 0 2px; color: #888; font-size: 11px;'>hola@bigstudio.cl | www.bigstudio.cl</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
            <div style='padding: 15px 30px; text-align: center;'>
                <p style='color: #555; font-size: 10px; margin: 0;'>Cotizacion generada por Big Studio - Equipo Big Studio - Agencia de Marketing Digital</p>
            </div>
        </div>";

        try {
            // Generar PDF de la cotizacion para adjuntar usando wkhtmltopdf
            $pdfHtml = $this->generarCotizacionPdfHtml($cotizacion);
            $pdfData = null;
            try {
                $tmpHtml = tempnam(sys_get_temp_dir(), 'cot_') . '.html';
                $tmpPdf = tempnam(sys_get_temp_dir(), 'cot_') . '.pdf';
                file_put_contents($tmpHtml, $pdfHtml);
                exec('wkhtmltopdf --quiet --page-size Letter --margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 ' . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf) . ' 2>&1', $output, $returnCode);
                if ($returnCode === 0 && file_exists($tmpPdf)) {
                    $pdfData = file_get_contents($tmpPdf);
                } else {
                    \Log::warning('wkhtmltopdf fallo para cotizacion', ['numero' => $cotizacion->numero, 'output' => implode("\n", $output)]);
                }
                @unlink($tmpHtml);
                @unlink($tmpPdf);
            } catch (\Exception $e) {
                \Log::warning('No se pudo generar PDF de cotizacion', ['numero' => $cotizacion->numero, 'error' => $e->getMessage()]);
            }
            Mail::html($contenidoHtml, function ($message) use ($cotizacion, $pdfData) {
                $message->from(config("mail.from.address"), "Agencia BigStudio")
                    ->to($cotizacion->cliente_email)
                    ->subject('Cotizacion #' . $cotizacion->numero . ' - Big Studio Agencia');
                // Adjuntar PDF de cotizacion
                if ($pdfData) {
                    $message->attachData($pdfData, 'Cotizacion_' . $cotizacion->numero . '.pdf', ['mime' => 'application/pdf']);
                }
            });

            $cotizacion->update([
                'estado' => 'enviada',
                'enviada_at' => now(),
            ]);

            Log::info('Cotizacion enviada', ['numero' => $cotizacion->numero, 'email' => $cotizacion->cliente_email]);
            return redirect()->route('agencia.cotizaciones')->with('success', 'Cotizacion #' . $cotizacion->numero . ' enviada a ' . $cotizacion->cliente_email);
        } catch (\Exception $e) {
            Log::error('Error enviando cotizacion: ' . $e->getMessage());
            return redirect()->route('agencia.cotizaciones')->with('error', 'Error al enviar: ' . $e->getMessage());
        }
    }

    private function generarFlowCotizacion(AgenciaCotizacion $cotizacion)
    {
        try {
            $apiKey = env('FLOW_API_KEY');
            $secretKey = env('FLOW_SECRET_KEY');
            $apiUrl = env('FLOW_API_URL', 'https://www.flow.cl/api');

            if (!$apiKey || !$secretKey) return null;

            $commerceOrder = 'COT-' . $cotizacion->numero . '-' . time();
            $cotizacion->update(['flow_order' => $commerceOrder]);

            $params = [
                'apiKey' => $apiKey,
                'commerceOrder' => $commerceOrder,
                'subject' => 'Cotizacion #' . $cotizacion->numero . ' - Big Studio',
                'currency' => 'CLP',
                'amount' => $this->montoConRecargoFlow($cotizacion->total),
                'email' => $cotizacion->cliente_email,
                'urlConfirmation' => route('agencia.cotizaciones.flow.confirmation'),
                'urlReturn' => route('agencia.cotizaciones.flow.return'),
            ];

            ksort($params);
            $toSign = '';
            foreach ($params as $key => $value) {
                $toSign .= $key . $value;
            }
            $params['s'] = hash_hmac('sha256', $toSign, $secretKey);

            $response = Http::withoutVerifying()->asForm()->post("{$apiUrl}/payment/create", $params);

            if ($response->successful()) {
                $data = $response->json();
                $cotizacion->update(['flow_token' => $data['token']]);
                return $data['url'] . '?token=' . $data['token'];
            }
        } catch (\Exception $e) {
            Log::error('Error generando Flow para cotizacion: ' . $e->getMessage());
        }
        return null;
    }

    public function cotizacionFlowReturn(Request $request)
    {
        $token = $request->get('token');
        $cotizacion = AgenciaCotizacion::where('flow_token', $token)->first();
        if ($cotizacion && $cotizacion->estado !== 'cancelada') {
            return redirect('https://integration-conector.bigstudio.cl/agencia/cotizaciones')
                ->with('success', 'Pago procesado para cotizacion #' . $cotizacion->numero);
        }
        return redirect('https://integration-conector.bigstudio.cl/agencia/cotizaciones');
    }

    public function cotizacionFlowConfirmation(Request $request)
    {
        try {
            $token = $request->input('token');
            $apiKey = env('FLOW_API_KEY');
            $secretKey = env('FLOW_SECRET_KEY');
            $apiUrl = env('FLOW_API_URL', 'https://www.flow.cl/api');

            $params = ['apiKey' => $apiKey, 'token' => $token];
            ksort($params);
            $toSign = '';
            foreach ($params as $key => $value) {
                $toSign .= $key . $value;
            }
            $params['s'] = hash_hmac('sha256', $toSign, $secretKey);

            $response = Http::withoutVerifying()->asForm()->get("{$apiUrl}/payment/getStatus", $params);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] == 2) { // Pagado
                    $cotizacion = AgenciaCotizacion::where('flow_order', $data['commerceOrder'])->first();
                    if ($cotizacion) {
                        $cotizacion->update([
                            'estado' => 'pagada',
                            'pagado_at' => now(),
                        ]);
                        Log::info('Cotizacion pagada por Flow', ['numero' => $cotizacion->numero]);

                        // Emitir factura automaticamente
                        $this->emitirFacturaCotizacion($cotizacion);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error en Flow confirmation cotizacion: ' . $e->getMessage());
        }
        return response('OK', 200);
    }

    public function cotizacionFacturar(AgenciaCotizacion $cotizacion)
    {
        $resultado = $this->emitirFacturaCotizacion($cotizacion);
        if ($resultado) {
            return redirect()->route('agencia.cotizaciones')->with('success', 'Factura emitida para cotizacion #' . $cotizacion->numero . ' - Folio: ' . $cotizacion->lioren_folio);
        }
        return redirect()->route('agencia.cotizaciones')->with('error', 'Error al emitir factura. Verifique que el servicio de Lioren este activo.');
    }

    private function emitirFacturaCotizacion(AgenciaCotizacion $cotizacion)
    {
        try {
            $cotizacion->load('items');
            $config = IntegracionConfig::where('user_id', 4)->first();
            if (!$config || !$config->lioren_api_key) {
                Log::error('No se encontro configuracion de Lioren para Big Studio');
                return false;
            }

            $detalles = [];
            foreach ($cotizacion->items as $item) {
                $detalles[] = [
                    'nombre' => substr($item->descripcion, 0, 80),
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio_unitario_neto,
                    'exento' => false,
                ];
            }

            // Si la cotización se pagó vía Flow (pagado_at solo lo setea la confirmación Flow),
            // el cliente pagó el recargo de pasarela: debe ir en la factura para que cuadre.
            if ($cotizacion->pagado_at) {
                $recargoBruto = $this->montoConRecargoFlow($cotizacion->total) - (int) $cotizacion->total;
                if ($recargoBruto > 0) {
                    $detalles[] = [
                        'nombre' => 'Recargo pago electrónico (' . rtrim(rtrim(number_format((float) config('flow.recargo_pct', 0), 2, ',', ''), '0'), ',') . '%)',
                        'cantidad' => 1,
                        'precio' => (int) round($recargoBruto / 1.19),
                        'exento' => false,
                    ];
                }
            }

            // Obtener IDs de localizacion para la comuna del cliente
            $comunaId = 317; // Santiago por defecto
            $ciudadId = 317;

            $receptorCot = [
                'rs' => substr($cotizacion->cliente_nombre, 0, 50),
                'rut' => $cotizacion->cliente_rut ?: '66666666-6',
                'giro' => substr($cotizacion->cliente_giro ?: 'Comercio en general', 0, 40),
                'direccion' => substr($cotizacion->cliente_direccion ?: 'Santiago', 0, 50),
                'comuna_id' => $comunaId,
                'ciudad_id' => $ciudadId,
            ];

            $opcionesCot = [];
            if ($cotizacion->descuento_porcentaje > 0) {
                $opcionesCot['descuento_global'] = [
                    'tipo' => 'porcentaje',
                    'valor' => $cotizacion->descuento_porcentaje,
                ];
            }

            // Emisión vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $result = app(\App\Services\LiorenService::class)->emitirFactura(
                $config->lioren_api_key,
                $detalles,
                $receptorCot,
                '',
                $opcionesCot
            );

            if ($result['ok']) {
                $cotizacion->update([
                    'factura_estado' => 'emitida',
                    'lioren_dte_id' => $result['id'] ?? null,
                    'lioren_folio' => $result['folio'] ?? null,
                    'lioren_pdf_url' => $result['pdf'] ?? null,
                    'lioren_xml_url' => $result['xml'] ?? null,
                    'estado' => 'facturada',
                    'facturado_at' => now(),
                ]);
                Log::info('Factura emitida para cotizacion', ['numero' => $cotizacion->numero, 'folio' => $result['folio'] ?? 'N/A']);
                return true;
            } else {
                Log::error('Error Lioren cotizacion', ['response' => $result['error']]);
                $cotizacion->update(['factura_estado' => 'error']);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error emitiendo factura cotizacion: ' . $e->getMessage());
            $cotizacion->update(['factura_estado' => 'error']);
            return false;
        }
    }

    /**
     * Ver detalle de una cotización (Task 4)
     */
    public function cotizacionVer(AgenciaCotizacion $cotizacion)
    {
        $cotizacion->load('items', 'cliente');
        return response()->json([
            'numero' => $cotizacion->numero,
            'fecha' => $cotizacion->created_at->format('d/m/Y'),
            'cliente_nombre' => $cotizacion->cliente_nombre,
            'cliente_rut' => $cotizacion->cliente_rut,
            'cliente_email' => $cotizacion->cliente_email,
            'cliente_telefono' => $cotizacion->cliente_telefono,
            'cliente_direccion' => $cotizacion->cliente_direccion,
            'cliente_giro' => $cotizacion->cliente_giro,
            'estado' => $cotizacion->estado,
            'valida_hasta' => $cotizacion->valida_hasta ? $cotizacion->valida_hasta->format('d/m/Y') : null,
            'subtotal_neto' => $cotizacion->subtotal_neto,
            'descuento_porcentaje' => $cotizacion->descuento_porcentaje,
            'descuento_monto' => $cotizacion->descuento_monto,
            'total_neto' => $cotizacion->total_neto,
            'iva' => $cotizacion->iva,
            'total' => $cotizacion->total,
            'notas' => $cotizacion->notas,
            'factura_estado' => $cotizacion->factura_estado,
            'lioren_folio' => $cotizacion->lioren_folio,
            'items' => $cotizacion->items->map(function($item) {
                return [
                    'codigo' => $item->codigo,
                    'descripcion' => $item->descripcion,
                    'cantidad' => $item->cantidad,
                    'precio_unitario_neto' => $item->precio_unitario_neto,
                    'total_neto' => $item->total_neto,
                ];
            }),
        ]);
    }

    public function cotizacionCancelar(AgenciaCotizacion $cotizacion)
    {
        $cotizacion->update(['estado' => 'cancelada']);
        return redirect()->route('agencia.cotizaciones')->with('success', 'Cotizacion #' . $cotizacion->numero . ' cancelada.');
    }
    /**
     * Obtener valor actual de la UF
     */
    public function obtenerValorUF(): float
    {
        return \Cache::remember("valor_uf_agencia", 3600, function () {
            try {
                $dbValue = \DB::table("system_settings")->where("key", "valor_uf")->value("value");
                if ($dbValue && (float)$dbValue > 0) {
                    return (float)$dbValue;
                }
            } catch (\Exception $e) {}
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(10)->get("https://mindicador.cl/api/uf");
                if ($response->successful()) {
                    $data = $response->json();
                    return $data["serie"][0]["valor"] ?? 39841.72;
                }
            } catch (\Exception $e) {}
            return 39841.72;
        });
    }

    /**
     * Descargar PDF de cotizacion (Task 8)
     */
    public function cotizacionDescargarPdf($id)
    {
        $cotizacion = AgenciaCotizacion::with('items')->findOrFail($id);
        $pdfHtml = $this->generarCotizacionPdfHtml($cotizacion);
        
        $tmpHtml = tempnam(sys_get_temp_dir(), 'cot_') . '.html';
        $tmpPdf = tempnam(sys_get_temp_dir(), 'cot_') . '.pdf';
        file_put_contents($tmpHtml, $pdfHtml);
        exec('wkhtmltopdf --quiet --page-size Letter --margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 ' . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf) . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($tmpPdf)) {
            $pdfContent = file_get_contents($tmpPdf);
            @unlink($tmpHtml);
            @unlink($tmpPdf);
            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="Cotizacion_' . $cotizacion->numero . '.pdf"');
        }
        @unlink($tmpHtml);
        @unlink($tmpPdf);
        return redirect()->route('agencia.cotizaciones')->with('error', 'Error al generar el PDF de la cotizacion.');
    }

    /**
     * Generar HTML para PDF de cotizacion (Task 8)
     */
    /**
     * Genera el HTML del PDF de cotizacion usando el template Blade BigStudio.
     * Reemplaza la version vieja (string concat) que estaba debajo.
     */
    private function generarCotizacionPdfHtml(AgenciaCotizacion $cotizacion)
    {
        $cotizacion->loadMissing('items');
        return view('agencia.cotizaciones.pdf', compact('cotizacion'))->render();
    }

    /**
     * @deprecated Reemplazado por el template Blade BigStudio.
     * Se conserva como _legacy por si hay que comparar formatos.
     */
    private function generarCotizacionPdfHtmlLegacy(AgenciaCotizacion $cotizacion)
    {
        $items = $cotizacion->items;
        $itemsHtml = "";
        foreach ($items as $item) {
            $totalItem = $item->cantidad * $item->precio_neto;
            $itemsHtml .= "<tr>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; font-size: 12px;'>" . e($item->codigo ?: "SVC") . "</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; font-size: 12px;'>" . e($item->descripcion) . "</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; font-size: 12px; text-align: center;'>" . $item->cantidad . "</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; font-size: 12px; text-align: right;'>$" . number_format($item->precio_neto, 0, ',', '.') . "</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; font-size: 12px; text-align: right;'>$" . number_format($totalItem, 0, ',', '.') . "</td>
            </tr>";
        }
        $descuentoHtml = "";
        if ($cotizacion->descuento_porcentaje > 0) {
            $descuentoHtml = "<tr><td colspan='4' style='text-align: right; padding: 5px 8px; font-size: 12px; color: #e74c3c;'>Descuento (" . $cotizacion->descuento_porcentaje . "%):</td><td style='text-align: right; padding: 5px 8px; font-size: 12px; color: #e74c3c;'>-$" . number_format($cotizacion->descuento_monto, 0, ',', '.') . "</td></tr>";
        }
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>body{font-family:Arial,sans-serif;margin:0;padding:20px;color:#333;font-size:12px;}table{width:100%;border-collapse:collapse;}</style></head><body>
        <div style='text-align:center;margin-bottom:20px;'>
            <h2 style='margin:0;color:#1a237e;'>Big Studio</h2>
            <p style='margin:5px 0;color:#FFC107;font-size:11px;font-weight:bold;'>AGENCIA DE MARKETING DIGITAL</p>
            <p style='margin:2px 0;font-size:10px;color:#888;'>hola@bigstudio.cl | www.bigstudio.cl</p>
        </div>
        <div style='background:#1a237e;color:#fff;padding:10px 15px;border-radius:5px;margin-bottom:15px;'>
            <h3 style='margin:0;font-size:16px;'>COTIZACION #" . e($cotizacion->numero) . "</h3>
        </div>
        <table style='margin-bottom:15px;'>
            <tr><td style='width:50%;vertical-align:top;'>
                <p style='margin:2px 0;font-size:12px;'><strong>Cliente:</strong> " . e($cotizacion->cliente_nombre) . "</p>
                <p style='margin:2px 0;font-size:12px;'><strong>Email:</strong> " . e($cotizacion->cliente_email) . "</p>
                <p style='margin:2px 0;font-size:12px;'><strong>RUT:</strong> " . e($cotizacion->cliente_rut ?: 'N/A') . "</p>
            </td><td style='width:50%;vertical-align:top;text-align:right;'>
                <p style='margin:2px 0;font-size:12px;'><strong>Fecha:</strong> " . $cotizacion->created_at->format('d/m/Y') . "</p>
                <p style='margin:2px 0;font-size:12px;'><strong>Valida hasta:</strong> " . $cotizacion->valida_hasta->format('d/m/Y') . "</p>
                <p style='margin:2px 0;font-size:12px;'><strong>Estado:</strong> " . ucfirst($cotizacion->estado) . "</p>
            </td></tr>
        </table>
        <table>
            <thead><tr style='background:#1a237e;'>
                <th style='padding:8px;color:#FFC107;font-size:11px;text-align:left;'>CODIGO</th>
                <th style='padding:8px;color:#FFC107;font-size:11px;text-align:left;'>DESCRIPCION</th>
                <th style='padding:8px;color:#FFC107;font-size:11px;text-align:center;'>CANT.</th>
                <th style='padding:8px;color:#FFC107;font-size:11px;text-align:right;'>P. NETO</th>
                <th style='padding:8px;color:#FFC107;font-size:11px;text-align:right;'>TOTAL NETO</th>
            </tr></thead>
            <tbody>{$itemsHtml}</tbody>
            <tfoot>
                <tr><td colspan='4' style='text-align:right;padding:5px 8px;font-size:12px;'>Subtotal Neto:</td><td style='text-align:right;padding:5px 8px;font-size:12px;'>$" . number_format($cotizacion->subtotal_neto, 0, ',', '.') . "</td></tr>
                {$descuentoHtml}
                <tr><td colspan='4' style='text-align:right;padding:5px 8px;font-size:12px;font-weight:bold;'>Total Neto:</td><td style='text-align:right;padding:5px 8px;font-size:12px;font-weight:bold;'>$" . number_format($cotizacion->total_neto, 0, ',', '.') . "</td></tr>
                <tr><td colspan='4' style='text-align:right;padding:5px 8px;font-size:12px;'>IVA (19%):</td><td style='text-align:right;padding:5px 8px;font-size:12px;'>$" . number_format($cotizacion->iva, 0, ',', '.') . "</td></tr>
                <tr style='background:#1a237e;'><td colspan='4' style='text-align:right;padding:10px 8px;color:#FFC107;font-size:14px;font-weight:bold;'>TOTAL:</td><td style='text-align:right;padding:10px 8px;color:#FFC107;font-size:14px;font-weight:bold;'>$" . number_format($cotizacion->total, 0, ',', '.') . "</td></tr>
            </tfoot>
        </table>
        <div style='margin-top:15px;padding:10px;background:#f5f5f5;border-radius:5px;'>
            <p style='font-size:11px;margin:2px 0;'><strong>Datos para Transferencia:</strong></p>
            <p style='font-size:11px;margin:2px 0;'>Banco: Banco Bci | Cuenta Corriente: 97580848</p>
            <p style='font-size:11px;margin:2px 0;'>RUT: 78.153.109-K | Nombre: Big Studio</p>
            <p style='font-size:11px;margin:2px 0;'>Email: hola@bigstudio.cl</p>
        </div>"
        . ($cotizacion->notas ? "<div style='margin-top:10px;'><p style='font-size:11px;color:#666;'><strong>Notas:</strong> " . e($cotizacion->notas) . "</p></div>" : "")
        . "<div style='margin-top:20px;text-align:center;border-top:1px solid #ddd;padding-top:10px;'>
            <p style='font-size:10px;color:#888;margin:0;'>Cotizacion generada por Big Studio - Equipo Big Studio - Agencia de Marketing Digital</p>
        </div></body></html>";
    }


    public function verFactura(AgenciaCobro $cobro, Request $request)
    {
        $tipo = $request->query('tipo', 'factura');
        
        if ($tipo === 'nc') {
            $pdfBase64 = $cobro->nc_pdf_url;
            $folio = $cobro->nc_folio;
            $titulo = 'Nota_Credito_Folio_' . $folio;
        } else {
            $pdfBase64 = $cobro->lioren_pdf_url;
            $folio = $cobro->lioren_folio;
            $titulo = 'Factura_Folio_' . $folio;
        }
        
        if (!$pdfBase64) {
            return back()->with('error', 'No hay PDF disponible para este documento.');
        }
        
        $pdfData = base64_decode($pdfBase64);
        return response($pdfData)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $titulo . '.pdf"');
    }
    
    public function reenviarFactura(AgenciaCobro $cobro, Request $request)
    {
        $tipo = $request->query('tipo', 'factura');
        $cliente = $cobro->cliente;
        
        if (!$cliente || !$cliente->email) {
            return back()->with('error', 'El cliente no tiene email configurado.');
        }
        
        if ($tipo === 'nc') {
            $pdfBase64 = $cobro->nc_pdf_url;
            $folio = $cobro->nc_folio;
            $tipoDoc = 'Nota de Credito';
            $filename = 'Nota_Credito_Folio_' . $folio . '.pdf';
        } else {
            $pdfBase64 = $cobro->lioren_pdf_url;
            $folio = $cobro->lioren_folio;
            $tipoDoc = 'Factura';
            $filename = 'Factura_Folio_' . $folio . '.pdf';
        }
        
        if (!$pdfBase64) {
            return back()->with('error', 'No hay PDF disponible para reenviar.');
        }
        
        $pdfData = base64_decode($pdfBase64);
        $user = auth()->user();
        $nombreEmpresa = $user->nombre_empresa ?? 'Big Studio';
        
        $asunto = $tipoDoc . ' #' . $folio . ' - ' . $nombreEmpresa;
        
        $contenidoHtml = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #1a1a2e; padding: 0;">
            <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 30px; text-align: center;">
                <h1 style="color: #f5c518; font-size: 24px; margin: 0;">' . htmlspecialchars($nombreEmpresa) . '</h1>
                <p style="color: #888888; font-size: 14px; margin-top: 5px;">SERVICIOS DE AGENCIA</p>
            </div>
            <div style="padding: 30px; background: #0d1117;">
                <h2 style="color: #f5c518; font-size: 18px; margin-bottom: 15px;">' . $tipoDoc . ' Adjunta</h2>
                <p style="color: #c9d1d9; font-size: 14px; line-height: 1.6;">
                    Estimado/a <strong>' . htmlspecialchars($cliente->nombre) . '</strong>,
                </p>
                <p style="color: #c9d1d9; font-size: 14px; line-height: 1.6;">
                    Le reenviamos la ' . strtolower($tipoDoc) . ' <strong>Folio #' . $folio . '</strong> correspondiente a:
                </p>
                <div style="background: #161b22; border-left: 3px solid #f5c518; padding: 15px; margin: 15px 0; border-radius: 4px;">
                    <p style="color: #f5c518; font-weight: bold; margin: 0;">' . htmlspecialchars($cobro->concepto) . '</p>
                    <p style="color: #8b949e; margin: 5px 0 0 0;">Monto: $' . number_format($cobro->monto, 0, ',', '.') . '</p>
                </div>
                <p style="color: #8b949e; font-size: 13px; margin-top: 20px;">
                    Encontrara el documento adjunto en formato PDF.
                </p>
            </div>
            <div style="background: #161b22; padding: 20px; text-align: center; border-top: 1px solid #30363d;">
                <p style="color: #484f58; font-size: 12px; margin: 0;">' . htmlspecialchars($nombreEmpresa) . ' - Equipo Big Studio - Agencia de Marketing Digital</p>
            </div>
        </div>';
        
        try {
            \Mail::html($contenidoHtml, function ($message) use ($cliente, $asunto, $pdfData, $filename) {
                $message->from(config("mail.from.address"), "Agencia BigStudio")
                        ->to($cliente->email, $cliente->nombre)
                        ->subject($asunto)
                        ->attachData($pdfData, $filename, ['mime' => 'application/pdf']);
            });
            return back()->with('success', $tipoDoc . ' reenviada exitosamente a ' . $cliente->email);
        } catch (\Exception $e) {
            \Log::error('Error reenviando factura: ' . $e->getMessage());
            return back()->with('error', 'Error al reenviar: ' . $e->getMessage());
        }
    }

    public function correoMasivo(Request $request)
    {
        $request->validate([
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);
        
        $clientes = AgenciaCliente::where('estado', 'activo')->whereNotNull('email')->get();
        
        if ($clientes->isEmpty()) {
            return back()->with('error', 'No hay clientes activos con email.');
        }
        
        $user = auth()->user();
        $nombreEmpresa = $user->nombre_empresa ?? 'Big Studio';
        $asunto = $request->asunto;
        $contenido = $request->contenido;
        $enviados = 0;
        $errores = 0;
        
        foreach ($clientes as $cliente) {
            $contenidoHtml = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #1a1a2e;">
                <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 30px; text-align: center;">
                    <h1 style="color: #f5c518; font-size: 24px; margin: 0;">' . htmlspecialchars($nombreEmpresa) . '</h1>
                    <p style="color: #888888; font-size: 14px; margin-top: 5px;">SERVICIOS DE AGENCIA</p>
                </div>
                <div style="padding: 30px; background: #0d1117;">
                    <p style="color: #c9d1d9; font-size: 14px; line-height: 1.6;">
                        Estimado/a <strong>' . htmlspecialchars($cliente->nombre) . '</strong>,
                    </p>
                    <div style="color: #c9d1d9; font-size: 14px; line-height: 1.8;">' . nl2br(htmlspecialchars($contenido)) . '</div>
                </div>
                <div style="background: #161b22; padding: 20px; text-align: center; border-top: 1px solid #30363d;">
                    <p style="color: #484f58; font-size: 12px; margin: 0;">' . htmlspecialchars($nombreEmpresa) . ' - Equipo Big Studio - Agencia de Marketing Digital</p>
                </div>
            </div>';
            
            try {
                \Mail::html($contenidoHtml, function ($message) use ($cliente, $asunto) {
                    $message->from(config("mail.from.address"), "Agencia BigStudio")
                        ->to($cliente->email, $cliente->nombre)->subject($asunto);
                });
                
                \App\Models\AgenciaCorreo::create([
                    'user_id' => auth()->id(),
                    'agencia_cliente_id' => $cliente->id,
                    'destinatario_email' => $cliente->email,
                    'destinatario_nombre' => $cliente->nombre,
                    'asunto' => $asunto . ' [Masivo]',
                    'contenido' => $contenido,
                    'estado' => 'enviado',
                ]);
                $enviados++;
            } catch (\Exception $e) {
                $errores++;
                \Log::error('Error correo masivo a ' . $cliente->email . ': ' . $e->getMessage());
            }
        }
        
        return back()->with('success', "Correo masivo enviado: {$enviados} exitosos, {$errores} errores de {$clientes->count()} clientes.");
    }

    // ==========================================
    // TAREAS DE AGENCIA (por cliente)
    // ==========================================

    /** Panel admin: todas las tareas con filtro por cliente y estado. */
    public function tareas(Request $request)
    {
        $query = AgenciaTarea::with(['cliente', 'comparticiones']);

        if ($request->filled('cliente_id')) {
            $query->where('agencia_cliente_id', $request->cliente_id);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        // Buscador: por nombre de cliente, proyecto/tienda o título de la tarea.
        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->whereHas('cliente', function ($c) use ($b) {
                    $c->where('nombre', 'like', "%{$b}%")->orWhere('proyecto', 'like', "%{$b}%");
                })->orWhere('titulo', 'like', "%{$b}%");
            });
        }

        $tareas = $query->orderByRaw("FIELD(estado,'requiere_cambios','en_revision','en_curso','pendiente','borrador','terminado')")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        $clientes = AgenciaCliente::orderBy('nombre')->get(['id', 'nombre', 'proyecto']);
        $colaboradores = User::where('role', 'colaborador')->orderBy('name')->get(['id', 'name', 'email']);

        $resumen = AgenciaTarea::selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $resumen = collect(AgenciaTarea::ESTADOS)->mapWithKeys(fn ($e) => [$e => (int) ($resumen[$e] ?? 0)])->all();

        return view('agencia.tareas.index', compact('tareas', 'clientes', 'colaboradores', 'resumen'));
    }

    /** Vista Tablero (Kanban): tareas agrupadas en columnas por estado. */
    public function tareasTablero(Request $request)
    {
        $query = AgenciaTarea::with(['cliente', 'comparticiones']);

        if ($request->filled('cliente_id')) {
            $query->where('agencia_cliente_id', $request->cliente_id);
        }
        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->whereHas('cliente', function ($c) use ($b) {
                    $c->where('nombre', 'like', "%{$b}%")->orWhere('proyecto', 'like', "%{$b}%");
                })->orWhere('titulo', 'like', "%{$b}%");
            });
        }

        $tareas = $query->orderByDesc('created_at')->get();

        // Agrupa por estado respetando el orden de AgenciaTarea::ESTADOS (columnas del tablero).
        $tareasPorEstado = collect(AgenciaTarea::ESTADOS)
            ->mapWithKeys(fn ($e) => [$e => $tareas->where('estado', $e)->values()])
            ->all();
        $resumen = collect(AgenciaTarea::ESTADOS)
            ->mapWithKeys(fn ($e) => [$e => $tareasPorEstado[$e]->count()])
            ->all();

        $clientes = AgenciaCliente::orderBy('nombre')->get(['id', 'nombre', 'proyecto']);
        $colaboradores = User::where('role', 'colaborador')->orderBy('name')->get(['id', 'name', 'email']);

        return view('agencia.tareas.tablero', compact('tareasPorEstado', 'clientes', 'colaboradores', 'resumen'));
    }

    public function tareaStore(Request $request)
    {
        $data = $request->validate([
            'agencia_cliente_id' => 'required|exists:agencia_clientes,id',
            'titulo'             => 'required|string|max:180',
            'descripcion'        => 'nullable|string',
            'estado'             => 'required|in:' . implode(',', AgenciaTarea::ESTADOS),
            'prioridad'          => 'nullable|in:baja,media,alta',
            'fecha_limite'       => 'nullable|date',
        ]);

        $data['prioridad'] = $data['prioridad'] ?? 'media';
        $data['creado_por'] = auth()->id();
        if ($data['estado'] === 'terminado') {
            $data['terminada_en'] = now();
        }

        AgenciaTarea::create($data);

        return back()->with('success', 'Tarea creada correctamente.');
    }

    public function tareaUpdate(Request $request, AgenciaTarea $tarea)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:180',
            'descripcion'  => 'nullable|string',
            'estado'       => 'required|in:' . implode(',', AgenciaTarea::ESTADOS),
            'prioridad'    => 'nullable|in:baja,media,alta',
            'fecha_limite' => 'nullable|date',
        ]);

        $data['prioridad'] = $data['prioridad'] ?? $tarea->prioridad;
        $data['terminada_en'] = $data['estado'] === 'terminado' ? ($tarea->terminada_en ?? now()) : null;

        $tarea->update($data);

        return back()->with('success', 'Tarea actualizada.');
    }

    /** Cambio rapido de estado desde el listado (admin). */
    public function tareaEstado(Request $request, AgenciaTarea $tarea)
    {
        $request->validate(['estado' => 'required|in:' . implode(',', AgenciaTarea::ESTADOS)]);
        $tarea->estado = $request->estado;
        $tarea->terminada_en = $request->estado === 'terminado' ? ($tarea->terminada_en ?? now()) : null;
        $tarea->save();

        // Avisa a los colaboradores compartidos del cambio (p.ej. "Requiere cambios").
        $this->notificarTarea($tarea, 'Tarea "' . $tarea->titulo . '" → ' . $tarea->estado_label, optional(auth()->user())->name . ' cambió el estado a "' . $tarea->estado_label . '".');

        return back()->with('success', 'Estado actualizado a ' . $tarea->estado_label . '.');
    }

    public function tareaDelete(AgenciaTarea $tarea)
    {
        $tarea->delete();
        return back()->with('success', 'Tarea eliminada.');
    }

    /** Detalle de un cliente con todas sus tareas (ver que falta por hacer). */
    public function clienteDetalle(AgenciaCliente $cliente)
    {
        $cliente->load(['tareas' => function ($q) {
            $q->with(['comparticiones.user', 'comentarios.autor', 'archivos.autor'])
              ->orderByRaw("FIELD(estado,'requiere_cambios','en_revision','en_curso','pendiente','borrador','terminado')")
              ->orderByDesc('created_at');
        }]);
        $colaboradores = User::where('role', 'colaborador')->orderBy('name')->get(['id', 'name', 'email']);

        return view('agencia.clientes.detalle', compact('cliente', 'colaboradores'));
    }

    /** Comparte una tarea por correo con uno o varios usuarios (ej. disenadores). */
    public function tareaCompartir(Request $request, AgenciaTarea $tarea)
    {
        $request->validate([
            'emails'   => 'required|array|min:1',
            'emails.*' => 'email',
        ]);

        $tarea->load('cliente');
        $enviados = 0;
        $errores = 0;

        foreach (array_unique($request->emails) as $email) {
            $email = trim($email);
            if ($email === '') {
                continue;
            }

            // Si el email corresponde a un usuario existente, lo enlazamos a la comparticion.
            $user = User::where('email', $email)->first();

            AgenciaTareaComparticion::updateOrCreate(
                ['agencia_tarea_id' => $tarea->id, 'email' => $email],
                ['user_id' => $user?->id, 'compartida_en' => now()]
            );

            try {
                Mail::send(new \App\Mail\TareaCompartidaMail($tarea, $email));
                $enviados++;
            } catch (\Throwable $e) {
                $errores++;
                \Log::error('Error compartiendo tarea ' . $tarea->id . ' a ' . $email . ': ' . $e->getMessage());
            }
        }

        $msg = "Tarea compartida: {$enviados} correo(s) enviado(s)";
        if ($errores) {
            $msg .= ", {$errores} con error";
        }
        return back()->with('success', $msg . '.');
    }

    // ==========================================
    // VISTA DEL COLABORADOR (diseñador): solo sus tareas
    // ==========================================

    /** Panel del colaborador: solo tareas compartidas con el, o todas si tiene el permiso de panel. */
    public function misTareas(Request $request)
    {
        $user = auth()->user();
        $panelCompleto = $user->hasRole('admin') || $user->can('agencia.tareas');

        $query = AgenciaTarea::with(['cliente', 'comparticiones', 'comentarios.autor', 'archivos.autor']);
        if (!$panelCompleto) {
            $query->compartidasCon($user);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $tareas = $query->orderByRaw("FIELD(estado,'requiere_cambios','en_revision','en_curso','pendiente','borrador','terminado')")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        // Acuse de lectura: al abrir el panel, marca como vistas las tareas mostradas (y enlaza la cuenta).
        if (!$panelCompleto) {
            AgenciaTareaComparticion::whereIn('agencia_tarea_id', $tareas->pluck('id'))
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhere('email', $user->email);
                })
                ->update([
                    'user_id'                     => $user->id,
                    'primer_acceso_en'            => DB::raw('COALESCE(primer_acceso_en, NOW())'),
                    'ultimo_visto_comentarios_en' => now(),
                ]);
        }

        return view('agencia.tareas.mias', compact('tareas', 'panelCompleto'));
    }

    /** El colaborador cambia el estado de UNA tarea (solo si esta compartida con el). */
    public function miTareaEstado(Request $request, AgenciaTarea $tarea)
    {
        $user = auth()->user();
        $request->validate(['estado' => 'required|in:' . implode(',', AgenciaTarea::ESTADOS)]);

        $permitido = $user->hasRole('admin')
            || $user->can('agencia.tareas')
            || $tarea->comparticiones()->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('email', $user->email);
            })->exists();
        if (!$permitido) {
            abort(403, 'Esta tarea no esta compartida contigo.');
        }

        $tarea->estado = $request->estado;
        $tarea->terminada_en = $request->estado === 'terminado' ? ($tarea->terminada_en ?? now()) : null;
        $tarea->save();

        $this->marcarVistoTarea($tarea, $user);
        $this->notificarTarea($tarea, 'Tarea "' . $tarea->titulo . '" → ' . $tarea->estado_label, $user->name . ' cambió el estado a "' . $tarea->estado_label . '".');

        return back()->with('success', 'Estado actualizado.');
    }

    // ==========================================
    // PACK DE COMUNICACIÓN: comentarios, archivos, visto, notificaciones
    // ==========================================

    /** Comentario del ADMIN en una tarea (notifica a los colaboradores compartidos). */
    public function tareaComentarStore(Request $request, AgenciaTarea $tarea)
    {
        $data = $request->validate([
            'cuerpo'         => 'required|string|max:5000',
            'enlace_externo' => 'nullable|url|max:500',
        ]);

        $tarea->comentarios()->create([
            'autor_user_id'  => auth()->id(),
            'autor_email'    => auth()->user()->email,
            'rol'            => 'admin',
            'cuerpo'         => $data['cuerpo'],
            'enlace_externo' => $data['enlace_externo'] ?? null,
        ]);

        $this->notificarTarea($tarea, 'Nuevo comentario en: ' . $tarea->titulo, auth()->user()->name . ' escribió: ' . \Illuminate\Support\Str::limit($data['cuerpo'], 140));
        return back()->with('success', 'Comentario agregado.');
    }

    /** Comentario del COLABORADOR (solo en tareas compartidas con él; notifica al admin). */
    public function miTareaComentarStore(Request $request, AgenciaTarea $tarea)
    {
        $user = auth()->user();
        $this->autorizarColaboradorTarea($tarea, $user);

        $data = $request->validate([
            'cuerpo'         => 'required|string|max:5000',
            'enlace_externo' => 'nullable|url|max:500',
        ]);

        $tarea->comentarios()->create([
            'autor_user_id'  => $user->id,
            'autor_email'    => $user->email,
            'rol'            => 'colaborador',
            'cuerpo'         => $data['cuerpo'],
            'enlace_externo' => $data['enlace_externo'] ?? null,
        ]);

        $this->marcarVistoTarea($tarea, $user);
        $this->notificarTarea($tarea, 'Nuevo comentario en: ' . $tarea->titulo, $user->name . ' escribió: ' . \Illuminate\Support\Str::limit($data['cuerpo'], 140));
        return back()->with('success', 'Comentario agregado.');
    }

    /** ADMIN sube un brief/referencia a la tarea. */
    public function tareaArchivoStore(Request $request, AgenciaTarea $tarea)
    {
        $request->validate(['archivo' => 'required|file|mimes:jpg,jpeg,png,webp,svg,gif,pdf,ai,eps,zip,rar,psd,doc,docx,xls,xlsx,csv,ppt,pptx|max:20480']);
        $file = $request->file('archivo');
        $this->guardarArchivoTarea($tarea, $file, 'brief', auth()->user());
        $this->notificarTarea($tarea, 'Nuevo archivo en: ' . $tarea->titulo, auth()->user()->name . ' adjuntó: ' . $file->getClientOriginalName());
        return back()->with('success', 'Archivo subido.');
    }

    /** COLABORADOR sube un entregable (solo en tareas compartidas con él; notifica al admin). */
    public function miTareaArchivoStore(Request $request, AgenciaTarea $tarea)
    {
        $user = auth()->user();
        $this->autorizarColaboradorTarea($tarea, $user);
        $request->validate(['archivo' => 'required|file|mimes:jpg,jpeg,png,webp,svg,gif,pdf,ai,eps,zip,rar,psd,doc,docx,xls,xlsx,csv,ppt,pptx|max:20480']);
        $file = $request->file('archivo');
        $this->guardarArchivoTarea($tarea, $file, 'entregable', $user);
        $this->marcarVistoTarea($tarea, $user);
        $this->notificarTarea($tarea, 'Nuevo entregable en: ' . $tarea->titulo, $user->name . ' subió: ' . $file->getClientOriginalName());
        return back()->with('success', 'Entregable subido.');
    }

    /** Descarga gateada de un adjunto (admin/creador o quien tenga la tarea compartida). */
    public function tareaArchivoDescargar(AgenciaTareaArchivo $archivo)
    {
        $tarea = $archivo->tarea;
        $user = auth()->user();
        $permitido = $user->hasRole('admin') || $user->can('agencia.tareas')
            || $tarea->comparticiones()->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('email', $user->email);
            })->exists();
        if (!$permitido) {
            abort(403);
        }
        $path = \Storage::disk('public')->path($archivo->ruta);
        if (!is_file($path)) {
            abort(404);
        }
        return response()->download($path, $archivo->nombre_original);
    }

    /** Elimina un adjunto (solo quien lo subió o un admin). */
    public function tareaArchivoDestroy(AgenciaTareaArchivo $archivo)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && (int) $archivo->subido_por_user_id !== (int) $user->id) {
            abort(403);
        }
        \Storage::disk('public')->delete($archivo->ruta);
        $archivo->delete();
        return back()->with('success', 'Archivo eliminado.');
    }

    /** Acuse de lectura del colaborador al abrir una tarea (fetch). */
    public function miTareaVisto(AgenciaTarea $tarea)
    {
        $user = auth()->user();
        $this->autorizarColaboradorTarea($tarea, $user);
        $this->marcarVistoTarea($tarea, $user);
        return response()->json(['ok' => true]);
    }

    // ---------- Helpers del pack ----------

    private function autorizarColaboradorTarea(AgenciaTarea $tarea, $user): void
    {
        $permitido = $user->hasRole('admin') || $user->can('agencia.tareas')
            || $tarea->comparticiones()->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('email', $user->email);
            })->exists();
        if (!$permitido) {
            abort(403, 'Esta tarea no esta compartida contigo.');
        }
    }

    private function guardarArchivoTarea(AgenciaTarea $tarea, $file, string $tipo, $user): AgenciaTareaArchivo
    {
        $ruta = $file->store('agencia_tarea_archivos/' . $tarea->id, 'public');
        return $tarea->archivos()->create([
            'tipo'               => $tipo,
            'subido_por_user_id' => $user->id,
            'nombre_original'    => $file->getClientOriginalName(),
            'ruta'               => $ruta,
            'mime_type'          => $file->getClientMimeType(),
            'tamano_bytes'       => $file->getSize(),
        ]);
    }

    /** Marca como vista (acuse + enlaza cuenta) la comparticion del colaborador. */
    private function marcarVistoTarea(AgenciaTarea $tarea, $user): void
    {
        if ($user->hasRole('admin')) {
            return;
        }
        $tarea->comparticiones()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('email', $user->email);
            })
            ->update([
                'user_id'                     => $user->id,
                'primer_acceso_en'            => DB::raw('COALESCE(primer_acceso_en, NOW())'),
                'ultimo_visto_comentarios_en' => now(),
            ]);
    }

    /** Notifica por correo a los involucrados (colaboradores + admin creador), excluyendo al autor. */
    private function notificarTarea(AgenciaTarea $tarea, string $titulo, string $mensaje): void
    {
        $tarea->loadMissing(['comparticiones', 'creador']);
        $autorEmail = optional(auth()->user())->email;
        $destinatarios = $tarea->comparticiones->pluck('email')
            ->push(optional($tarea->creador)->email)
            ->filter()
            ->reject(fn ($e) => $e === $autorEmail)
            ->unique()
            ->values();

        foreach ($destinatarios as $email) {
            try {
                Mail::send(new \App\Mail\TareaNotificacionMail($tarea, $email, $titulo, $mensaje));
            } catch (\Throwable $e) {
                \Log::error('Error notificando tarea ' . $tarea->id . ' a ' . $email . ': ' . $e->getMessage());
            }
        }
    }
}
