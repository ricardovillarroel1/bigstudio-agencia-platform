<?php
// Check notas_credito table
$ncs = \App\Models\NotaCredito::all();
echo "Total NCs: " . $ncs->count() . PHP_EOL;
echo "NCs emitidas: " . \App\Models\NotaCredito::where('status', 'emitida')->count() . PHP_EOL;
foreach ($ncs as $nc) {
    echo "NC #{$nc->id} folio:{$nc->folio} status:{$nc->status} user:{$nc->user_id} monto:{$nc->monto_total} order:{$nc->shopify_order_id}" . PHP_EOL;
}

// Check boletas with tipodoc 61 (NCs stored in boletas table)
$ncBoletas = \App\Models\Boleta::where('tipodoc', 61)->get();
echo PHP_EOL . "NCs in boletas table: " . $ncBoletas->count() . PHP_EOL;
foreach ($ncBoletas as $b) {
    echo "Boleta NC #{$b->id} folio:{$b->folio} tipodoc:{$b->tipodoc} user:{$b->user_id} monto:{$b->monto_total}" . PHP_EOL;
}

// Check facturas with tipo_documento 61
$ncFacturas = \App\Models\FacturaEmitida::where('tipo_documento', 61)->get();
echo PHP_EOL . "NCs in facturas_emitidas table: " . $ncFacturas->count() . PHP_EOL;
foreach ($ncFacturas as $f) {
    echo "Factura NC #{$f->id} folio:{$f->folio} tipo:{$f->tipo_documento} user:{$f->user_id} monto:{$f->monto_total}" . PHP_EOL;
}
