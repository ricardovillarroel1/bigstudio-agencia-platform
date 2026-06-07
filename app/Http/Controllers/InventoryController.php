<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IntegracionConfig;
use App\Models\ProductMapping;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\InventorySyncService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Vista principal de inventario
     * Admin: muestra lista de clientes con resumen; al seleccionar uno muestra detalle
     * Cliente: muestra directamente su inventario
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');
        $routePrefix = $isAdmin ? 'integracion.inventario' : 'cliente.inventario';

        if ($isAdmin) {
            // Obtener todos los configs
            $configs = IntegracionConfig::all();
            $selectedConfigId = request('config_id');

            // Construir lista de clientes con estadísticas resumidas
            $clients = [];
            foreach ($configs as $config) {
                $clientUser = User::find($config->user_id);
                $mappingCount = ProductMapping::where('user_id', $config->user_id)->count();
                $mappedCount = ProductMapping::where('user_id', $config->user_id)
                    ->whereNotNull('lioren_product_id')->count();
                $errorsToday = SyncLog::where('user_id', $config->user_id)
                    ->where('status', 'error')
                    ->whereDate('created_at', today())->count();
                $lastSync = SyncLog::where('user_id', $config->user_id)
                    ->orderBy('created_at', 'desc')->first();

                $clients[] = [
                    'config' => $config,
                    'user' => $clientUser,
                    'total_products' => $mappingCount,
                    'mapped' => $mappedCount,
                    'unmapped' => $mappingCount - $mappedCount,
                    'errors_today' => $errorsToday,
                    'sync_enabled' => $config->sync_inventario_enabled,
                    'activo' => $config->activo,
                    'last_sync' => $lastSync ? $lastSync->created_at : null,
                ];
            }

            // Si se seleccionó un cliente, cargar su detalle
            $selectedConfig = null;
            $mappings = [];
            $syncLogs = [];
            $stats = [];

            if ($selectedConfigId) {
                $selectedConfig = $configs->firstWhere('id', $selectedConfigId);
                if ($selectedConfig) {
                    $userId = $selectedConfig->user_id;
                    $mappings = ProductMapping::where('user_id', $userId)
                        ->orderBy('product_title')
                        ->get();
                    $syncLogs = SyncLog::where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->limit(50)
                        ->get();
                    $inventorySync = new InventorySyncService($userId);
                    $stats = $inventorySync->getStats();
                }
            }

            return view('integracion.inventario', compact(
                'configs', 'clients', 'selectedConfig', 'selectedConfigId',
                'mappings', 'syncLogs', 'stats', 'isAdmin', 'routePrefix'
            ));
        } else {
            // Vista de cliente: mostrar directamente su inventario
            $configs = IntegracionConfig::where('user_id', $user->id)
                ->get();
            $selectedConfigId = request('config_id', $configs->first()->id ?? null);
            $selectedConfig = $configs->firstWhere('id', $selectedConfigId);
            $clients = [];
            $mappings = [];
            $syncLogs = [];
            $stats = [];

            if ($selectedConfig) {
                $userId = $selectedConfig->user_id;
                $buscarProd = trim((string) request('buscar_prod', ''));
                $filtroEstado = request('filtro_estado', 'todos');
                $mq = ProductMapping::where('user_id', $userId);
                if ($buscarProd !== '') {
                    $mq->where(function ($q) use ($buscarProd) {
                        $q->where('product_title', 'like', "%{$buscarProd}%")
                          ->orWhere('sku', 'like', "%{$buscarProd}%");
                    });
                }
                if ($filtroEstado === 'mapeados') {
                    $mq->whereNotNull('lioren_product_id');
                } elseif ($filtroEstado === 'sin_mapear') {
                    $mq->whereNull('lioren_product_id');
                } elseif ($filtroEstado === 'error') {
                    $mq->where('sync_status', 'error');
                }
                // Contadores globales para los chips de filtro (sobre TODO el catalogo del cliente)
                $countAll = ProductMapping::where('user_id', $userId)->count();
                $countMapped = ProductMapping::where('user_id', $userId)->whereNotNull('lioren_product_id')->count();
                $countUnmapped = ProductMapping::where('user_id', $userId)->whereNull('lioren_product_id')->count();
                $countError = ProductMapping::where('user_id', $userId)->where('sync_status', 'error')->count();
                $mappings = $mq->orderBy('product_title')
                    ->paginate(10, ['*'], 'prod_page')
                    ->appends(request()->except('prod_page'));
                $syncLogs = SyncLog::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->paginate(5, ['*'], 'sync_page')
                    ->appends(request()->except('sync_page'));
                $inventorySync = new InventorySyncService($userId);
                $stats = $inventorySync->getStats();
            }

            return view('integracion.inventario', compact(
                'configs', 'clients', 'selectedConfig', 'selectedConfigId',
                'mappings', 'syncLogs', 'stats', 'isAdmin', 'routePrefix',
                'countAll', 'countMapped', 'countUnmapped', 'countError'
            ));
        }
    }

    /**
     * Sincronización completa de productos
     */
    public function fullSync(Request $request)
    {
        $configId = $request->input('config_id');
        $config = IntegracionConfig::findOrFail($configId);

        $inventorySync = new InventorySyncService($config->user_id);
        $result = $inventorySync->fullSync();

        $routeName = auth()->user()->hasRole('admin') ? 'integracion.inventario' : 'cliente.inventario';

        if ($result['success']) {
            return redirect()->route($routeName, ['config_id' => $configId])
                ->with('success', "Sincronización completa: {$result['synced']} productos sincronizados" . (($result['auto_mapped'] ?? 0) > 0 ? ", {$result['auto_mapped']} auto-mapeados con Lioren" : "") .
                    ($result['errors'] > 0 ? ", {$result['errors']} errores" : ""));
        } else {
            return redirect()->route($routeName, ['config_id' => $configId])
                ->withErrors(['error' => 'Error en sincronización: ' . ($result['error'] ?? 'Error desconocido')]);
        }
    }

    /**
     * Mapear un producto de Shopify a Lioren
     */
    public function mapProduct(Request $request)
    {
        $request->validate([
            'mapping_id' => 'required|exists:product_mappings,id',
            'lioren_product_id' => 'required|string',
        ]);

        $mapping = ProductMapping::findOrFail($request->mapping_id);
        $mapping->update([
            'lioren_product_id' => $request->lioren_product_id,
            'sync_status' => 'mapped',
            'last_synced_at' => now(),
        ]);

        return redirect()->back()->with('success', "Producto '{$mapping->product_title}' mapeado exitosamente a Lioren #{$request->lioren_product_id}");
    }

    /**
     * Desmapear un producto
     */
    public function unmapProduct(Request $request)
    {
        $mapping = ProductMapping::findOrFail($request->mapping_id);
        $mapping->update([
            'lioren_product_id' => null,
            'sync_status' => 'pending',
        ]);

        return redirect()->back()->with('success', "Mapeo eliminado para '{$mapping->product_title}'");
    }

    /**
     * Obtener productos de Lioren (API JSON)
     */
    public function getLiorenProducts(Request $request)
    {
        $configId = $request->input('config_id');
        $config = IntegracionConfig::findOrFail($configId);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$config->lioren_api_key}",
                'Accept' => 'application/json',
            ])->get('https://www.lioren.cl/api/productos?rpp=100');

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'products' => $response->json(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo productos de Lioren',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener logs de sincronización (API JSON)
     */
    public function getSyncLogs(Request $request)
    {
        $configId = $request->input('config_id');
        $config = IntegracionConfig::findOrFail($configId);

        $logs = SyncLog::where('user_id', $config->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }
}
