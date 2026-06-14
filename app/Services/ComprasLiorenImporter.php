<?php

namespace App\Services;

use App\Models\IntegracionConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Importa los DTE recibidos en Lioren (facturas de compra de proveedores) a la tabla
 * facturas_compra del módulo de finanzas. Idempotente: deduplica por lioren_id y por
 * (proveedor_rut + numero_factura), así que se puede correr cuantas veces se quiera.
 */
class ComprasLiorenImporter
{
    /** Tipos de documento que se importan como factura de compra (con/sin IVA). */
    private const TIPOS = ['33', '34', '56']; // 33 factura afecta, 34 exenta, 56 nota débito

    public function importar(array $params = []): array
    {
        $userId = (int) config('finanzas.lioren_config_user_id', env('FINANZAS_LIOREN_CONFIG_USER_ID', 4));
        $config = IntegracionConfig::where('user_id', $userId)->first();
        if (!$config || empty($config->lioren_api_key)) {
            return ['ok' => false, 'msg' => 'No hay una cuenta de Lioren configurada para importar compras.', 'nuevas' => 0, 'omitidas' => 0, 'total' => 0];
        }

        $resp = app(LiorenService::class)->documentosRecibidos($config->lioren_api_key, $params);
        if (!$resp['ok']) {
            return ['ok' => false, 'msg' => 'Error consultando Lioren: ' . mb_substr((string) $resp['error'], 0, 160), 'nuevas' => 0, 'omitidas' => 0, 'total' => 0];
        }

        $nuevas = 0;
        $omitidas = 0;
        $total = count($resp['documentos']);

        foreach ($resp['documentos'] as $d) {
            $tipodoc = (string) ($d['tipodoc'] ?? '33');
            if (!in_array($tipodoc, self::TIPOS, true)) {
                continue; // se ignoran boletas/NC recibidas
            }

            $liorenId = $d['id'] ?? null;
            $rut = $this->formatRut($d['rut'] ?? '');
            $folio = (string) ($d['folio'] ?? '');

            $existe = DB::table('facturas_compra')->where(function ($q) use ($liorenId, $rut, $folio) {
                if ($liorenId) {
                    $q->where('lioren_id', $liorenId);
                }
                $q->orWhere(function ($q2) use ($rut, $folio) {
                    $q2->where('proveedor_rut', $rut)->where('numero_factura', $folio);
                });
            })->exists();
            if ($existe) {
                $omitidas++;
                continue;
            }

            $totalDoc = (int) round((float) ($d['total'] ?? 0));
            if ($tipodoc === '34') { // exenta: sin IVA
                $neto = $totalDoc;
                $iva = 0;
            } else {
                $neto = (int) round($totalDoc / 1.19);
                $iva = $totalDoc - $neto;
            }

            $fechaEmision = isset($d['fechaemision']) ? Carbon::parse($d['fechaemision'])->toDateString() : now()->toDateString();

            DB::table('facturas_compra')->insert([
                'proveedor_nombre' => mb_substr((string) ($d['rs'] ?? 'Proveedor'), 0, 255),
                'proveedor_rut'    => $rut,
                'numero_factura'   => mb_substr($folio, 0, 50),
                'fecha_emision'    => $fechaEmision,
                'monto_neto'       => $neto,
                'monto_iva'        => $iva,
                'monto_total'      => $totalDoc,
                // Las compras recibidas son gastos ya incurridos (cargos automáticos), NO deudas
                // pendientes: se registran como "pagada" para que cuenten como egreso/IVA crédito
                // pero NO aparezcan en Cuentas por Pagar.
                'estado'           => 'pagada',
                'pagada_at'        => $fechaEmision,
                'metodo_pago'      => 'Cargo automático',
                'origen'           => 'lioren',
                'lioren_id'        => $liorenId,
                'notas'            => 'Importada de Lioren · recepción ' . ($d['fecharecepcion'] ?? '') . ' · tipodoc ' . $tipodoc,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            $nuevas++;
        }

        Log::channel('single')->info("ComprasLioren: {$nuevas} nuevas, {$omitidas} ya existían (de {$total}).");
        return ['ok' => true, 'msg' => null, 'nuevas' => $nuevas, 'omitidas' => $omitidas, 'total' => $total];
    }

    /** "970060006" -> "97006000-6" */
    private function formatRut($rut): string
    {
        $rut = preg_replace('/[^0-9kK]/', '', (string) $rut);
        if (strlen($rut) < 2) {
            return $rut;
        }
        return substr($rut, 0, -1) . '-' . strtoupper(substr($rut, -1));
    }
}
