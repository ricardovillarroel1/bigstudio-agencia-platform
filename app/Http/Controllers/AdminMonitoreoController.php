<?php

namespace App\Http\Controllers;

use App\Services\MonitoreoBoletasService;
use App\Models\IntegracionConfig;
use Illuminate\Http\Request;

class AdminMonitoreoController extends Controller
{
    /**
     * Panel "Pedidos sin boleta": lista los pedidos pagados en Shopify (de todos los clientes
     * activos) que no tienen documento emitido.
     */
    public function pedidosSinBoleta(MonitoreoBoletasService $svc)
    {
        $reporte = $svc->detectarHuerfanos();
        $total = array_sum(array_map(fn($r) => count($r['huerfanos']), $reporte));
        return view('admin.pedidos-sin-boleta.index', compact('reporte', 'total'));
    }

    /**
     * Emite el documento de un pedido puntual desde el panel (botón "Emitir boleta").
     */
    public function emitirPedidoSinBoleta(Request $request, MonitoreoBoletasService $svc)
    {
        $orderId = (string) $request->input('order_id');
        $userId = (int) $request->input('user_id');

        $config = IntegracionConfig::where('user_id', $userId)->where('activo', 1)->first();
        if (!$config || !$orderId) {
            return back()->with('error', 'Datos insuficientes para emitir (cliente o pedido no encontrado).');
        }

        $r = $svc->emitirPedido($orderId, $config);
        return back()->with($r['ok'] ? 'success' : 'error', $r['msg']);
    }
}
