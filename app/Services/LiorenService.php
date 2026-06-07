<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Punto ÚNICO de comunicación con Lioren (emisión de DTE).
 *
 * Centraliza la estructura del "emisor", los endpoints, los headers y el parseo de la respuesta,
 * para que ningún flujo arme el payload a mano (esa duplicación fue la causa de varios bugs:
 * boletas guardadas en $0, notas de crédito que no se guardaban, etc.).
 *
 * Todos los métodos devuelven un arreglo normalizado:
 *   ['ok' => bool, 'folio' => int|null, 'id' => int|null, 'pdf' => string|null, 'xml' => string|null,
 *    'montoneto' => int|null, 'montoiva' => int|null, 'montototal' => int|null,
 *    'error' => string|null, 'status' => int|null, 'raw' => array]
 */
class LiorenService
{
    public const ENDPOINT_BOLETAS = 'https://www.lioren.cl/api/boletas';
    public const ENDPOINT_DTES    = 'https://www.lioren.cl/api/dtes';

    /**
     * Emite una BOLETA electrónica (tipodoc 39). Los precios de $detalles van BRUTOS (con IVA).
     */
    public function emitirBoleta(string $apiKey, array $detalles, array $receptor = [], string $observaciones = ''): array
    {
        $payload = [
            'emisor'   => ['tipodoc' => '39', 'servicio' => 3, 'observaciones' => mb_substr($observaciones, 0, 250)],
            'detalles' => $detalles,
            'expects'  => 'all',
        ];
        $receptor = array_filter($receptor, fn ($v) => $v !== null && $v !== '');
        if (!empty($receptor)) {
            $payload['receptor'] = $receptor;
        }
        return $this->post($apiKey, self::ENDPOINT_BOLETAS, $payload);
    }

    /**
     * Emite una FACTURA electrónica (tipodoc 33). Los precios de $detalles van NETOS (sin IVA).
     *
     * $opciones admite:
     *   - 'tipodoc'          => '33'|'34'... (default '33'; p.ej. 34 = factura exenta)
     *   - 'descuento_global' => ['tipo'=>'porcentaje','valor'=>10]  (descuento a nivel documento)
     */
    public function emitirFactura(string $apiKey, array $detalles, array $receptor, string $observaciones = '', array $opciones = []): array
    {
        $payload = [
            'emisor'   => ['tipodoc' => (string) ($opciones['tipodoc'] ?? '33'), 'fecha' => now()->format('Y-m-d'), 'observaciones' => mb_substr($observaciones, 0, 250)],
            'receptor' => $receptor,
            'detalles' => $detalles,
            'expects'  => 'all',
        ];
        if (!empty($opciones['descuento_global'])) {
            $payload['descuento_global'] = $opciones['descuento_global'];
        }
        return $this->post($apiKey, self::ENDPOINT_DTES, $payload);
    }

    /**
     * Emite una NOTA DE CRÉDITO (tipodoc 61) referenciando un documento.
     * $referencia: ['fecha'=>'Y-m-d', 'tipodoc'=>'39'|'33', 'folio'=>'123', 'razon'=>1|2|3, 'glosa'=>'...'].
     *   razon 1 = anula, 2 = corrige texto, 3 = corrige montos.
     */
    public function emitirNotaCredito(string $apiKey, array $detalles, array $receptor, array $referencia, string $observaciones = ''): array
    {
        $payload = [
            'emisor'      => ['tipodoc' => '61', 'fecha' => now()->format('Y-m-d'), 'observaciones' => mb_substr($observaciones, 0, 250)],
            'receptor'    => array_filter($receptor, fn ($v) => $v !== null && $v !== ''),
            'detalles'    => $detalles,
            'referencias' => [$referencia],
            'expects'     => 'all',
        ];
        return $this->post($apiKey, self::ENDPOINT_DTES, $payload);
    }

    /**
     * POST genérico a Lioren con manejo de errores y respuesta normalizada.
     */
    private function post(string $apiKey, string $endpoint, array $payload): array
    {
        try {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($endpoint, $payload);

            $data = is_array($resp->json()) ? $resp->json() : [];

            $ok = $resp->successful() && isset($data['folio']);

            if (!$ok) {
                Log::channel('single')->error('LiorenService: emisión fallida', [
                    'endpoint' => $endpoint,
                    'status'   => $resp->status(),
                    'body'     => mb_substr($resp->body(), 0, 500),
                ]);
            }

            // Devuelve el JSON crudo de Lioren + las claves de control garantizadas. Así, cualquier
            // flujo que lea campos crudos (tipodoc, montototal, rs, detalles, etc.) sigue funcionando,
            // y siempre existen 'ok', 'folio', 'id', 'pdf', 'xml', 'status', 'error', 'raw'.
            return array_merge($data, [
                'ok'     => $ok,
                'folio'  => $data['folio'] ?? null,
                'id'     => $data['id'] ?? null,
                'pdf'    => $data['pdf'] ?? null,
                'xml'    => $data['xml'] ?? null,
                'status' => $resp->status(),
                'error'  => $ok ? null : $resp->body(),
                'raw'    => $data,
            ]);
        } catch (\Throwable $e) {
            Log::channel('single')->error('LiorenService: excepción en emisión: ' . $e->getMessage());
            return ['ok' => false, 'folio' => null, 'id' => null, 'pdf' => null, 'xml' => null, 'error' => $e->getMessage(), 'status' => null, 'raw' => []];
        }
    }
}
