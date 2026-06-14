<?php

namespace App\Http\Controllers;

use App\Services\NotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Módulo de tareas alimentado por Notion (la fuente de verdad).
 * Lee y escribe la base "GESTIÓN DE TAREAS — EQUIPO" vía la API de Notion.
 */
class NotionTareasController extends Controller
{
    public const ESTADOS      = ['📋 Por hacer', '🔨 En progreso', '👀 En revisión', '🚫 Bloqueado', '✅ Hecho'];
    public const PRIORIDADES  = ['🔴 Alta', '🟡 Media', '🟢 Baja'];
    public const CLIENTES     = ['Eusebella', 'Machiques', 'Langford', 'Renegade', 'Botas Militares', 'BIG STUDIO'];
    public const AREAS        = ['Ads / Meta', 'Desarrollo Shopify', 'Diseño', 'Contenido', 'SEO', 'Integraciones', 'Customer Success', 'Administración'];
    public const RESPONSABLES = ['Ricardo (Dueño)', 'Ariel (Diseñador gráfico)'];

    public function __construct(protected NotionService $notion)
    {
    }

    protected function tareasCache(): array
    {
        return Cache::remember('notion_tareas', (int) config('notion.cache_ttl', 60), fn () => $this->notion->tareas());
    }

    public function index(Request $request)
    {
        $base = [
            'estados' => self::ESTADOS, 'prioridades' => self::PRIORIDADES,
            'clientes' => self::CLIENTES, 'areas' => self::AREAS, 'responsables' => self::RESPONSABLES,
        ];

        if (!$this->notion->configurado()) {
            return view('agencia.notion.index', array_merge($base, ['error' => 'Falta configurar el token de Notion (NOTION_TOKEN).', 'porEstado' => [], 'total' => 0]));
        }

        try {
            $tareas = collect($this->tareasCache());
        } catch (\Throwable $e) {
            return view('agencia.notion.index', array_merge($base, ['error' => 'No se pudo leer Notion: ' . $e->getMessage(), 'porEstado' => [], 'total' => 0]));
        }

        if ($request->filled('cliente')) {
            $tareas = $tareas->where('cliente', $request->cliente);
        }
        if ($request->filled('buscar')) {
            $b = mb_strtolower($request->buscar);
            $tareas = $tareas->filter(fn ($t) => str_contains(mb_strtolower(($t['titulo'] ?? '') . ' ' . ($t['cliente'] ?? '')), $b));
        }

        $porEstado = [];
        foreach (self::ESTADOS as $e) {
            $porEstado[$e] = $tareas->where('estado', $e)->values()->all();
        }

        return view('agencia.notion.index', array_merge($base, [
            'porEstado' => $porEstado,
            'total'     => $tareas->count(),
            'error'     => null,
        ]));
    }

    /** Cambia el estado de una tarea (drag & drop). Devuelve JSON. */
    public function estado(Request $request, string $page)
    {
        $request->validate(['estado' => 'required|in:' . implode(',', self::ESTADOS)]);
        try {
            $this->notion->actualizarTarea($page, ['estado' => $request->estado]);
            Cache::forget('notion_tareas');
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Crea una tarea nueva directamente en Notion. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:200',
            'cliente'      => 'nullable|in:' . implode(',', self::CLIENTES),
            'area'         => 'nullable|in:' . implode(',', self::AREAS),
            'responsable'  => 'nullable|in:' . implode(',', self::RESPONSABLES),
            'estado'       => 'nullable|in:' . implode(',', self::ESTADOS),
            'prioridad'    => 'nullable|in:' . implode(',', self::PRIORIDADES),
            'fecha_limite' => 'nullable|date',
            'notas'        => 'nullable|string',
        ]);

        $props = [
            'titulo'      => $data['titulo'],
            'estado'      => $data['estado'] ?: '📋 Por hacer',
            'prioridad'   => $data['prioridad'] ?: '🟡 Media',
            'responsable' => $data['responsable'] ?: 'Ricardo (Dueño)',
        ];
        foreach (['cliente', 'area', 'notas'] as $k) {
            if (!empty($data[$k])) {
                $props[$k] = $data[$k];
            }
        }
        if (!empty($data['fecha_limite'])) {
            $props['fecha_limite'] = $data['fecha_limite'];
        }

        try {
            $this->notion->crearTarea($props);
            Cache::forget('notion_tareas');
            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'message' => 'Tarea creada en Notion.']);
            }
            return back()->with('success', 'Tarea creada en Notion.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', 'No se pudo crear en Notion: ' . $e->getMessage());
        }
    }

    /** Edición completa de una tarea (modal). */
    public function actualizar(Request $request, string $page)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:200',
            'cliente'      => 'nullable|in:' . implode(',', self::CLIENTES),
            'area'         => 'nullable|in:' . implode(',', self::AREAS),
            'responsable'  => 'nullable|in:' . implode(',', self::RESPONSABLES),
            'estado'       => 'required|in:' . implode(',', self::ESTADOS),
            'prioridad'    => 'nullable|in:' . implode(',', self::PRIORIDADES),
            'fecha_limite' => 'nullable|date',
            'notas'        => 'nullable|string',
        ]);
        try {
            $this->notion->actualizarTarea($page, [
                'titulo'       => $data['titulo'],
                'estado'       => $data['estado'],
                'prioridad'    => $data['prioridad'] ?? '',
                'cliente'      => $data['cliente'] ?? '',
                'area'         => $data['area'] ?? '',
                'responsable'  => $data['responsable'] ?? '',
                'fecha_limite' => $data['fecha_limite'] ?? null,
                'notas'        => $data['notas'] ?? '',
            ]);
            Cache::forget('notion_tareas');
            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'message' => 'Tarea actualizada.']);
            }
            return back()->with('success', 'Tarea actualizada en Notion.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', 'No se pudo actualizar: ' . $e->getMessage());
        }
    }

    /** Archiva (elimina) una tarea en Notion. */
    public function archivar(Request $request, string $page)
    {
        try {
            $this->notion->archivarTarea($page);
            Cache::forget('notion_tareas');
            if ($request->expectsJson()) {
                return response()->json(['ok' => true]);
            }
            return back()->with('success', 'Tarea archivada en Notion.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', 'No se pudo archivar: ' . $e->getMessage());
        }
    }

    /** Vista Por cliente (agrupada). */
    public function porCliente(Request $request)
    {
        $base = $this->opciones();
        try {
            $tareas = collect($this->tareasCache());
        } catch (\Throwable $e) {
            return view('agencia.notion.cliente', array_merge($base, ['error' => $e->getMessage(), 'porCliente' => collect()]));
        }
        if ($request->filled('buscar')) {
            $b = mb_strtolower($request->buscar);
            $tareas = $tareas->filter(fn ($t) => str_contains(mb_strtolower(($t['titulo'] ?? '') . ' ' . ($t['cliente'] ?? '')), $b));
        }
        $porCliente = $tareas->groupBy(fn ($t) => $t['cliente'] ?: 'Sin cliente')->sortKeys();
        return view('agencia.notion.cliente', array_merge($base, ['porCliente' => $porCliente, 'error' => null]));
    }

    /** Calendario mensual por fecha límite. */
    public function calendario(Request $request)
    {
        $base = $this->opciones();
        $year  = (int) ($request->get('y') ?: now()->year);
        $month = (int) ($request->get('m') ?: now()->month);
        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        $inicio = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();
        $extra = [
            'year' => $year, 'month' => $month, 'inicio' => $inicio, 'fin' => $fin,
            'prev' => $inicio->copy()->subMonthNoOverflow(), 'next' => $inicio->copy()->addMonthNoOverflow(),
        ];
        try {
            $tareas = collect($this->tareasCache());
        } catch (\Throwable $e) {
            return view('agencia.notion.calendario', array_merge($base, $extra, ['error' => $e->getMessage(), 'porDia' => collect()]));
        }
        $porDia = $tareas->filter(fn ($t) => !empty($t['fecha_limite']))
            ->filter(fn ($t) => \Carbon\Carbon::parse($t['fecha_limite'])->between($inicio, $fin))
            ->groupBy(fn ($t) => (int) \Carbon\Carbon::parse($t['fecha_limite'])->day);
        return view('agencia.notion.calendario', array_merge($base, $extra, ['porDia' => $porDia, 'error' => null]));
    }

    /** Fichas de cliente (lista, desde Notion). */
    public function clientes()
    {
        try {
            $clientes = Cache::remember('notion_clientes', (int) config('notion.cache_ttl', 60), fn () => $this->notion->clientes());
        } catch (\Throwable $e) {
            return view('agencia.notion.clientes', ['clientes' => [], 'error' => $e->getMessage()]);
        }
        return view('agencia.notion.clientes', ['clientes' => $clientes, 'error' => null]);
    }

    /** Ficha de un cliente con sus accesos (contenido de la página) y sus tareas. */
    public function clienteVer(string $page)
    {
        try {
            $clientes = Cache::remember('notion_clientes', (int) config('notion.cache_ttl', 60), fn () => $this->notion->clientes());
            $cliente  = collect($clientes)->firstWhere('id', $page);
            $bloques  = $this->notion->bloquesSimplificados($page);
            $tareas   = collect($this->tareasCache())->where('cliente', $cliente['nombre'] ?? '___nada___')->values()->all();
        } catch (\Throwable $e) {
            return view('agencia.notion.cliente-detalle', ['cliente' => null, 'bloques' => [], 'tareas' => [], 'error' => $e->getMessage()]);
        }
        return view('agencia.notion.cliente-detalle', ['cliente' => $cliente, 'bloques' => $bloques, 'tareas' => $tareas, 'error' => null]);
    }

    /** Detalle de una tarea: propiedades + contenido del cuerpo (brief/notas) de Notion. */
    public function tareaVer(string $page)
    {
        try {
            $tarea   = $this->notion->tarea($page);
            $bloques = $this->notion->bloquesSimplificados($page);
        } catch (\Throwable $e) {
            return view('agencia.notion.tarea-detalle', array_merge($this->opciones(), ['tarea' => null, 'bloques' => [], 'error' => $e->getMessage()]));
        }
        return view('agencia.notion.tarea-detalle', array_merge($this->opciones(), ['tarea' => $tarea, 'bloques' => $bloques, 'error' => null]));
    }

    /** Agrega una nota al cuerpo de la tarea en Notion. */
    public function tareaNota(Request $request, string $page)
    {
        $request->validate(['nota' => 'required|string|max:2000']);
        try {
            $this->notion->agregarNota($page, $request->nota);
            Cache::forget('notion_tareas');
            return back()->with('success', 'Nota agregada a la tarea.');
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo agregar la nota: ' . $e->getMessage());
        }
    }

    /** Edita las propiedades de una ficha de cliente en Notion. */
    public function clienteActualizar(Request $request, string $page)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:200',
            'estado'    => 'nullable|string|max:60',
            'sitio_web' => 'nullable|string|max:300',
            'email'     => 'nullable|email',
            'telefono'  => 'nullable|string|max:60',
            'rubro'     => 'nullable|string|max:300',
            'notas'     => 'nullable|string',
        ]);
        try {
            $this->notion->actualizarCliente($page, $data);
            Cache::forget('notion_clientes');
            return back()->with('success', 'Ficha actualizada en Notion.');
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar la ficha: ' . $e->getMessage());
        }
    }

    protected function opciones(): array
    {
        return [
            'estados' => self::ESTADOS, 'prioridades' => self::PRIORIDADES,
            'clientes' => self::CLIENTES, 'areas' => self::AREAS, 'responsables' => self::RESPONSABLES,
        ];
    }
}
