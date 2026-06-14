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
            return back()->with('success', 'Tarea creada en Notion.');
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo crear en Notion: ' . $e->getMessage());
        }
    }
}
