<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyGdprController extends Controller
{
    /**
     * Respuesta HTTP 200 para GET a los endpoints de webhook GDPR.
     * Shopify hace GET de prueba durante el crawl de revisión y un 405 cuenta como error.
     */
    public function gdprInfo()
    {
        return response(
            "Shopify GDPR webhook endpoint. This URL accepts POST requests from Shopify only.\n",
            200,
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * Verificar firma HMAC de Shopify
     */
    private function verifyWebhook(Request $request): bool
    {
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $secret = config('shopify.client_secret');
        
        if (!$hmac || !$secret) {
            return false;
        }
        
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));
        
        return hash_equals($hmac, $calculatedHmac);
    }

    /**
     * Webhook: Solicitud de datos del cliente (GDPR)
     * Shopify solicita que proporcionemos todos los datos que tenemos del cliente
     */
    public function customersDataRequest(Request $request)
    {

        // DEBUG: Log directo a PHP para verificar que el controlador se ejecuta
        error_log("WEBHOOK GDPR LLAMADO - " . date('Y-m-d H:i:s'));
        
        Log::info('?? GDPR Webhook recibido: customers/data_request', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'body' => $request->getContent()
        ]);

        // Verificar firma HMAC
        $isValid = $this->verifyWebhook($request);
        
        Log::info('?? Verificaci�n HMAC', [
            'webhook' => 'customers/data_request',
            'hmac_valido' => $isValid ? 'S�' : 'NO',
            'hmac_recibido' => $request->header('X-Shopify-Hmac-Sha256'),
            'secret_configurado' => config('shopify.client_secret') ? 'S�' : 'NO'
        ]);

        if (!$isValid) {
            Log::warning('? GDPR Webhook: Firma HMAC inv�lida', [
                'webhook' => 'customers/data_request',
                'ip' => $request->ip()
            ]);
            return response('Unauthorized', 401);
        }

        $data = $request->json()->all();
        
        Log::info('? GDPR: Solicitud de datos del cliente recibida', [
            'shop_domain' => $data['shop_domain'] ?? null,
            'customer_id' => $data['customer']['id'] ?? null,
            'customer_email' => $data['customer']['email'] ?? null,
        ]);

        // TODO: Implementar l�gica para recopilar y enviar datos del cliente
        // Por ahora solo registramos la solicitud
        
        return response('', 200);
    }

    /**
     * Webhook: Solicitud de eliminaci�n de datos del cliente (GDPR)
     * Shopify solicita que eliminemos todos los datos del cliente
     */
    public function customersRedact(Request $request)
    {
        Log::info('?? GDPR Webhook recibido: customers/redact', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'body' => $request->getContent()
        ]);

        // Verificar firma HMAC
        $isValid = $this->verifyWebhook($request);
        
        Log::info('?? Verificaci�n HMAC', [
            'webhook' => 'customers/redact',
            'hmac_valido' => $isValid ? 'S�' : 'NO',
            'hmac_recibido' => $request->header('X-Shopify-Hmac-Sha256'),
            'secret_configurado' => config('shopify.client_secret') ? 'S�' : 'NO'
        ]);

        if (!$isValid) {
            Log::warning('? GDPR Webhook: Firma HMAC inv�lida', [
                'webhook' => 'customers/redact',
                'ip' => $request->ip()
            ]);
            return response('Unauthorized', 401);
        }

        $data = $request->json()->all();
        
        Log::info('? GDPR: Solicitud de eliminaci�n de datos del cliente', [
            'shop_domain' => $data['shop_domain'] ?? null,
            'customer_id' => $data['customer']['id'] ?? null,
            'customer_email' => $data['customer']['email'] ?? null,
        ]);

        // TODO: Implementar l�gica para eliminar datos del cliente
        // - Eliminar de integracion_configs
        // - Eliminar de solicitudes
        // - Eliminar de facturas/boletas relacionadas
        // - Anonimizar datos si es necesario por regulaciones
        
        return response('', 200);
    }

    /**
     * Webhook: Aplicación desinstalada (app/uninstalled)
     * Shopify lo envía cuando el merchant desinstala la app.
     * Debemos invalidar el token y marcar la integración como inactiva.
     */
    public function appUninstalled(Request $request)
    {
        Log::info('Shopify webhook recibido: app/uninstalled', [
            'shop' => $request->header('X-Shopify-Shop-Domain'),
            'ip' => $request->ip(),
        ]);

        if (!$this->verifyWebhook($request)) {
            Log::warning('app/uninstalled: HMAC inválido', [
                'shop' => $request->header('X-Shopify-Shop-Domain'),
            ]);
            return response('Unauthorized', 401);
        }

        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        if (!$shopDomain) {
            return response('Missing shop domain', 400);
        }

        // Marcar integraciones de este shop como inactivas y limpiar el token
        $afectadas = \App\Models\IntegracionConfig::where('shop_domain', $shopDomain)
            ->orWhere('shopify_tienda', $shopDomain)
            ->get();

        foreach ($afectadas as $config) {
            $config->update([
                'activo' => false,
                'shopify_token' => null,
            ]);
        }

        // Marcar solicitudes como desinstaladas
        \App\Models\Solicitud::where('tienda_shopify', $shopDomain)
            ->update([
                'estado' => 'desinstalada',
                'access_token' => null,
                'integracion_conectada' => false,
            ]);

        Log::info('app/uninstalled procesado', [
            'shop' => $shopDomain,
            'integraciones_inactivadas' => $afectadas->count(),
        ]);

        return response('', 200);
    }

    /**
     * Webhook: Solicitud de eliminaci�n de datos de la tienda (GDPR)
     * Shopify solicita que eliminemos todos los datos de la tienda
     */
    public function shopRedact(Request $request)
    {
        Log::info('?? GDPR Webhook recibido: shop/redact', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'body' => $request->getContent()
        ]);

        // Verificar firma HMAC
        $isValid = $this->verifyWebhook($request);
        
        Log::info('?? Verificaci�n HMAC', [
            'webhook' => 'shop/redact',
            'hmac_valido' => $isValid ? 'S�' : 'NO',
            'hmac_recibido' => $request->header('X-Shopify-Hmac-Sha256'),
            'secret_configurado' => config('shopify.client_secret') ? 'S�' : 'NO'
        ]);

        if (!$isValid) {
            Log::warning('? GDPR Webhook: Firma HMAC inv�lida', [
                'webhook' => 'shop/redact',
                'ip' => $request->ip()
            ]);
            return response('Unauthorized', 401);
        }

        $data = $request->json()->all();
        
        Log::info('? GDPR: Solicitud de eliminaci�n de datos de la tienda', [
            'shop_domain' => $data['shop_domain'] ?? null,
            'shop_id' => $data['shop_id'] ?? null,
        ]);

        // TODO: Implementar l�gica para eliminar todos los datos de la tienda
        // - Eliminar integracion_configs de esta tienda
        // - Eliminar solicitudes relacionadas
        // - Eliminar webhooks
        // - Eliminar facturas/boletas
        // - Eliminar mappings de productos
        
        return response('', 200);
    }
}
