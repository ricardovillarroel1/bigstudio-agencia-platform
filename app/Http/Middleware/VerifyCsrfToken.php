<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'integracion/webhook-receiver',
        // Flow (cobros de planes Big Studio)
        'flow/return',
        'flow/confirmation',
        // Flow Agencia (cobros generales de la agencia)
        'agencia-flow/return',
        'agencia-flow/confirmation',
        // Flow Agencia - Cotizaciones
        'agencia-cotizaciones-flow/return',
        'agencia-cotizaciones-flow/confirmation',
        // Shopify GDPR Webhooks
        'webhooks/customers/data_request',
        'webhooks/customers/redact',
        'webhooks/shop/redact',
        // Shopify App lifecycle webhook
        'webhooks/app/uninstalled',
        // Onboarding publico: ya autenticado por token unico de 40 chars en URL
        'o/*/w/*/autoguardar',
        'o/*/u/*/*',
        'o/*/a/*',
    ];
}
