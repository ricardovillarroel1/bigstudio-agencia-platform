<?php
$tables = ['cuentas_bancarias', 'cuentas_banco', 'movimientos_bancarios', 'movimientos_banco', 'importaciones_cartola', 'facturas_servicio', 'iva_mensual', 'cierres_iva', 'presupuestos'];
foreach ($tables as $t) {
    try {
        $cols = array_column(\DB::select('DESCRIBE ' . $t), 'Field');
        echo $t . ': ' . implode(',', $cols) . PHP_EOL;
    } catch (\Exception $e) {
        echo $t . ': TABLE NOT FOUND' . PHP_EOL;
    }
}
