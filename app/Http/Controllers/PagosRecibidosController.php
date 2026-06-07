<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacturaServicio;

/**
 * Vista unificada de TODOS los pagos recibidos por la plataforma.
 *
 * Reemplaza a /admin/transferencias (que estaba vacia porque la tabla
 * pago_transferencias nunca se llenaba: los admins marcan facturas pagadas
 * directamente sin pasar por ese flujo).
 *
 * Fuente de verdad: facturas_servicio donde estado='pagada'.
 * Clasifica el origen:
 *   - flow_token != null               -> "Flow"
 *   - concepto LIKE %transfer%         -> "Transferencia"
 *   - resto                            -> "Manual" (admin marco pagada)
 */
class PagosRecibidosController extends Controller
{
    public function index(Request $request)
    {
        $filtros = [
            'origen' => $request->query('origen', ''),
            'desde'  => $request->query('desde', ''),
            'hasta'  => $request->query('hasta', ''),
            'q'      => trim((string) $request->query('q', '')),
        ];
        $hayFiltros = $filtros['origen'] !== '' || $filtros['desde'] !== '' || $filtros['hasta'] !== '' || $filtros['q'] !== '';

        $query = FacturaServicio::with('user:id,name,email')
            ->where('estado', 'pagada');

        if ($filtros['desde'] !== '') $query->whereDate('pagada_at', '>=', $filtros['desde']);
        if ($filtros['hasta'] !== '') $query->whereDate('pagada_at', '<=', $filtros['hasta']);
        if ($filtros['q'] !== '') {
            $q = $filtros['q'];
            $query->where(function ($w) use ($q) {
                $w->where('numero_factura', 'like', "%{$q}%")
                  ->orWhere('folio', 'like', "%{$q}%")
                  ->orWhereHas('user', function ($u) use ($q) {
                      $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                  });
            });
        }

        // Filtro de origen requiere logica server-side post-load porque
        // se calcula sobre 2 columnas + el concepto. Lo hago en SQL con CASE.
        if ($filtros['origen'] === 'flow') {
            $query->whereNotNull('flow_token');
        } elseif ($filtros['origen'] === 'transferencia') {
            $query->whereNull('flow_token')->where('concepto', 'like', '%transfer%');
        } elseif ($filtros['origen'] === 'manual') {
            $query->whereNull('flow_token')->where('concepto', 'not like', '%transfer%');
        }

        $pagos = $query->orderByDesc('pagada_at')->orderByDesc('id')->paginate(30)->withQueryString();

        // Conteos globales (no filtrados) para las tarjetas KPI superiores
        $stats = [
            'total'        => FacturaServicio::where('estado', 'pagada')->count(),
            // Total con IVA: usa `monto` (que ya incluye neto+IVA+extras).
            // Fallback a (monto_neto+monto_iva+monto_extra_clp) si algun registro viejo tiene monto=0.
            'monto_total'  => (int) FacturaServicio::where('estado', 'pagada')
                                ->sum(\DB::raw('CASE WHEN COALESCE(monto,0) > 0 THEN monto ELSE COALESCE(monto_neto,0) + COALESCE(monto_iva,0) + COALESCE(monto_extra_clp,0) END')),
            'flow'         => FacturaServicio::where('estado', 'pagada')->whereNotNull('flow_token')->count(),
            'transferencia'=> FacturaServicio::where('estado', 'pagada')->whereNull('flow_token')->where('concepto', 'like', '%transfer%')->count(),
            'manual'       => FacturaServicio::where('estado', 'pagada')->whereNull('flow_token')->where('concepto', 'not like', '%transfer%')->count(),
        ];

        return view('admin.pagos-recibidos.index', compact('pagos', 'stats', 'filtros', 'hayFiltros'));
    }
}
