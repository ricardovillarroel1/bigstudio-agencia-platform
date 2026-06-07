<?php

namespace App\Services;

use App\Models\AgenciaOnboardingProducto;
use App\Models\AgenciaOnboardingProyecto;
use Illuminate\Support\Str;

class ShopifyProductCsvExporter
{
    /**
     * Headers oficiales de Shopify (orden exacto).
     */
    public const HEADERS = [
        "Title",
        "URL handle",
        "Description",
        "Vendor",
        "Product category",
        "Type",
        "Tags",
        "Published on online store",
        "Status",
        "SKU",
        "Barcode",
        "Option1 name",
        "Option1 value",
        "Option1 Linked To",
        "Option2 name",
        "Option2 value",
        "Option2 Linked To",
        "Option3 name",
        "Option3 value",
        "Option3 Linked To",
        "Price",
        "Compare-at price",
        "Cost per item",
        "Charge tax",
        "Tax code",
        "Unit price total measure",
        "Unit price total measure unit",
        "Unit price base measure",
        "Unit price base measure unit",
        "Inventory tracker",
        "Inventory quantity",
        "Continue selling when out of stock",
        "Weight value (grams)",
        "Weight unit for display",
        "Requires shipping",
        "Fulfillment service",
        "Product image URL",
        "Image position",
        "Image alt text",
        "Variant image URL",
        "Gift card",
        "SEO title",
        "SEO description",
        "Color (product.metafields.shopify.color-pattern)",
        "Google Shopping / Google product category",
        "Google Shopping / Gender",
        "Google Shopping / Age group",
        "Google Shopping / Manufacturer part number (MPN)",
        "Google Shopping / Ad group name",
        "Google Shopping / Ads labels",
        "Google Shopping / Condition",
        "Google Shopping / Custom product",
        "Google Shopping / Custom label 0",
        "Google Shopping / Custom label 1",
        "Google Shopping / Custom label 2",
        "Google Shopping / Custom label 3",
        "Google Shopping / Custom label 4",
    ];

    public function exportar(AgenciaOnboardingProyecto $proyecto, string $rutaSalida): string
    {
        $productos = AgenciaOnboardingProducto::with("imagen")
            ->where("proyecto_id", $proyecto->id)
            ->orderBy("seccion_key")
            ->orderBy("orden")
            ->orderBy("id")
            ->get();

        $fp = fopen($rutaSalida, "w");
        // BOM UTF-8 para que Excel lo abra bien
        fwrite($fp, "\xEF\xBB\xBF");

        fputcsv($fp, self::HEADERS, ",", '"', "\\");

        foreach ($productos as $producto) {
            $this->escribirProducto($fp, $producto);
        }

        fclose($fp);
        return $rutaSalida;
    }

    private function escribirProducto($fp, AgenciaOnboardingProducto $producto): void
    {
        $variantes = $producto->variantes ?? [];
        if (empty($variantes)) {
            $variantes = [["sku" => "", "precio" => 0, "stock" => 0]];
        }

        $handle = $producto->urlHandle();
        $imagenUrl = $producto->imagen_archivo_id
            ? url(route("onboarding.archivo.descargar", [
                "token" => $producto->proyecto->token,
                "archivo" => $producto->imagen_archivo_id,
            ], false))
            : "";

        // URL absoluta correcta
        if ($imagenUrl) {
            $imagenUrl = config("app.onboarding_url", "https://onboarding.bigstudio.cl") . $imagenUrl;
        }

        foreach ($variantes as $i => $variante) {
            $esFilaPrincipal = ($i === 0);

            $fila = array_fill(0, count(self::HEADERS), "");

            // Producto info solo en la primera fila
            if ($esFilaPrincipal) {
                $fila[$this->c("Title")] = $producto->titulo;
                $fila[$this->c("Description")] = $producto->descripcion ?? "";
                $fila[$this->c("Vendor")] = $producto->vendor ?? "";
                $fila[$this->c("Product category")] = $producto->categoria ?? "";
                $fila[$this->c("Type")] = $producto->tipo ?? "";
                $fila[$this->c("Tags")] = $producto->tags ?? "";
                $fila[$this->c("Published on online store")] = $producto->publicado ? "TRUE" : "FALSE";
                $fila[$this->c("Status")] = $producto->estado ?: "active";
                $fila[$this->c("Gift card")] = $producto->es_gift_card ? "TRUE" : "FALSE";
                $fila[$this->c("SEO title")] = $producto->seo_title ?? "";
                $fila[$this->c("SEO description")] = $producto->seo_description ?? "";
                $fila[$this->c("Product image URL")] = $imagenUrl;
                $fila[$this->c("Image position")] = "1";
                $fila[$this->c("Image alt text")] = $producto->imagen_alt ?? "";
            }

            // Siempre presente: handle, variant-level data
            $fila[$this->c("URL handle")] = $handle;
            $fila[$this->c("SKU")] = $variante["sku"] ?? "";
            $fila[$this->c("Barcode")] = $variante["barcode"] ?? "";

            // Opciones
            if ($producto->opcion1_nombre) {
                if ($esFilaPrincipal) {
                    $fila[$this->c("Option1 name")] = $producto->opcion1_nombre;
                } else {
                    // Continua repitiendo el nombre en otras filas tambien (Shopify lo acepta vacio en variantes posteriores)
                }
                $fila[$this->c("Option1 value")] = $variante["opcion1_value"] ?? "";
            }
            if ($producto->opcion2_nombre) {
                if ($esFilaPrincipal) {
                    $fila[$this->c("Option2 name")] = $producto->opcion2_nombre;
                }
                $fila[$this->c("Option2 value")] = $variante["opcion2_value"] ?? "";
            }
            if ($producto->opcion3_nombre) {
                if ($esFilaPrincipal) {
                    $fila[$this->c("Option3 name")] = $producto->opcion3_nombre;
                }
                $fila[$this->c("Option3 value")] = $variante["opcion3_value"] ?? "";
            }

            // Precio, costo, inventario, peso
            $fila[$this->c("Price")] = $this->fmt($variante["precio"] ?? null);
            $fila[$this->c("Compare-at price")] = $this->fmt($variante["compare_at"] ?? null);
            $fila[$this->c("Cost per item")] = $this->fmt($variante["costo"] ?? null);
            $fila[$this->c("Charge tax")] = "TRUE";
            $fila[$this->c("Inventory tracker")] = "shopify";
            $fila[$this->c("Inventory quantity")] = $variante["stock"] ?? "0";
            $fila[$this->c("Continue selling when out of stock")] = "DENY";
            $fila[$this->c("Weight value (grams)")] = $this->fmt($variante["peso_g"] ?? null);
            $fila[$this->c("Weight unit for display")] = "g";
            $fila[$this->c("Requires shipping")] = $producto->requiere_envio ? "TRUE" : "FALSE";
            $fila[$this->c("Fulfillment service")] = "manual";

            fputcsv($fp, $fila, ",", '"', "\\");
        }
    }

    private function c(string $header): int
    {
        $idx = array_search($header, self::HEADERS, true);
        return $idx === false ? 0 : $idx;
    }

    private function fmt($valor): string
    {
        if ($valor === null || $valor === "") return "";
        if (!is_numeric($valor)) return "";
        return number_format((float)$valor, 2, ".", "");
    }
}
