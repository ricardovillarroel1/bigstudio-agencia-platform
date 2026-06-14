<?php

namespace App\Services;

use App\Models\GoogleAdAccount;
use App\Models\GoogleAdInsight;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de integración con Google Ads API (Google Marketing).
 *
 * FASE 1 (actual): infraestructura + modo DEMO.
 *   - Sin OAuth: genera datos deterministas (igual que MetaAdsService::syncDemo).
 *   - Con OAuth: usa el access_token para llamar a la Google Ads API real.
 *
 * Tokens guardados en tabla `settings`:
 *   - google_client_id
 *   - google_client_secret
 *   - google_developer_token
 *   - google_refresh_token       (obtenido vía OAuth)
 *   - google_access_token        (corto plazo, se refresca solo)
 *   - google_access_token_exp    (timestamp Unix de expiración)
 *   - google_login_customer_id   (MCC ID por defecto, opcional)
 */
class GoogleAdsService
{
    protected string $apiVersion = 'v18';
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $developerToken;
    protected ?string $refreshToken;

    public function __construct()
    {
        $this->clientId = $this->setting('google_client_id');
        $this->clientSecret = $this->setting('google_client_secret');
        $this->developerToken = $this->setting('google_developer_token');
        $this->refreshToken = $this->setting('google_refresh_token');
    }

    protected function setting(string $key): ?string
    {
        try {
            return DB::table('settings')->where('key', $key)->value('value');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function saveSetting(string $key, ?string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }

    public function canStartOAuth(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    public function hasCredentials(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->developerToken);
    }

    public function hasOAuthToken(): bool
    {
        return $this->canStartOAuth() && !empty($this->refreshToken);
    }

    public function hasToken(): bool
    {
        return $this->hasCredentials() && !empty($this->refreshToken);
    }

    /** URL para iniciar OAuth (el usuario será redirigido aquí). */
    public function buildAuthUrl(string $redirectUri, string $state = ''): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/adwords',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /** Intercambia el código de OAuth por refresh_token + access_token. */
    public function exchangeCodeForTokens(string $code, string $redirectUri): array
    {
        $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);
        if (!$resp->successful()) {
            throw new \RuntimeException('Google OAuth falló: ' . $resp->body());
        }
        $data = $resp->json();
        if (!empty($data['refresh_token'])) {
            $this->saveSetting('google_refresh_token', $data['refresh_token']);
            $this->refreshToken = $data['refresh_token'];
        }
        if (!empty($data['access_token'])) {
            $this->saveSetting('google_access_token', $data['access_token']);
            $this->saveSetting('google_access_token_exp', (string) (time() + ($data['expires_in'] ?? 3600)));
        }
        return $data;
    }

    /** Refresca el access_token usando el refresh_token guardado. */
    public function getAccessToken(): ?string
    {
        $cached = $this->setting('google_access_token');
        $exp = (int) ($this->setting('google_access_token_exp') ?? 0);
        if ($cached && time() < ($exp - 60)) {
            return $cached;
        }
        if (!$this->refreshToken) return null;
        $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        if (!$resp->successful()) {
            Log::warning('Google refresh fallo', ['body' => $resp->body()]);
            return null;
        }
        $data = $resp->json();
        $this->saveSetting('google_access_token', $data['access_token']);
        $this->saveSetting('google_access_token_exp', (string) (time() + ($data['expires_in'] ?? 3600)));
        return $data['access_token'];
    }

    /** Lista las cuentas accesibles (CustomerService.listAccessibleCustomers). */
    public function listAccessibleCustomers(): array
    {
        if (!$this->hasToken()) return [];
        $access = $this->getAccessToken();
        if (!$access) return [];
        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $access,
                'developer-token' => $this->developerToken,
            ])->get("https://googleads.googleapis.com/{$this->apiVersion}/customers:listAccessibleCustomers");
            if ($resp->successful()) {
                return $resp->json('resourceNames', []);
            }
            Log::warning('Google listAccessibleCustomers fallo', ['body' => $resp->body()]);
        } catch (\Throwable $e) {
            Log::error('Google listAccessibleCustomers: ' . $e->getMessage());
        }
        return [];
    }

    /** Sincroniza una cuenta para un período YYYY-MM. */
    public function syncAccount(GoogleAdAccount $cuenta, string $periodo): array
    {
        [$desde, $hasta] = $this->rangoMes($periodo);
        if ($this->hasToken()) {
            return $this->syncFromApi($cuenta, $periodo, $desde, $hasta);
        }
        return $this->syncDemo($cuenta, $periodo);
    }

    public function syncAccountRango(GoogleAdAccount $cuenta, string $desde, string $hasta): string
    {
        $key = $desde . '_' . $hasta;
        if ($this->hasToken()) {
            $this->syncFromApi($cuenta, $key, $desde, $hasta);
        } else {
            $this->syncDemo($cuenta, $key);
        }
        return $key;
    }

    /** Llama a la Google Ads API real. */
    protected function syncFromApi(GoogleAdAccount $cuenta, string $periodo, string $desde, string $hasta): array
    {
        try {
            $access = $this->getAccessToken();
            if (!$access) throw new \RuntimeException('No access_token disponible.');

            $customerId = str_replace('-', '', $cuenta->customer_id);
            $loginCustomerId = $cuenta->login_customer_id ?: $this->setting('google_login_customer_id');

            $headers = [
                'Authorization' => 'Bearer ' . $access,
                'developer-token' => $this->developerToken,
                'Content-Type' => 'application/json',
            ];
            if ($loginCustomerId) {
                $headers['login-customer-id'] = str_replace('-', '', $loginCustomerId);
            }

            // ===== Nivel cuenta =====
            $cuentaGaql = "SELECT metrics.cost_micros, metrics.impressions, metrics.clicks, metrics.conversions, metrics.conversions_value
                FROM customer
                WHERE segments.date BETWEEN '$desde' AND '$hasta'";
            $cuentaResp = Http::withHeaders($headers)->post(
                "https://googleads.googleapis.com/{$this->apiVersion}/customers/{$customerId}/googleAds:search",
                ['query' => $cuentaGaql]
            );
            $cuentaRow = $cuentaResp->json('results.0.metrics', []);
            $resumen = $this->mapMetrics($cuentaRow);
            $this->guardar($cuenta, $periodo, 'cuenta', null, $cuenta->nombre_cuenta, $resumen);

            // ===== Por campaña =====
            GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'campania')->delete();
            $campGaql = "SELECT campaign.id, campaign.name, metrics.cost_micros, metrics.impressions, metrics.clicks, metrics.conversions, metrics.conversions_value
                FROM campaign
                WHERE segments.date BETWEEN '$desde' AND '$hasta'";
            $campResp = Http::withHeaders($headers)->post(
                "https://googleads.googleapis.com/{$this->apiVersion}/customers/{$customerId}/googleAds:search",
                ['query' => $campGaql]
            );
            foreach (($campResp->json('results', [])) as $row) {
                $cid = $row['campaign']['id'] ?? null;
                $cname = $row['campaign']['name'] ?? 'Campaña';
                if (!$cid) continue;
                $this->guardar($cuenta, $periodo, 'campania', (string) $cid, $cname, $this->mapMetrics($row['metrics'] ?? []));
            }

            // ===== Demográficos: edad =====
            $this->syncDemograficosApi($cuenta, $periodo, $customerId, $headers, $desde, $hasta);

            $cuenta->update(['ultima_sync_at' => now()]);
            Log::info('Google Ads sync OK', ['cuenta' => $cuenta->customer_id, 'periodo' => $periodo]);
            return $resumen;
        } catch (\Throwable $e) {
            Log::error('Google Ads syncFromApi: ' . $e->getMessage());
            return [];
        }
    }

    protected function syncDemograficosApi(GoogleAdAccount $cuenta, string $periodo, string $customerId, array $headers, string $desde, string $hasta): void
    {
        $combos = [
            'demo_age' => ['view' => 'age_range_view', 'breakdown' => 'ad_group_criterion.age_range.type'],
            'demo_gender' => ['view' => 'gender_view', 'breakdown' => 'ad_group_criterion.gender.type'],
            'demo_region' => ['view' => 'geographic_view', 'breakdown' => 'geographic_view.country_criterion_id'],
        ];
        foreach ($combos as $nivel => $cfg) {
            try {
                GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodo)->where('nivel', $nivel)->delete();
                $gaql = "SELECT {$cfg['breakdown']}, metrics.cost_micros, metrics.impressions, metrics.clicks, metrics.conversions, metrics.conversions_value
                    FROM {$cfg['view']}
                    WHERE segments.date BETWEEN '$desde' AND '$hasta'";
                $resp = Http::withHeaders($headers)->post(
                    "https://googleads.googleapis.com/{$this->apiVersion}/customers/{$customerId}/googleAds:search",
                    ['query' => $gaql]
                );
                foreach (($resp->json('results', [])) as $row) {
                    $val = $this->extraerBreakdown($row, $cfg['breakdown']) ?: 'Desconocido';
                    $this->guardar($cuenta, $periodo, $nivel, $val, $val, $this->mapMetrics($row['metrics'] ?? []));
                }
            } catch (\Throwable $e) {
                Log::error("Google demo $nivel: " . $e->getMessage());
            }
        }
    }

    protected function extraerBreakdown(array $row, string $path): ?string
    {
        $keys = explode('.', $path);
        $v = $row;
        foreach ($keys as $k) {
            $v = $v[$k] ?? null;
            if ($v === null) return null;
        }
        return (string) $v;
    }

    protected function mapMetrics(array $m): array
    {
        return [
            'inversion' => isset($m['cost_micros']) ? (int) round(((int) $m['cost_micros']) / 1_000_000) : 0,
            'ventas' => isset($m['conversions_value']) ? (int) round((float) $m['conversions_value']) : 0,
            'compras' => isset($m['conversions']) ? (int) round((float) $m['conversions']) : 0,
            'alcance' => 0, // Google Ads no expone reach directamente
            'impresiones' => (int) ($m['impressions'] ?? 0),
            'clicks' => (int) ($m['clicks'] ?? 0),
        ];
    }

    /** Datos DEMO deterministas (igual número para misma cuenta+periodo). */
    protected function syncDemo(GoogleAdAccount $cuenta, string $periodo): array
    {
        $seed = crc32($cuenta->customer_id . $periodo);
        $rand = function ($min, $max) use (&$seed) {
            $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
            return $min + ($seed % max(1, ($max - $min)));
        };

        $inversion = $rand(600000, 1800000);
        $roas = (2.5 + ($seed % 35) / 10);
        $ventas = (int) round($inversion * $roas);
        $compras = $rand(80, 380);
        $impresiones = $rand(180000, 600000);
        $clicks = $rand(4000, 16000);

        $resumen = [
            'inversion' => $inversion,
            'ventas' => $ventas,
            'compras' => $compras,
            'alcance' => 0,
            'impresiones' => $impresiones,
            'clicks' => $clicks,
        ];
        $this->guardar($cuenta, $periodo, 'cuenta', null, $cuenta->nombre_cuenta, $resumen);

        // Demo campañas
        GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'campania')->delete();
        $tiposCamp = [
            'Search — Marca' => 0.35,
            'Performance Max — Catálogo' => 0.28,
            'Search — Genéricos' => 0.18,
            'Display — Remarketing' => 0.11,
            'YouTube — Awareness' => 0.05,
            'Shopping — Estándar' => 0.03,
        ];
        foreach ($tiposCamp as $nombre => $pct) {
            $cInv = (int) round($inversion * $pct);
            $cVen = (int) round($ventas * $pct * (0.7 + ($rand(0, 60) / 100)));
            $this->guardar($cuenta, $periodo, 'campania', md5($nombre), $nombre, [
                'inversion' => $cInv,
                'ventas' => $cVen,
                'compras' => (int) round($compras * $pct),
                'alcance' => 0,
                'impresiones' => (int) round($impresiones * $pct),
                'clicks' => (int) round($clicks * $pct),
            ]);
        }

        // Demo edad
        GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->whereIn('nivel', ['demo_age', 'demo_gender', 'demo_region'])->delete();
        $edades = ['18-24' => 0.12, '25-34' => 0.32, '35-44' => 0.28, '45-54' => 0.16, '55-64' => 0.08, '65+' => 0.04];
        foreach ($edades as $rango => $pct) {
            $this->guardar($cuenta, $periodo, 'demo_age', $rango, $rango, [
                'inversion' => (int) round($inversion * $pct),
                'ventas' => (int) round($ventas * $pct),
                'compras' => (int) round($compras * $pct),
                'alcance' => 0,
                'impresiones' => (int) round($impresiones * $pct),
                'clicks' => (int) round($clicks * $pct),
            ]);
        }
        // Demo género
        $generos = ['male' => 0.62, 'female' => 0.34, 'unknown' => 0.04];
        foreach ($generos as $g => $pct) {
            $this->guardar($cuenta, $periodo, 'demo_gender', $g, $g, [
                'inversion' => (int) round($inversion * $pct),
                'ventas' => (int) round($ventas * $pct),
                'compras' => (int) round($compras * $pct),
                'alcance' => 0,
                'impresiones' => (int) round($impresiones * $pct),
                'clicks' => (int) round($clicks * $pct),
            ]);
        }
        // Demo región
        $regiones = ['Santiago Metropolitan Region' => 0.42, 'Bío Bío Region' => 0.11, 'Araucanía Region' => 0.08, 'Valparaíso Region' => 0.07, 'Maule Region' => 0.06, 'Antofagasta Region' => 0.05, 'Los Lagos Region' => 0.05, 'O\'Higgins Region' => 0.04, 'Coquimbo Region' => 0.03, 'Los Ríos Region' => 0.03];
        $faltante = 1 - array_sum($regiones);
        $regiones['Otras'] = $faltante;
        foreach ($regiones as $r => $pct) {
            $this->guardar($cuenta, $periodo, 'demo_region', $r, $r, [
                'inversion' => (int) round($inversion * $pct),
                'ventas' => 0,
                'compras' => 0,
                'alcance' => 0,
                'impresiones' => (int) round($impresiones * $pct),
                'clicks' => (int) round($clicks * $pct),
            ]);
        }

        // === DIARIO demo ===
        $this->syncDemoDiarioGoogle($cuenta, $periodo, $resumen, crc32($cuenta->customer_id . $periodo));

        // === Top anuncios demo ===
        $this->syncDemoTopAnunciosGoogle($cuenta, $periodo, $resumen, crc32($cuenta->customer_id . $periodo));

        $cuenta->update(['ultima_sync_at' => now()]);
        return $resumen;
    }

    /** Genera serie diaria demo para Google Ads. */
    protected function syncDemoDiarioGoogle(GoogleAdAccount $cuenta, string $periodo, array $totales, int $seed): void
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) return;
        try {
            GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'dia')->delete();
            [$desde, $hasta] = $this->rangoMes($periodo);
            $diaInicio = (int) date('d', strtotime($desde));
            $diaFin = (int) date('d', strtotime($hasta));
            $pesos = []; $sumaPesos = 0;
            for ($d = $diaInicio; $d <= $diaFin; $d++) {
                $fecha = sprintf('%s-%02d', substr($desde, 0, 7), $d);
                $dow = (int) date('w', strtotime($fecha));
                // Google: día de semana laboral pesa más (al revés que Meta)
                $variacion = 0.85 + ((($seed + $d) % 30) / 100);
                $peso = $variacion * ($dow === 0 || $dow === 6 ? 0.85 : 1.15);
                $pesos[$d] = $peso;
                $sumaPesos += $peso;
            }
            foreach ($pesos as $d => $peso) {
                $factor = $peso / $sumaPesos;
                $fecha = sprintf('%s-%02d', substr($desde, 0, 7), $d);
                $this->guardar($cuenta, $periodo, 'dia', $fecha, $fecha, [
                    'inversion' => (int) round($totales['inversion'] * $factor),
                    'ventas' => (int) round($totales['ventas'] * $factor),
                    'compras' => (int) round($totales['compras'] * $factor),
                    'alcance' => 0,
                    'impresiones' => (int) round($totales['impresiones'] * $factor),
                    'clicks' => (int) round($totales['clicks'] * $factor),
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Google syncDemoDiario: ' . $e->getMessage());
        }
    }

    /** Top anuncios demo (Search ads con titulares + descripción). */
    protected function syncDemoTopAnunciosGoogle(GoogleAdAccount $cuenta, string $periodo, array $totales, int $seed): void
    {
        try {
            GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'top_ad')->delete();
            $anuncios = [
                ['Botas Militares ✓ Resistentes al agua', 'Envío gratis · Pago en cuotas · Stock listo. Botas tácticas para profesionales.', 0.22],
                ['Bototos Tácticos | Promo Mayo', 'Hasta 40% off en líneas seleccionadas. Llega mañana a tu casa.', 0.18],
                ['Botas Trekking de Mujer', 'Diseño premium · Suela antideslizante · Cordones reforzados.', 0.15],
                ['Catálogo Botas Militares', 'Más de 60 modelos · Talla 36 a 46 · Garantía 6 meses.', 0.13],
                ['¿Buscas resistencia y comodidad?', 'Botas profesionales para trabajo pesado. Despacho en 24-48h.', 0.10],
                ['Outlet de Botas Hasta -50%', 'Modelos descontinuados con stock limitado. Compra hoy.', 0.07],
            ];
            $i = 0;
            foreach ($anuncios as $a) {
                $i++;
                $factor = $a[2];
                GoogleAdInsight::create([
                    'google_ad_account_id' => $cuenta->id,
                    'periodo' => $periodo,
                    'nivel' => 'top_ad',
                    'objeto_id' => 'demo_ad_' . $i,
                    'objeto_nombre' => $a[0],
                    'inversion' => (int) round($totales['inversion'] * $factor),
                    'ventas' => (int) round($totales['ventas'] * $factor * (0.8 + ($seed + $i) % 50 / 100)),
                    'compras' => (int) round($totales['compras'] * $factor),
                    'alcance' => 0,
                    'impresiones' => (int) round($totales['impresiones'] * $factor),
                    'clicks' => (int) round($totales['clicks'] * $factor),
                    'extra' => ['descripcion' => $a[1], 'tipo' => 'search', 'thumbnail_url' => null],
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Google syncDemoTopAnuncios: ' . $e->getMessage());
        }
    }

    protected function guardar(GoogleAdAccount $cuenta, string $periodo, string $nivel, ?string $objetoId, ?string $objetoNombre, array $m, array $extra = []): void
    {
        GoogleAdInsight::updateOrCreate(
            [
                'google_ad_account_id' => $cuenta->id,
                'periodo' => $periodo,
                'nivel' => $nivel,
                'objeto_id' => $objetoId,
            ],
            array_merge($m, ['objeto_nombre' => $objetoNombre, 'extra' => $extra ?: null])
        );
    }

    protected function rangoMes(string $periodo): array
    {
        [$y, $mo] = explode('-', $periodo);
        $desde = sprintf('%04d-%02d-01', $y, $mo);
        $hasta = date('Y-m-t', strtotime($desde));
        return [$desde, $hasta];
    }
}
