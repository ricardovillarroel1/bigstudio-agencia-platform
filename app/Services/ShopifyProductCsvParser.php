<?php

namespace App\Services;

class ShopifyProductCsvParser
{
    private array $productos = [];
    private array $warnings = [];
    private array $errores = [];

    /**
     * Parsea un CSV de productos de Shopify y devuelve la estructura validada.
     */
    public function parse(string $rutaCsv): array
    {
        $this->productos = [];
        $this->warnings = [];
        $this->errores = [];

        if (!is_file($rutaCsv)) {
            $this->errores[] = ["tipo" => "archivo_no_encontrado", "mensaje" => "El archivo CSV no existe."];
            return $this->resultado();
        }

        // Auto-detect encoding (Excel suele guardar como UTF-8 BOM o Latin-1)
        $contenido = file_get_contents($rutaCsv);
        $encoding = mb_detect_encoding($contenido, ["UTF-8", "ISO-8859-1", "Windows-1252"], true);
        if ($encoding && $encoding !== "UTF-8") {
            $contenido = mb_convert_encoding($contenido, "UTF-8", $encoding);
        }
        // Remover BOM si existe
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        $tmpFile = tempnam(sys_get_temp_dir(), "csv_clean_");
        file_put_contents($tmpFile, $contenido);

        $handle = fopen($tmpFile, "r");
        if (!$handle) {
            $this->errores[] = ["tipo" => "no_lectura", "mensaje" => "No se pudo abrir el CSV."];
            @unlink($tmpFile);
            return $this->resultado();
        }

        // Detectar separador (Shopify usa "," pero algunos países exportan ";")
        $primeraLinea = fgets($handle);
        $sep = (substr_count($primeraLinea, ";") > substr_count($primeraLinea, ",")) ? ";" : ",";
        rewind($handle);

        $headers = fgetcsv($handle, 0, $sep, "\"", "\\");
        if (!$headers) {
            $this->errores[] = ["tipo" => "headers_faltantes", "mensaje" => "No se detectaron encabezados en el CSV."];
            fclose($handle);
            @unlink($tmpFile);
            return $this->resultado();
        }

        $headers = array_map(fn($h) => trim((string)$h), $headers);

        // Validar que el CSV tiene la estructura mínima de Shopify
        $minimosShopify = ["Title", "URL handle", "SKU", "Price"];
        $faltantes = array_diff($minimosShopify, $headers);
        if (count($faltantes) > 0) {
            $this->errores[] = [
                "tipo" => "csv_no_shopify",
                "mensaje" => "El CSV no parece ser una plantilla de Shopify. Faltan columnas: " . implode(", ", $faltantes),
                "sugerencia" => "Descargá la plantilla oficial desde el wizard y llenala respetando los encabezados.",
            ];
            fclose($handle);
            @unlink($tmpFile);
            return $this->resultado();
        }

        // Indexar headers para acceso rapido
        $colIdx = array_flip($headers);

        // Acumular productos agrupados por URL handle
        $porHandle = [];
        $linea = 1; // ya leímos los headers

        while (($fila = fgetcsv($handle, 0, $sep, "\"", "\\")) !== false) {
            $linea++;

            // Saltear filas completamente vacías
            if (count(array_filter($fila, fn($v) => trim((string)$v) !== "")) === 0) {
                continue;
            }

            $handle_val = $this->v($fila, $colIdx, "URL handle");
            if (empty($handle_val)) {
                $this->warnings[] = [
                    "linea" => $linea,
                    "tipo" => "handle_vacio",
                    "mensaje" => "Fila sin URL handle, se omite.",
                ];
                continue;
            }

            if (!isset($porHandle[$handle_val])) {
                $porHandle[$handle_val] = [
                    "handle"            => $handle_val,
                    "titulo"            => $this->v($fila, $colIdx, "Title"),
                    "descripcion"       => $this->v($fila, $colIdx, "Description"),
                    "vendor"            => $this->v($fila, $colIdx, "Vendor"),
                    "categoria"         => $this->v($fila, $colIdx, "Product category"),
                    "tipo"              => $this->v($fila, $colIdx, "Type"),
                    "tags"              => $this->v($fila, $colIdx, "Tags"),
                    "publicado"         => $this->b($fila, $colIdx, "Published on online store"),
                    "estado"            => $this->v($fila, $colIdx, "Status"),
                    "seo_title"         => $this->v($fila, $colIdx, "SEO title"),
                    "seo_description"   => $this->v($fila, $colIdx, "SEO description"),
                    "imagen_principal"  => $this->v($fila, $colIdx, "Product image URL"),
                    "imagen_alt"        => $this->v($fila, $colIdx, "Image alt text"),
                    "es_gift_card"      => $this->b($fila, $colIdx, "Gift card"),
                    "variantes"         => [],
                ];
            }

            // Esta linea siempre representa una variante (incluso si solo hay 1 producto sin variantes)
            $variante = [
                "linea_csv"     => $linea,
                "sku"           => $this->v($fila, $colIdx, "SKU"),
                "barcode"       => $this->v($fila, $colIdx, "Barcode"),
                "opcion1_name"  => $this->v($fila, $colIdx, "Option1 name"),
                "opcion1_value" => $this->v($fila, $colIdx, "Option1 value"),
                "opcion2_name"  => $this->v($fila, $colIdx, "Option2 name"),
                "opcion2_value" => $this->v($fila, $colIdx, "Option2 value"),
                "opcion3_name"  => $this->v($fila, $colIdx, "Option3 name"),
                "opcion3_value" => $this->v($fila, $colIdx, "Option3 value"),
                "precio"        => $this->n($fila, $colIdx, "Price"),
                "compare_at"    => $this->n($fila, $colIdx, "Compare-at price"),
                "costo"         => $this->n($fila, $colIdx, "Cost per item"),
                "stock"         => $this->i($fila, $colIdx, "Inventory quantity"),
                "peso_g"        => $this->n($fila, $colIdx, "Weight value (grams)"),
                "requiere_envio" => $this->b($fila, $colIdx, "Requires shipping"),
                "variant_imagen" => $this->v($fila, $colIdx, "Variant image URL"),
            ];

            // Validaciones por variante
            if (empty($variante["sku"])) {
                $this->warnings[] = [
                    "linea" => $linea,
                    "tipo" => "sku_vacio",
                    "mensaje" => "Variante sin SKU en '{$porHandle[$handle_val]['titulo']}'",
                ];
            }
            if ($variante["precio"] === null) {
                $this->warnings[] = [
                    "linea" => $linea,
                    "tipo" => "precio_vacio",
                    "mensaje" => "Variante sin precio (SKU: " . ($variante["sku"] ?: "sin SKU") . ")",
                ];
            } elseif ($variante["precio"] < 0) {
                $this->errores[] = [
                    "linea" => $linea,
                    "tipo" => "precio_negativo",
                    "mensaje" => "Precio negativo no permitido (SKU: " . ($variante["sku"] ?: "sin SKU") . ")",
                ];
            }
            if ($variante["stock"] !== null && $variante["stock"] < 0) {
                $this->warnings[] = [
                    "linea" => $linea,
                    "tipo" => "stock_negativo",
                    "mensaje" => "Stock negativo: " . $variante["stock"] . " (SKU: " . ($variante["sku"] ?: "sin SKU") . ")",
                ];
            }

            $porHandle[$handle_val]["variantes"][] = $variante;
        }

        fclose($handle);
        @unlink($tmpFile);

        // Detectar SKUs duplicados a nivel global
        $skusGlobales = [];
        foreach ($porHandle as $producto) {
            foreach ($producto["variantes"] as $v) {
                if (!empty($v["sku"])) {
                    $skusGlobales[$v["sku"]] = ($skusGlobales[$v["sku"]] ?? 0) + 1;
                }
            }
        }
        foreach ($skusGlobales as $sku => $cuenta) {
            if ($cuenta > 1) {
                $this->errores[] = [
                    "tipo" => "sku_duplicado",
                    "mensaje" => "SKU '{$sku}' aparece {$cuenta} veces en el catalogo.",
                ];
            }
        }

        $this->productos = array_values($porHandle);

        // Detectar productos sin imagen
        foreach ($this->productos as $p) {
            if (empty($p["imagen_principal"])) {
                $this->warnings[] = [
                    "tipo" => "sin_imagen",
                    "mensaje" => "Producto '{$p['titulo']}' sin imagen principal.",
                ];
            }
        }

        return $this->resultado();
    }

    private function v(array $fila, array $colIdx, string $columna): ?string
    {
        if (!isset($colIdx[$columna])) return null;
        $valor = $fila[$colIdx[$columna]] ?? null;
        $valor = trim((string)$valor);
        return $valor === "" ? null : $valor;
    }

    private function n(array $fila, array $colIdx, string $columna): ?float
    {
        $v = $this->v($fila, $colIdx, $columna);
        if ($v === null) return null;
        // Normalizar coma -> punto
        $v = str_replace(",", ".", $v);
        if (!is_numeric($v)) return null;
        return (float)$v;
    }

    private function i(array $fila, array $colIdx, string $columna): ?int
    {
        $v = $this->v($fila, $colIdx, $columna);
        if ($v === null) return null;
        if (!is_numeric($v)) return null;
        return (int)$v;
    }

    private function b(array $fila, array $colIdx, string $columna): ?bool
    {
        $v = $this->v($fila, $colIdx, $columna);
        if ($v === null) return null;
        return in_array(strtolower($v), ["true", "1", "yes", "si", "sí", "active"]);
    }

    private function resultado(): array
    {
        $totalVariantes = 0;
        foreach ($this->productos as $p) {
            $totalVariantes += count($p["variantes"]);
        }

        return [
            "productos" => $this->productos,
            "total_productos" => count($this->productos),
            "total_variantes" => $totalVariantes,
            "warnings" => $this->warnings,
            "errores" => $this->errores,
        ];
    }
}
