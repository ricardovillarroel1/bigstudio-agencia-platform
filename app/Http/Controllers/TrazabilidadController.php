<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Trazabilidad de productos (SKU): ver a quien se vendio un producto,
 * cuanto y cuando. Funciona para admin (todos los clientes) o un cliente
 * especifico (auth user).
 *
 * Fuentes de datos:
 *  - tabla `boletas` (campo JSON `detalles` con items)
 *  - tabla `facturas_emitidas` (campo JSON `detalles` con items)
 *
 * Estructura de cada item en `detalles`:
 *   { codigo, nombre, cantidad, unidad, precio, descuento?, exento?, montodescuento? }
 */
class TrazabilidadController extends Controller
{
    public function index(Request $request)
    {
        $user      = auth()->user();
        $isAdmin   = $user->hasRole('admin');

        $filtros = [
            'sku'       => trim((string) $request->query('sku', '')),
            'periodo'   => $request->query('periodo', 'mes'), // dia | semana | mes | anio | rango
            'desde'     => $request->query('desde', ''),
            'hasta'     => $request->query('hasta', ''),
            'user_id'   => $isAdmin ? $request->query('user_id', '') : (string) $user->id,
        ];

        // Resolver rango de fechas segun el periodo
        [$desde, $hasta] = $this->resolverRango($filtros);

        // Catalogo para autocomplete: SKUs unicos del scope (admin = todos, cliente = los suyos)
        $catalogoSkus = $this->cargarCatalogo($filtros['user_id']);

        // Lista de clientes (solo admin)
        $clientes = $isAdmin
            ? DB::table('users')->whereIn('id', function ($q) {
                  $q->select('user_id')->from('boletas')
                      ->union(DB::table('facturas_emitidas')->select('user_id'));
              })->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        // Si no hay SKU, no buscamos nada todavia
        $resultados = collect();
        $stats = [
            'unidades'        => 0,
            'monto_total'     => 0,
            'clientes_unicos' => 0,
            'ultima_venta'    => null,
            'producto_nombre' => null,
            'series_diarias'  => [],
            'top_compradores' => [],
        ];

        // Diagnostico extra: si filtro por cliente y no hay datos, ver si el SKU
        // pertenece a otro cliente o si simplemente no existe en ningun lado.
        $diagnostico = null;

        if ($filtros['sku'] !== '') {
            $resultados = $this->buscarVentasPorSku($filtros['sku'], $desde, $hasta, $filtros['user_id']);
            $stats = $this->calcularStats($resultados, $filtros['sku']);

            if ($resultados->isEmpty()) {
                $diagnostico = $this->diagnosticarSku($filtros['sku'], $filtros['user_id'], $isAdmin);
            }
        }

        return view('trazabilidad.index', compact(
            'filtros', 'desde', 'hasta', 'catalogoSkus', 'clientes',
            'resultados', 'stats', 'isAdmin', 'diagnostico'
        ));
    }

    /**
     * Cuando no hay resultados, intenta explicar por que:
     *  - El SKU no existe en ninguna boleta/factura del scope
     *  - El SKU existe pero en OTRO cliente (admin con filtro)
     *  - El SKU existe en el catalogo pero nunca se vendio
     */
    private function diagnosticarSku(string $sku, $userIdFiltrado, bool $isAdmin): array
    {
        $skuLike = '%"codigo":"' . $sku . '"%';
        $skuLikeSpace = '%"codigo": "' . $sku . '"%';

        // 1. ¿Aparece en alguna boleta o factura (sin filtro de user)?
        $ventasTotales = DB::table('boletas')
            ->where(function ($w) use ($skuLike, $skuLikeSpace) {
                $w->where('detalles', 'like', $skuLike)->orWhere('detalles', 'like', $skuLikeSpace);
            })
            ->count()
            + DB::table('facturas_emitidas')
                ->where(function ($w) use ($skuLike, $skuLikeSpace) {
                    $w->where('detalles', 'like', $skuLike)->orWhere('detalles', 'like', $skuLikeSpace);
                })
                ->count();

        // 2. ¿En que clientes aparece?
        $userIdsConVentas = DB::table('boletas')
            ->where(function ($w) use ($skuLike, $skuLikeSpace) {
                $w->where('detalles', 'like', $skuLike)->orWhere('detalles', 'like', $skuLikeSpace);
            })
            ->pluck('user_id')
            ->merge(
                DB::table('facturas_emitidas')
                    ->where(function ($w) use ($skuLike, $skuLikeSpace) {
                        $w->where('detalles', 'like', $skuLike)->orWhere('detalles', 'like', $skuLikeSpace);
                    })
                    ->pluck('user_id')
            )
            ->unique()
            ->filter()
            ->values();

        $clientesConVentas = $userIdsConVentas->isEmpty()
            ? collect()
            : DB::table('users')->whereIn('id', $userIdsConVentas)->get(['id', 'name']);

        // 3. ¿Esta en el catalogo product_mappings?
        $catalogo = DB::table('product_mappings')
            ->where('sku', $sku)
            ->get(['user_id', 'product_title']);

        $catalogoUserIds = $catalogo->pluck('user_id')->unique()->filter();
        $catalogoUsers = $catalogoUserIds->isEmpty()
            ? collect()
            : DB::table('users')->whereIn('id', $catalogoUserIds)->get(['id', 'name'])->keyBy('id');

        $nombreProducto = $catalogo->first()->product_title ?? null;

        // Clasificar el caso
        if ($ventasTotales === 0 && $catalogo->isEmpty()) {
            $caso = 'sku_inexistente';
        } elseif ($ventasTotales === 0 && $catalogo->isNotEmpty()) {
            $caso = 'sku_en_catalogo_sin_ventas';
        } elseif ($userIdFiltrado !== '' && !$userIdsConVentas->contains((int) $userIdFiltrado)) {
            $caso = 'sku_pertenece_a_otro_cliente';
        } else {
            $caso = 'sku_existe_fuera_del_rango';
        }

        return [
            'caso'                  => $caso,
            'ventas_totales'        => $ventasTotales,
            'clientes_con_ventas'   => $clientesConVentas,
            'esta_en_catalogo'      => $catalogo->isNotEmpty(),
            'catalogo_de_clientes'  => $catalogoUsers,
            'nombre_producto'       => $nombreProducto,
            'user_id_filtrado'      => $userIdFiltrado,
        ];
    }

    /**
     * Resuelve las fechas inicio/fin segun el periodo elegido.
     */
    private function resolverRango(array $filtros): array
    {
        $now = Carbon::now();
        switch ($filtros['periodo']) {
            case 'dia':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'semana':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'anio':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
            case 'rango':
                $d = $filtros['desde'] ? Carbon::parse($filtros['desde'])->startOfDay() : $now->copy()->startOfMonth();
                $h = $filtros['hasta'] ? Carbon::parse($filtros['hasta'])->endOfDay()   : $now->copy()->endOfDay();
                return [$d, $h];
            case 'mes':
            default:
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }
    }

    /**
     * Carga el catalogo de SKUs distintos del scope para autocomplete.
     */
    private function cargarCatalogo($userId)
    {
        $query = DB::table('product_mappings')
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->select('sku', DB::raw('MIN(product_title) as nombre'))
            ->groupBy('sku')
            ->orderBy('sku')
            ->limit(500);

        if ($userId !== '' && $userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    /**
     * Busca ventas (boletas + facturas_emitidas) que contengan el SKU,
     * dentro del rango y opcionalmente filtradas por cliente.
     */
    private function buscarVentasPorSku(string $sku, Carbon $desde, Carbon $hasta, $userId)
    {
        $skuEscaped = str_replace(['\\', '"', '%', '_'], ['\\\\', '\\"', '\\%', '\\_'], $sku);
        $like = '%"codigo":"' . $skuEscaped . '"%';
        // Tambien hay variantes con espacios alrededor del :
        $likeSpace = '%"codigo": "' . $skuEscaped . '"%';

        $ventas = collect();

        // --- Boletas ---
        $boletasQ = DB::table('boletas')
            ->whereBetween('fecha', [$desde, $hasta])
            ->where('status', 'emitida')
            ->where(function ($w) use ($like, $likeSpace) {
                $w->where('detalles', 'like', $like)->orWhere('detalles', 'like', $likeSpace);
            });
        if ($userId !== '' && $userId !== null) $boletasQ->where('user_id', $userId);

        foreach ($boletasQ->orderByDesc('fecha')->get() as $b) {
            $items = $this->extraerItems($b->detalles, $sku);
            foreach ($items as $item) {
                $ventas->push($this->normalizarVenta($b, $item, 'boleta'));
            }
        }

        // --- Facturas emitidas ---
        $facturasQ = DB::table('facturas_emitidas')
            ->whereBetween('emitida_at', [$desde, $hasta])
            ->where(function ($w) use ($like, $likeSpace) {
                $w->where('detalles', 'like', $like)->orWhere('detalles', 'like', $likeSpace);
            });
        if ($userId !== '' && $userId !== null) $facturasQ->where('user_id', $userId);

        foreach ($facturasQ->orderByDesc('emitida_at')->get() as $f) {
            $items = $this->extraerItems($f->detalles, $sku);
            foreach ($items as $item) {
                $ventas->push($this->normalizarVenta($f, $item, 'factura'));
            }
        }

        return $ventas->sortByDesc('fecha')->values();
    }

    /**
     * Decodifica el JSON detalles y devuelve solo los items cuyo codigo coincida.
     */
    private function extraerItems($detallesJson, string $sku): array
    {
        $arr = json_decode($detallesJson ?? '[]', true);
        if (!is_array($arr)) return [];
        return array_values(array_filter($arr, function ($it) use ($sku) {
            return isset($it['codigo']) && (string) $it['codigo'] === $sku;
        }));
    }

    /**
     * Normaliza una linea de venta a una estructura comun.
     */
    private function normalizarVenta($doc, array $item, string $tipo): array
    {
        $cantidad = (float) ($item['cantidad'] ?? 0);
        $precio   = (float) ($item['precio'] ?? 0);
        $desc     = (float) ($item['montodescuento'] ?? 0);
        $totalLn  = ($cantidad * $precio) - $desc;

        return [
            'tipo'             => $tipo, // boleta | factura
            'doc_id'           => $doc->id,
            'folio'            => $doc->folio ?? null,
            'fecha'            => $tipo === 'boleta'
                ? Carbon::parse($doc->fecha)
                : Carbon::parse($doc->emitida_at ?? $doc->created_at),
            'cliente_user_id'  => $doc->user_id,
            'receptor_nombre'  => $tipo === 'boleta'
                ? ($doc->receptor_nombre ?: 'Boleta sin receptor')
                : ($doc->razon_social ?: 'Sin receptor'),
            'receptor_rut'     => $tipo === 'boleta' ? ($doc->receptor_rut ?? '') : ($doc->rut_receptor ?? ''),
            'item_codigo'      => $item['codigo'] ?? '',
            'item_nombre'      => $item['nombre'] ?? '',
            'cantidad'         => $cantidad,
            'unidad'           => $item['unidad'] ?? 'UN',
            'precio'           => $precio,
            'descuento'        => $desc,
            'total_linea'      => $totalLn,
            'shopify_order_id' => $doc->shopify_order_id ?? null,
        ];
    }

    /**
     * Agrega stats (totales, top compradores, serie diaria).
     */
    private function calcularStats($ventas, string $sku): array
    {
        if ($ventas->isEmpty()) {
            return [
                'unidades'        => 0,
                'monto_total'     => 0,
                'clientes_unicos' => 0,
                'ultima_venta'    => null,
                'producto_nombre' => null,
                'series_diarias'  => [],
                'top_compradores' => [],
            ];
        }

        $unidades = (int) $ventas->sum('cantidad');
        $monto    = (int) $ventas->sum('total_linea');
        $rut      = $ventas->pluck('receptor_rut')->filter()->unique();
        $clientes = $rut->isNotEmpty() ? $rut->count() : $ventas->pluck('receptor_nombre')->unique()->count();
        $ultima   = $ventas->first()['fecha'] ?? null;
        $nombre   = $ventas->first()['item_nombre'] ?? null;

        // Top compradores (por RUT cuando hay, sino por nombre)
        $top = $ventas
            ->groupBy(function ($v) { return $v['receptor_rut'] ?: $v['receptor_nombre']; })
            ->map(function ($group) {
                return [
                    'nombre'   => $group->first()['receptor_nombre'],
                    'rut'      => $group->first()['receptor_rut'],
                    'unidades' => (int) $group->sum('cantidad'),
                    'monto'    => (int) $group->sum('total_linea'),
                    'compras'  => $group->count(),
                ];
            })
            ->sortByDesc('unidades')
            ->take(5)
            ->values();

        // Serie diaria para sparkline
        $serie = $ventas
            ->groupBy(function ($v) { return $v['fecha']->format('Y-m-d'); })
            ->map(function ($group) { return (int) $group->sum('cantidad'); })
            ->sortKeys();

        return [
            'unidades'        => $unidades,
            'monto_total'     => $monto,
            'clientes_unicos' => $clientes,
            'ultima_venta'    => $ultima,
            'producto_nombre' => $nombre,
            'series_diarias'  => $serie->toArray(),
            'top_compradores' => $top->toArray(),
        ];
    }
}
