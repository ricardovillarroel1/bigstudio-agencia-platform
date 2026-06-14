<?php

return [
    'api_key' => env('FLOW_API_KEY'),
    'secret_key' => env('FLOW_SECRET_KEY'),
    'environment' => env('FLOW_ENVIRONMENT', 'sandbox'),
    'api_urls' => [
        'sandbox' => env('FLOW_API_URL_SANDBOX', 'https://sandbox.flow.cl/api'),
        'production' => env('FLOW_API_URL_PRODUCTION', 'https://www.flow.cl/api'),
    ],
    'api_url' => env('FLOW_ENVIRONMENT') === 'production'
        ? env('FLOW_API_URL_PRODUCTION', 'https://www.flow.cl/api')
        : env('FLOW_API_URL_SANDBOX', 'https://sandbox.flow.cl/api'),

    // Recargo % que se suma al monto cuando el cliente paga vía Flow, para que la
    // comisión de la pasarela no se descuente del valor del servicio.
    // DESACTIVADO (0): Flow cobra el monto exacto; facturas y correos quedan sin recargo.
    // Para ACTIVAR: poner FLOW_RECARGO_PCT=3.19 en el .env y correr `php artisan config:clear`.
    // Con el % activo todo es automático: monto Flow con recargo, ítem "Recargo pago
    // electrónico" en las facturas post-pago, y nota informativa en ambos correos.
    'recargo_pct' => (float) env('FLOW_RECARGO_PCT', 0),
];
