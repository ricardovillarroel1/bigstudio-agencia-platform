<?php

namespace App\Services;

use App\Models\MetaAdAccount;
use App\Models\MetaAdInsight;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de integración con Meta Marketing API (Facebook/Instagram Ads).
 *
 * FASE 1 (actual): infraestructura lista. El token se guarda en settings (key 'meta_system_token').
 * Mientras no haya token, fetchInsights() usa datos de ejemplo (demo) para que el reporte
 * se vea funcionando. Cuando se pegue el token real, llama a la API de Meta automáticamente.
 */
class MetaAdsService
{
    protected string $apiVersion = 'v21.0';
    protected ?string $token;

    public function __construct()
    {
        $this->token = $this->getToken();
    }

    /** Token de System User guardado en settings. */
    public function getToken(): ?string
    {
        try {
            $row = DB::table('settings')->where('key', 'meta_system_token')->first();
            return $row->value ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function hasToken(): bool
    {
        return !empty($this->token);
    }

    /**
     * Lista las cuentas publicitarias accesibles con el token (para autocompletar al vincular).
     * Devuelve [] si no hay token o si falla.
     */
    public function listAdAccounts(): array
    {
        if (!$this->hasToken()) return [];
        try {
            $resp = Http::get("https://graph.facebook.com/{$this->apiVersion}/me/adaccounts", [
                'fields' => 'account_id,name,currency',
                'access_token' => $this->token,
                'limit' => 200,
            ]);
            if ($resp->successful()) {
                return $resp->json('data', []);
            }
            Log::warning('Meta listAdAccounts fallo', ['body' => $resp->body()]);
        } catch (\Throwable $e) {
            Log::error('Meta listAdAccounts error: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * Descarga insights de una cuenta para un período (mes 'YYYY-MM') y los persiste.
     * Sin token -> genera datos demo deterministas para esa cuenta/período.
     * @return array Resumen guardado (nivel cuenta).
     */
    public function syncAccount(MetaAdAccount $cuenta, string $periodo): array
    {
        [$desde, $hasta] = $this->rangoMes($periodo);
        if ($this->hasToken()) {
            return $this->syncFromApi($cuenta, $periodo, $desde, $hasta);
        }
        return $this->syncDemo($cuenta, $periodo);
    }

    /** Sincroniza un RANGO de fechas personalizado (YYYY-MM-DD a YYYY-MM-DD). */
    public function syncAccountRango(MetaAdAccount $cuenta, string $desde, string $hasta): string
    {
        $key = $desde . '_' . $hasta; // clave de período para rango custom
        if ($this->hasToken()) {
            $this->syncFromApi($cuenta, $key, $desde, $hasta);
        } else {
            $this->syncDemo($cuenta, $key);
        }
        return $key;
    }

    /** Llama a la Meta Marketing API real. */
    protected function syncFromApi(MetaAdAccount $cuenta, string $periodo, ?string $desde = null, ?string $hasta = null): array
    {
        try {
            if (!$desde || !$hasta) {
                [$desde, $hasta] = $this->rangoMes($periodo);
            }
            $base = "https://graph.facebook.com/{$this->apiVersion}/{$cuenta->act_id}/insights";
            $timeRange = json_encode(['since' => $desde, 'until' => $hasta]);

            // ===== Nivel cuenta =====
            $cuentaResp = Http::get($base, [
                'fields' => 'spend,impressions,reach,clicks,actions,action_values',
                'time_range' => $timeRange,
                'access_token' => $this->token,
            ]);
            $cuentaData = $cuentaResp->json('data.0', []);
            $resumen = $this->mapInsight($cuentaData);
            $this->guardar($cuenta, $periodo, 'cuenta', null, $cuenta->nombre_cuenta, $resumen);

            // ===== Limpia campañas previas de este período (evita duplicados/zombies) =====
            MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'campania')->delete();

            // ===== Insights por campaña (con paginación: trae TODAS) =====
            $insightsPorCampania = [];
            $url = $base;
            $params = [
                'level' => 'campaign',
                'fields' => 'campaign_id,campaign_name,spend,impressions,reach,clicks,actions,action_values',
                'time_range' => $timeRange,
                'access_token' => $this->token,
                'limit' => 200,
            ];
            $guard = 0;
            do {
                $resp = $guard === 0 ? Http::get($url, $params) : Http::get($url);
                $json = $resp->json();
                foreach (($json['data'] ?? []) as $camp) {
                    if (!empty($camp['campaign_id'])) {
                        $insightsPorCampania[$camp['campaign_id']] = $camp;
                    }
                }
                $url = $json['paging']['next'] ?? null;
                $guard++;
            } while ($url && $guard < 20);

            // ===== Lista de campañas ACTIVAS (aunque no tengan gasto en el período) =====
            $campStatusUrl = "https://graph.facebook.com/{$this->apiVersion}/{$cuenta->act_id}/campaigns";
            $campañas = [];
            $url = $campStatusUrl;
            $params = [
                'fields' => 'id,name,status,effective_status',
                'effective_status' => json_encode(['ACTIVE']),
                'access_token' => $this->token,
                'limit' => 200,
            ];
            $guard = 0;
            do {
                $resp = $guard === 0 ? Http::get($url, $params) : Http::get($url);
                $json = $resp->json();
                foreach (($json['data'] ?? []) as $c) {
                    $campañas[$c['id']] = $c['name'] ?? 'Campaña';
                }
                $url = $json['paging']['next'] ?? null;
                $guard++;
            } while ($url && $guard < 20);

            // ===== Unir: todas las activas + las que tuvieron insights =====
            $idsTotales = array_unique(array_merge(array_keys($campañas), array_keys($insightsPorCampania)));
            $guardadas = 0;
            foreach ($idsTotales as $cid) {
                $nombre = $campañas[$cid] ?? ($insightsPorCampania[$cid]['campaign_name'] ?? 'Campaña');
                $m = isset($insightsPorCampania[$cid])
                    ? $this->mapInsight($insightsPorCampania[$cid])
                    : ['inversion' => 0, 'ventas' => 0, 'compras' => 0, 'alcance' => 0, 'impresiones' => 0, 'clicks' => 0];
                $this->guardar($cuenta, $periodo, 'campania', $cid, $nombre, $m);
                $guardadas++;
            }

            $this->syncDemograficos($cuenta, $periodo, $base, $timeRange);

            // 3) Insights por CONJUNTO (adset) y ANUNCIO (ad) + demograficos
            try {
                MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodo)->whereIn('nivel', ['adset', 'ad'])->delete();
                $adsets = $this->fetchInsightsPaged($base, [
                    'level' => 'adset',
                    'fields' => 'campaign_id,campaign_name,adset_id,adset_name,spend,impressions,reach,clicks,actions,action_values',
                    'time_range' => $timeRange, 'access_token' => $this->token, 'limit' => 200,
                ]);
                foreach ($adsets as $r) {
                    if (empty($r['adset_id'])) continue;
                    $this->guardar($cuenta, $periodo, 'adset', $r['adset_id'], $r['adset_name'] ?? 'Conjunto', $this->mapInsight($r),
                        ['campaign_id' => $r['campaign_id'] ?? null, 'campaign_name' => $r['campaign_name'] ?? null]);
                }
                $ads = $this->fetchInsightsPaged($base, [
                    'level' => 'ad',
                    'fields' => 'campaign_id,campaign_name,adset_id,adset_name,ad_id,ad_name,spend,impressions,reach,clicks,actions,action_values',
                    'time_range' => $timeRange, 'access_token' => $this->token, 'limit' => 200,
                ]);
                foreach ($ads as $r) {
                    if (empty($r['ad_id'])) continue;
                    $this->guardar($cuenta, $periodo, 'ad', $r['ad_id'], $r['ad_name'] ?? 'Anuncio', $this->mapInsight($r),
                        ['campaign_id' => $r['campaign_id'] ?? null, 'campaign_name' => $r['campaign_name'] ?? null,
                         'adset_id' => $r['adset_id'] ?? null, 'adset_name' => $r['adset_name'] ?? null]);
                }
                $this->syncDemograficos($cuenta, $periodo, $base, $timeRange);
            } catch (\Throwable $e) {
                Log::error('Meta sync adset/ad/demo: ' . $e->getMessage());
            }

            Log::info('Meta sync OK', [
                'cuenta' => $cuenta->act_id, 'periodo' => $periodo,
                'campanias_activas' => count($campañas),
                'campanias_con_insights' => count($insightsPorCampania),
                'campanias_guardadas' => $guardadas,
            ]);

            $cuenta->update(['ultima_sync_at' => now()]);
            return $resumen;
        } catch (\Throwable $e) {
            Log::error('Meta syncFromApi error: ' . $e->getMessage());
            return [];
        }
    }

    /** Extrae métricas relevantes del payload de Meta.
     *  IMPORTANTE: usa UN solo tipo de conversión ('omni_purchase'), que es el que Meta
     *  usa para su ROAS oficial en Ads Manager. NO sumar varios tipos (se solapan = doble). */
    protected function mapInsight(array $d): array
    {
        $tipo = 'omni_purchase';
        $compras = 0; $ventas = 0;

        foreach (($d['actions'] ?? []) as $a) {
            if (($a['action_type'] ?? '') === $tipo) {
                $compras = (int) ($a['value'] ?? 0);
                break;
            }
        }
        foreach (($d['action_values'] ?? []) as $a) {
            if (($a['action_type'] ?? '') === $tipo) {
                $ventas = (int) round($a['value'] ?? 0);
                break;
            }
        }

        return [
            'inversion' => (int) round($d['spend'] ?? 0),
            'ventas' => $ventas,
            'compras' => $compras,
            'alcance' => (int) ($d['reach'] ?? 0),
            'impresiones' => (int) ($d['impressions'] ?? 0),
            'clicks' => (int) ($d['clicks'] ?? 0),
        ];
    }

    /** Genera datos demo deterministas (mismos números para misma cuenta+periodo). */
    protected function syncDemo(MetaAdAccount $cuenta, string $periodo): array
    {
        $seed = crc32($cuenta->act_id . $periodo);
        $rand = fn($min, $max) => $min + ($seed % max(1, ($max - $min)));
        $inversion = $rand(800000, 2200000);
        $roas = (3 + ($seed % 30) / 10); // 3.0 - 6.0
        $ventas = (int) round($inversion * $roas);
        $compras = $rand(120, 480);
        $resumen = [
            'inversion' => $inversion,
            'ventas' => $ventas,
            'compras' => $compras,
            'alcance' => $rand(80000, 250000),
            'impresiones' => $rand(300000, 900000),
            'clicks' => $rand(6000, 22000),
        ];
        $this->guardar($cuenta, $periodo, 'cuenta', null, $cuenta->nombre_cuenta, $resumen);
        return $resumen;
    }

    protected function guardar(MetaAdAccount $cuenta, string $periodo, string $nivel, ?string $objetoId, ?string $objetoNombre, array $m, array $extra = []): void
    {
        MetaAdInsight::updateOrCreate(
            [
                'meta_ad_account_id' => $cuenta->id,
                'periodo' => $periodo,
                'nivel' => $nivel,
                'objeto_id' => $objetoId,
            ],
            array_merge($m, ['objeto_nombre' => $objetoNombre, 'extra' => $extra ?: null])
        );
    }

    /** Pagina un endpoint de insights de Meta y devuelve TODAS las filas (data). */
    protected function fetchInsightsPaged(string $url, array $params): array
    {
        $rows = [];
        $guard = 0;
        do {
            $resp = $guard === 0 ? Http::get($url, $params) : Http::get($url);
            $json = $resp->json();
            foreach (($json['data'] ?? []) as $r) { $rows[] = $r; }
            $url = $json['paging']['next'] ?? null;
            $guard++;
        } while ($url && $guard < 20);
        return $rows;
    }

    /** Sincroniza desgloses demograficos (edad, sexo, region) a nivel cuenta. */
    protected function syncDemograficos(MetaAdAccount $cuenta, string $periodo, string $base, string $timeRange): void
    {
        $mapa = ['demo_age' => 'age', 'demo_gender' => 'gender', 'demo_region' => 'region'];
        try {
            MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->whereIn('nivel', array_keys($mapa))->delete();
            foreach ($mapa as $nivel => $bd) {
                $rows = $this->fetchInsightsPaged($base, [
                    'breakdowns' => $bd,
                    'fields' => 'spend,impressions,reach,clicks,actions,action_values',
                    'time_range' => $timeRange,
                    'access_token' => $this->token,
                    'limit' => 500,
                ]);
                foreach ($rows as $r) {
                    $val = (string) ($r[$bd] ?? 'Desconocido');
                    if ($val === '') { $val = 'Desconocido'; }
                    $this->guardar($cuenta, $periodo, $nivel, $val, $val, $this->mapInsight($r));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Meta sync demograficos error: ' . $e->getMessage());
        }
    }

    protected function rangoMes(string $periodo): array
    {
        [$y, $mo] = explode('-', $periodo);
        $desde = sprintf('%04d-%02d-01', $y, $mo);
        $hasta = date('Y-m-t', strtotime($desde));
        return [$desde, $hasta];
    }
}
