<?php

namespace App\Services;

use App\Models\AgenciaOnboardingProducto;
use App\Models\AgenciaOnboardingProyecto;
use Illuminate\Support\Facades\Http;

class ShopifyProductPusher
{
    /**
     * Crea los productos del proyecto en una tienda Shopify via Admin API REST.
     *
     * @return array{creados:int, errores:array, total:int}
     */
    public function push(AgenciaOnboardingProyecto $proyecto, string $shopDomain, string $accessToken, string $apiVersion = "2024-01"): array
    {
        $shopDomain = trim($shopDomain);
        // Normalizar dominio: aceptar "tienda" o "tienda.myshopify.com" o URL completa
        $shopDomain = preg_replace("#^https?://#", "", $shopDomain);
        $shopDomain = rtrim($shopDomain, "/");
        if (!str_contains($shopDomain, ".myshopify.com")) {
            $shopDomain = $shopDomain . ".myshopify.com";
        }

        $base = "https://{$shopDomain}/admin/api/{$apiVersion}";

        $productos = AgenciaOnboardingProducto::with("imagen")
            ->where("proyecto_id", $proyecto->id)
            ->orderBy("orden")->orderBy("id")
            ->get();

        $creados = 0;
        $errores = [];
        $base_url = config("app.onboarding_url", "https://onboarding.bigstudio.cl");

        foreach ($productos as $p) {
            $payload = $this->mapProducto($p, $proyecto, $base_url);
            try {
                $resp = Http::withHeaders([
                    "X-Shopify-Access-Token" => $accessToken,
                    "Content-Type" => "application/json",
                ])->timeout(30)->post("{$base}/products.json", ["product" => $payload]);

                if ($resp->successful()) {
                    $creados++;
                } else {
                    $errores[] = [
                        "producto" => $p->titulo,
                        "status" => $resp->status(),
                        "detalle" => substr($resp->body(), 0, 300),
                    ];
                }
            } catch (\Throwable $e) {
                $errores[] = ["producto" => $p->titulo, "status" => 0, "detalle" => $e->getMessage()];
            }
        }

        return ["creados" => $creados, "errores" => $errores, "total" => $productos->count()];
    }

    /**
     * Valida credenciales haciendo un GET liviano a shop.json.
     */
    public function verificar(string $shopDomain, string $accessToken, string $apiVersion = "2024-01"): array
    {
        $shopDomain = preg_replace("#^https?://#", "", trim($shopDomain));
        $shopDomain = rtrim($shopDomain, "/");
        if (!str_contains($shopDomain, ".myshopify.com")) {
            $shopDomain = $shopDomain . ".myshopify.com";
        }
        try {
            $resp = Http::withHeaders(["X-Shopify-Access-Token" => $accessToken])
                ->timeout(15)->get("https://{$shopDomain}/admin/api/{$apiVersion}/shop.json");
            if ($resp->successful()) {
                return ["ok" => true, "tienda" => $resp->json("shop.name") ?? $shopDomain];
            }
            return ["ok" => false, "msg" => "Credenciales rechazadas (HTTP " . $resp->status() . ")"];
        } catch (\Throwable $e) {
            return ["ok" => false, "msg" => $e->getMessage()];
        }
    }

    private function mapProducto(AgenciaOnboardingProducto $p, AgenciaOnboardingProyecto $proyecto, string $base_url): array
    {
        $payload = [
            "title" => $p->titulo,
            "body_html" => $p->descripcion ?? "",
            "vendor" => $p->vendor ?? "",
            "product_type" => $p->tipo ?? "",
            "tags" => $p->tags ?? "",
            "status" => in_array($p->estado, ["active", "draft", "archived"]) ? $p->estado : "draft",
        ];

        // Opciones
        $options = [];
        if ($p->opcion1_nombre) $options[] = ["name" => $p->opcion1_nombre];
        if ($p->opcion2_nombre) $options[] = ["name" => $p->opcion2_nombre];
        if ($p->opcion3_nombre) $options[] = ["name" => $p->opcion3_nombre];
        if ($options) $payload["options"] = $options;

        // Variantes
        $variants = [];
        foreach (($p->variantes ?? []) as $v) {
            $variant = [
                "price" => isset($v["precio"]) ? (string)$v["precio"] : "0",
                "sku" => $v["sku"] ?? "",
                "inventory_management" => "shopify",
                "inventory_quantity" => (int)($v["stock"] ?? 0),
            ];
            if (!empty($v["opcion1_value"])) $variant["option1"] = $v["opcion1_value"];
            if (!empty($v["opcion2_value"])) $variant["option2"] = $v["opcion2_value"];
            if (!empty($v["opcion3_value"])) $variant["option3"] = $v["opcion3_value"];
            if (isset($v["compare_at"]) && $v["compare_at"]) $variant["compare_at_price"] = (string)$v["compare_at"];
            if (isset($v["peso_g"]) && $v["peso_g"]) { $variant["grams"] = (int)$v["peso_g"]; $variant["weight_unit"] = "g"; }
            if (!empty($v["barcode"])) $variant["barcode"] = $v["barcode"];
            $variants[] = $variant;
        }
        if (empty($variants)) {
            $variants[] = ["price" => "0", "inventory_management" => "shopify", "inventory_quantity" => 0];
        }
        $payload["variants"] = $variants;

        // Imagenes (Shopify las descarga desde la URL publica)
        $images = [];
        foreach ($p->imagenesIds() as $imgId) {
            $images[] = ["src" => $base_url . route("onboarding.archivo.descargar", ["token" => $proyecto->token, "archivo" => $imgId], false)];
        }
        if ($images) $payload["images"] = $images;

        return $payload;
    }
}
