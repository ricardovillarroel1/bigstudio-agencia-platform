<?php

namespace App\Http\Controllers;

use App\Mail\OnboardingCompletadoMail;
use App\Models\AgenciaOnboardingArchivo;
use App\Models\AgenciaOnboardingProducto;
use App\Services\ShopifyProductCsvParser;
use Illuminate\Support\Facades\Http;
use App\Models\AgenciaOnboardingProyecto;
use App\Models\AgenciaOnboardingRespuesta;
use App\Models\AgenciaOnboardingEvento;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OnboardingPublicoController extends Controller
{
    /**
     * Landing del onboarding (bienvenida).
     */
    public function mostrar(Request $request, string $token): Response
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) return $proyecto;

        $this->registrarPrimerAcceso($proyecto);

        return response()->view("onboarding.bienvenida", [
            "proyecto" => $proyecto,
        ]);
    }

    /**
     * Wizard: muestra una sección específica.
     */
    public function wizard(Request $request, string $token, ?int $indice = null): Response
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) return $proyecto;

        $this->registrarPrimerAcceso($proyecto);

        $secciones = $proyecto->plantilla->secciones ?? [];
        $totalSecciones = count($secciones);

        if ($totalSecciones === 0) {
            abort(404, "La plantilla no tiene secciones configuradas.");
        }

        $indice = $indice ?? 0;
        $indice = max(0, min($indice, $totalSecciones - 1));
        $seccion = $secciones[$indice];

        // Respuestas ya guardadas para precarga
        $respuestas = $proyecto->respuestas()
            ->where("seccion_key", $seccion["key"])
            ->pluck("valor", "campo_key")
            ->toArray();

        return response()->view("onboarding.wizard", [
            "proyecto" => $proyecto,
            "secciones" => $secciones,
            "totalSecciones" => $totalSecciones,
            "indice" => $indice,
            "seccion" => $seccion,
            "respuestas" => $respuestas,
            "esUltima" => $indice === $totalSecciones - 1,
            "esPrimera" => $indice === 0,
        ]);
    }

    /**
     * Guarda las respuestas de una sección y avanza.
     */
    public function guardar(Request $request, string $token, int $indice)
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) return $proyecto;

        $secciones = $proyecto->plantilla->secciones ?? [];
        $totalSecciones = count($secciones);

        if ($indice < 0 || $indice >= $totalSecciones) {
            abort(404);
        }

        $seccion = $secciones[$indice];
        $campos = $seccion["campos"] ?? [];
        $seccionKey = $seccion["key"];

        DB::transaction(function () use ($proyecto, $campos, $seccionKey, $request) {
            foreach ($campos as $campo) {
                $valor = $request->input("campos." . $campo["key"]);
                if (is_array($valor)) {
                    $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
                }

                // Si el valor nuevo es vacio/null, NO sobreescribir respuesta existente.
                // Esto evita borrar datos cuando el cliente navega Siguiente/Anterior sin haber
                // tocado los campos de esta seccion (los campos vacios del form NO deben
                // sobreescribir valores previos). Para limpiar un campo explicitamente,
                // el cliente debe usar el autoguardar (blur por campo individual).
                if ($valor === null || $valor === "") {
                    continue;
                }

                AgenciaOnboardingRespuesta::updateOrCreate(
                    [
                        "proyecto_id" => $proyecto->id,
                        "seccion_key" => $seccionKey,
                        "campo_key"   => $campo["key"],
                    ],
                    ["valor" => $valor]
                );
            }
        });

        // Recalcular avance y registrar evento si la sección quedó completa
        $this->recalcularAvance($proyecto);
        if ($this->seccionEstaCompleta($proyecto, $seccion)) {
            AgenciaOnboardingEvento::registrar(
                $proyecto->id,
                "seccion_completada",
                "Sección \"" . $seccion["titulo"] . "\" completada"
            );
        }

        // Botón "Material listo" → marcamos como completado
        if ($request->input("material_listo") === "1") {
            $proyecto->update([
                "estado" => "completado",
                "fecha_completado" => now(),
                "porcentaje_avance" => 100,
            ]);
            AgenciaOnboardingEvento::registrar(
                $proyecto->id,
                "completado",
                "Cliente marcó el material como listo"
            );

            // Notificar al equipo BigStudio por email (silenciar errores para no bloquear UX)
            try {
                Mail::send(new OnboardingCompletadoMail($proyecto->fresh(["cliente", "plantilla", "respuestas", "archivos"])));
                AgenciaOnboardingEvento::registrar($proyecto->id, "notificacion_enviada", "Email a hola@bigstudio.cl enviado");
            } catch (\Throwable $e) {
                Log::warning("Onboarding mail failed: " . $e->getMessage());
                AgenciaOnboardingEvento::registrar($proyecto->id, "notificacion_fallida", $e->getMessage());
            }

            // Webhook configurable (Slack/Notion/Make/Zapier). Solo si esta configurado en .env.
            $webhookUrl = config("app.onboarding_webhook_url", env("ONBOARDING_WEBHOOK_URL"));
            if ($webhookUrl) {
                try {
                    $resp = Http::timeout(8)->post($webhookUrl, [
                        "event" => "onboarding.completado",
                        "proyecto_id" => $proyecto->id,
                        "cliente" => $proyecto->cliente?->nombre,
                        "titulo" => $proyecto->titulo,
                        "plantilla" => $proyecto->plantilla?->nombre,
                        "porcentaje_avance" => $proyecto->porcentaje_avance,
                        "fecha_completado" => $proyecto->fecha_completado?->toIso8601String(),
                        "admin_url" => url("/agencia/onboardings/" . $proyecto->id),
                        "text" => "🎉 Onboarding completado por " . ($proyecto->cliente?->nombre ?? "cliente") . " — " . $proyecto->titulo,
                    ]);
                    AgenciaOnboardingEvento::registrar(
                        $proyecto->id,
                        "webhook_enviado",
                        "Webhook enviado (HTTP " . $resp->status() . ")"
                    );
                } catch (\Throwable $e) {
                    Log::warning("Onboarding webhook failed: " . $e->getMessage());
                    AgenciaOnboardingEvento::registrar($proyecto->id, "webhook_fallido", $e->getMessage());
                }
            }

            return redirect()->route("onboarding.completado", ["token" => $proyecto->token]);
        }

        // Decidir siguiente paso
        $accion = $request->input("accion", "siguiente");
        if ($accion === "anterior" && $indice > 0) {
            return redirect()->route("onboarding.wizard", ["token" => $proyecto->token, "indice" => $indice - 1]);
        }
        if ($accion === "siguiente" && $indice < $totalSecciones - 1) {
            return redirect()->route("onboarding.wizard", ["token" => $proyecto->token, "indice" => $indice + 1]);
        }

        return redirect()->route("onboarding.wizard", ["token" => $proyecto->token, "indice" => $indice])
            ->with("success", "Cambios guardados.");
    }

    /**
     * AJAX: autosave (sin redirect).
     */
    public function autoguardar(Request $request, string $token, int $indice): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false, "msg" => "token invalido"], 410);
        }

        $secciones = $proyecto->plantilla->secciones ?? [];
        if (!isset($secciones[$indice])) {
            return response()->json(["ok" => false, "msg" => "seccion invalida"], 404);
        }
        $seccion = $secciones[$indice];

        $campoKey = $request->input("campo_key");
        $valor    = $request->input("valor");

        AgenciaOnboardingRespuesta::updateOrCreate(
            [
                "proyecto_id" => $proyecto->id,
                "seccion_key" => $seccion["key"],
                "campo_key"   => $campoKey,
            ],
            ["valor" => is_array($valor) ? json_encode($valor) : $valor]
        );

        $this->recalcularAvance($proyecto);

        return response()->json([
            "ok" => true,
            "porcentaje" => $proyecto->fresh()->porcentaje_avance,
        ]);
    }

    /**
     * Vista final tras completar.
     */
    public function completado(string $token): Response
    {
        $proyecto = AgenciaOnboardingProyecto::with(["cliente", "plantilla"])
            ->where("token", $token)
            ->firstOrFail();

        return response()->view("onboarding.completado", [
            "proyecto" => $proyecto,
        ]);
    }

    /**
     * Sube un archivo asociado a un campo de una seccion.
     */
    public function subirArchivo(Request $request, string $token, int $indice, string $campoKey)
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false, "msg" => "token invalido"], 410);
        }

        $secciones = $proyecto->plantilla->secciones ?? [];
        if (!isset($secciones[$indice])) {
            return response()->json(["ok" => false, "msg" => "seccion invalida"], 404);
        }
        $seccionKey = $secciones[$indice]["key"];

        $request->validate([
            "archivo" => "required|file|max:51200",
        ]);

        $file = $request->file("archivo");
        $directorio = "/var/www/onboarding-storage/{$proyecto->id}/{$seccionKey}";
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $extension = $file->getClientOriginalExtension();
        $nombreSeguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $file->getClientOriginalName());
        $rutaCompleta = $directorio . "/" . $nombreSeguro;

        $file->move($directorio, $nombreSeguro);

        $archivo = AgenciaOnboardingArchivo::create([
            "proyecto_id"     => $proyecto->id,
            "seccion_key"     => $seccionKey,
            "campo_key"       => $campoKey,
            "nombre_original" => $file->getClientOriginalName(),
            "ruta"            => "{$proyecto->id}/{$seccionKey}/{$nombreSeguro}",
            "mime_type"       => mime_content_type($rutaCompleta) ?: $file->getClientMimeType(),
            "tamano_bytes"    => filesize($rutaCompleta) ?: 0,
        ]);

        AgenciaOnboardingEvento::registrar(
            $proyecto->id,
            "archivo_subido",
            "Archivo \"{$archivo->nombre_original}\" en {$seccionKey}/{$campoKey}",
            ["archivo_id" => $archivo->id]
        );

        // Marcar el campo en respuestas como "tiene archivos" para que cuente en el avance
        AgenciaOnboardingRespuesta::updateOrCreate(
            [
                "proyecto_id" => $proyecto->id,
                "seccion_key" => $seccionKey,
                "campo_key"   => $campoKey,
            ],
            ["valor" => "archivos_subidos"]
        );
        $this->recalcularAvance($proyecto);

        return response()->json([
            "ok" => true,
            "archivo" => [
                "id"     => $archivo->id,
                "nombre" => $archivo->nombre_original,
                "tamano" => $archivo->tamanoLegible(),
                "url"    => route("onboarding.archivo.descargar", ["token" => $proyecto->token, "archivo" => $archivo->id]),
            ],
            "porcentaje" => $proyecto->fresh()->porcentaje_avance,
        ]);
    }

    /**
     * Elimina un archivo subido.
     */
    public function eliminarArchivo(Request $request, string $token, int $archivoId): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false], 410);
        }

        $archivo = AgenciaOnboardingArchivo::where("proyecto_id", $proyecto->id)
            ->where("id", $archivoId)
            ->first();

        if (!$archivo) {
            return response()->json(["ok" => false, "msg" => "no existe"], 404);
        }

        $rutaCompleta = "/var/www/onboarding-storage/" . $archivo->ruta;
        if (is_file($rutaCompleta)) {
            @unlink($rutaCompleta);
        }

        $seccionKey = $archivo->seccion_key;
        $campoKey = $archivo->campo_key;
        $archivo->delete();

        // Si no quedan archivos para ese campo, vaciar la respuesta marcadora
        $quedan = AgenciaOnboardingArchivo::where("proyecto_id", $proyecto->id)
            ->where("seccion_key", $seccionKey)
            ->where("campo_key", $campoKey)
            ->exists();
        if (!$quedan) {
            AgenciaOnboardingRespuesta::where("proyecto_id", $proyecto->id)
                ->where("seccion_key", $seccionKey)
                ->where("campo_key", $campoKey)
                ->update(["valor" => null]);
        }

        $this->recalcularAvance($proyecto);

        return response()->json([
            "ok" => true,
            "porcentaje" => $proyecto->fresh()->porcentaje_avance,
        ]);
    }

    /**
     * Descarga (o muestra inline) un archivo subido por el cliente.
     */
    public function descargarArchivo(string $token, int $archivoId)
    {
        $proyecto = AgenciaOnboardingProyecto::where("token", $token)->firstOrFail();
        $archivo = AgenciaOnboardingArchivo::where("proyecto_id", $proyecto->id)
            ->where("id", $archivoId)
            ->firstOrFail();

        $rutaCompleta = "/var/www/onboarding-storage/" . $archivo->ruta;
        if (!is_file($rutaCompleta)) {
            abort(404, "Archivo no encontrado en disco.");
        }

        return response()->file($rutaCompleta, [
            "Content-Type" => $archivo->mime_type ?: "application/octet-stream",
        ]);
    }

    // ============================================================
    // Helpers privados
    // ============================================================

    // ============================================================
    // PRODUCTOS - constructor visual + CRUD
    // ============================================================

    /**
     * GET - Lista de productos del campo (para hidratar la UI).
     */
    public function listarProductos(string $token, int $indice, string $campoKey): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false], 410);
        }

        $secciones = $proyecto->plantilla->secciones ?? [];
        if (!isset($secciones[$indice])) {
            return response()->json(["ok" => false, "msg" => "seccion invalida"], 404);
        }
        $seccionKey = $secciones[$indice]["key"];

        $productos = AgenciaOnboardingProducto::with("imagen")
            ->where("proyecto_id", $proyecto->id)
            ->where("seccion_key", $seccionKey)
            ->where("campo_key", $campoKey)
            ->orderBy("orden")
            ->orderBy("id")
            ->get()
            ->map(fn($p) => $this->serializarProducto($p))
            ->all();

        return response()->json(["ok" => true, "productos" => $productos]);
    }

    /**
     * POST - Crea un producto nuevo.
     */
    public function crearProducto(Request $request, string $token, int $indice, string $campoKey): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false], 410);
        }

        $secciones = $proyecto->plantilla->secciones ?? [];
        if (!isset($secciones[$indice])) {
            return response()->json(["ok" => false, "msg" => "seccion invalida"], 404);
        }
        $seccionKey = $secciones[$indice]["key"];

        $data = $this->validarProducto($request);

        $producto = AgenciaOnboardingProducto::create(array_merge($data, [
            "proyecto_id" => $proyecto->id,
            "seccion_key" => $seccionKey,
            "campo_key"   => $campoKey,
            "orden"       => AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)
                ->where("seccion_key", $seccionKey)
                ->where("campo_key", $campoKey)
                ->count(),
        ]));

        $this->marcarRespuestaProductos($proyecto, $seccionKey, $campoKey);
        $this->recalcularAvance($proyecto);

        AgenciaOnboardingEvento::registrar(
            $proyecto->id,
            "producto_creado",
            "Producto agregado: {$producto->titulo}",
            ["producto_id" => $producto->id]
        );

        return response()->json([
            "ok" => true,
            "producto" => $this->serializarProducto($producto->fresh("imagen")),
            "porcentaje" => $proyecto->fresh()->porcentaje_avance,
        ]);
    }

    /**
     * PUT - Actualiza un producto existente.
     */
    public function actualizarProducto(Request $request, string $token, int $productoId): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false], 410);
        }

        $producto = AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)->find($productoId);
        if (!$producto) {
            return response()->json(["ok" => false, "msg" => "producto no encontrado"], 404);
        }

        $data = $this->validarProducto($request);
        $producto->update($data);
        $this->recalcularAvance($proyecto);

        return response()->json([
            "ok" => true,
            "producto" => $this->serializarProducto($producto->fresh("imagen")),
            "porcentaje" => $proyecto->fresh()->porcentaje_avance,
        ]);
    }

    /**
     * DELETE - Elimina un producto.
     */
    public function eliminarProducto(string $token, int $productoId): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false], 410);
        }

        $producto = AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)->find($productoId);
        if (!$producto) {
            return response()->json(["ok" => false], 404);
        }

        $seccionKey = $producto->seccion_key;
        $campoKey = $producto->campo_key;

        // Borrar imagen asociada si solo este producto la usa
        if ($producto->imagen_archivo_id) {
            $usadaEnOtros = AgenciaOnboardingProducto::where("imagen_archivo_id", $producto->imagen_archivo_id)
                ->where("id", "!=", $producto->id)
                ->exists();
            if (!$usadaEnOtros) {
                $img = AgenciaOnboardingArchivo::find($producto->imagen_archivo_id);
                if ($img) {
                    $rutaImg = "/var/www/onboarding-storage/" . $img->ruta;
                    if (is_file($rutaImg)) @unlink($rutaImg);
                    $img->delete();
                }
            }
        }

        $producto->delete();

        // Si no quedan productos, limpiar la respuesta marcadora
        $quedan = AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)
            ->where("seccion_key", $seccionKey)
            ->where("campo_key", $campoKey)
            ->exists();
        if (!$quedan) {
            AgenciaOnboardingRespuesta::where("proyecto_id", $proyecto->id)
                ->where("seccion_key", $seccionKey)
                ->where("campo_key", $campoKey)
                ->update(["valor" => null]);
        }
        $this->recalcularAvance($proyecto);

        return response()->json(["ok" => true, "porcentaje" => $proyecto->fresh()->porcentaje_avance]);
    }

    /**
     * POST - Sube la imagen principal de un producto.
     */
    public function subirImagenProducto(Request $request, string $token, int $productoId): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false], 410);
        }

        $producto = AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)->find($productoId);
        if (!$producto) {
            return response()->json(["ok" => false], 404);
        }

        $request->validate([
            "archivo" => "required|file|mimes:jpg,jpeg,png,webp,gif|max:10240",
        ]);

        $file = $request->file("archivo");
        $directorio = "/var/www/onboarding-storage/{$proyecto->id}/productos";
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $nombreSeguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $file->getClientOriginalName());
        $rutaCompleta = $directorio . "/" . $nombreSeguro;
        $file->move($directorio, $nombreSeguro);

        $archivo = AgenciaOnboardingArchivo::create([
            "proyecto_id"     => $proyecto->id,
            "seccion_key"     => $producto->seccion_key,
            "campo_key"       => "producto_imagen",
            "nombre_original" => $file->getClientOriginalName(),
            "ruta"            => "{$proyecto->id}/productos/{$nombreSeguro}",
            "mime_type"       => mime_content_type($rutaCompleta) ?: $file->getClientMimeType(),
            "tamano_bytes"    => filesize($rutaCompleta) ?: 0,
        ]);

        // Agregar a la galeria (no reemplazar)
        $ids = $producto->imagenesIds();
        $ids[] = $archivo->id;
        $producto->update([
            "imagenes" => $ids,
            "imagen_archivo_id" => $ids[0], // principal = primera
        ]);

        return response()->json([
            "ok" => true,
            "imagen" => [
                "id" => $archivo->id,
                "url" => route("onboarding.archivo.descargar", ["token" => $proyecto->token, "archivo" => $archivo->id]),
                "nombre" => $archivo->nombre_original,
            ],
            "imagenes" => $this->imagenesSerializadas($producto->fresh(), $proyecto->token),
        ]);
    }

    /**
     * DELETE - Elimina una imagen especifica de la galeria de un producto.
     */
    public function eliminarImagenProducto(string $token, int $productoId, int $archivoId): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false], 410);
        }

        $producto = AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)->find($productoId);
        if (!$producto) {
            return response()->json(["ok" => false], 404);
        }

        $ids = $producto->imagenesIds();
        $ids = array_values(array_filter($ids, fn($id) => (int)$id !== $archivoId));

        // Borrar archivo fisico + registro
        $img = AgenciaOnboardingArchivo::find($archivoId);
        if ($img) {
            $ruta = "/var/www/onboarding-storage/" . $img->ruta;
            if (is_file($ruta)) @unlink($ruta);
            $img->delete();
        }

        $producto->update([
            "imagenes" => $ids,
            "imagen_archivo_id" => $ids[0] ?? null,
        ]);

        return response()->json([
            "ok" => true,
            "imagenes" => $this->imagenesSerializadas($producto->fresh(), $proyecto->token),
        ]);
    }

    private function imagenesSerializadas(AgenciaOnboardingProducto $producto, string $token): array
    {
        return array_map(fn($id) => [
            "id" => $id,
            "url" => route("onboarding.archivo.descargar", ["token" => $token, "archivo" => $id]),
        ], $producto->imagenesIds());
    }

    /**
     * POST - Duplica un producto existente (copia con sufijo).
     */
    public function duplicarProducto(string $token, int $productoId): JsonResponse
    {
        $proyecto = $this->resolverProyecto($token);
        if ($proyecto instanceof Response) {
            return response()->json(["ok" => false], 410);
        }

        $original = AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)->find($productoId);
        if (!$original) {
            return response()->json(["ok" => false, "msg" => "producto no encontrado"], 404);
        }

        $copia = $original->replicate();
        $copia->titulo = $original->titulo . " (copia)";
        // Limpiar SKUs de las variantes para evitar duplicados en Shopify
        $variantes = $copia->variantes ?? [];
        foreach ($variantes as &$v) {
            if (!empty($v["sku"])) {
                $v["sku"] = $v["sku"] . "-COPIA";
            }
        }
        $copia->variantes = $variantes;
        $copia->orden = AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)
            ->where("seccion_key", $original->seccion_key)
            ->where("campo_key", $original->campo_key)
            ->count();
        $copia->save();

        AgenciaOnboardingEvento::registrar(
            $proyecto->id,
            "producto_duplicado",
            "Producto duplicado: {$copia->titulo}",
            ["original_id" => $original->id, "copia_id" => $copia->id]
        );

        $this->recalcularAvance($proyecto);

        return response()->json([
            "ok" => true,
            "producto" => $this->serializarProducto($copia->fresh("imagen")),
            "porcentaje" => $proyecto->fresh()->porcentaje_avance,
        ]);
    }

    // ===== Helpers privados =====

    private function validarProducto(Request $request): array
    {
        $data = $request->validate([
            "titulo" => "required|string|max:255",
            "descripcion" => "nullable|string",
            "vendor" => "nullable|string|max:255",
            "categoria" => "nullable|string|max:255",
            "tipo" => "nullable|string|max:120",
            "tags" => "nullable|string|max:500",
            "publicado" => "nullable|boolean",
            "estado" => "nullable|in:active,draft,archived",
            "seo_title" => "nullable|string|max:255",
            "seo_description" => "nullable|string|max:500",
            "imagen_alt" => "nullable|string|max:255",
            "opcion1_nombre" => "nullable|string|max:100",
            "opcion1_valores" => "nullable|array",
            "opcion2_nombre" => "nullable|string|max:100",
            "opcion2_valores" => "nullable|array",
            "opcion3_nombre" => "nullable|string|max:100",
            "opcion3_valores" => "nullable|array",
            "variantes" => "required|array|min:1",
            "variantes.*.sku" => "nullable|string|max:255",
            "variantes.*.precio" => "nullable|numeric|min:0",
            "variantes.*.compare_at" => "nullable|numeric|min:0",
            "variantes.*.costo" => "nullable|numeric|min:0",
            "variantes.*.stock" => "nullable|integer",
            "variantes.*.peso_g" => "nullable|numeric|min:0",
            "variantes.*.barcode" => "nullable|string|max:255",
            "variantes.*.opcion1_value" => "nullable|string|max:100",
            "variantes.*.opcion2_value" => "nullable|string|max:100",
            "variantes.*.opcion3_value" => "nullable|string|max:100",
            "requiere_envio" => "nullable|boolean",
            "es_gift_card" => "nullable|boolean",
        ]);

        // Asegurar valores default
        $data["publicado"] = $data["publicado"] ?? true;
        $data["estado"] = $data["estado"] ?? "active";
        $data["requiere_envio"] = $data["requiere_envio"] ?? true;
        $data["es_gift_card"] = $data["es_gift_card"] ?? false;

        return $data;
    }

    private function serializarProducto(AgenciaOnboardingProducto $p): array
    {
        return [
            "id" => $p->id,
            "titulo" => $p->titulo,
            "descripcion" => $p->descripcion,
            "vendor" => $p->vendor,
            "categoria" => $p->categoria,
            "tipo" => $p->tipo,
            "tags" => $p->tags,
            "publicado" => $p->publicado,
            "estado" => $p->estado,
            "seo_title" => $p->seo_title,
            "seo_description" => $p->seo_description,
            "imagen_url" => $p->imagen_archivo_id ? route("onboarding.archivo.descargar", ["token" => $p->proyecto->token, "archivo" => $p->imagen_archivo_id]) : null,
            "imagenes" => array_map(fn($id) => [
                "id" => $id,
                "url" => route("onboarding.archivo.descargar", ["token" => $p->proyecto->token, "archivo" => $id]),
            ], $p->imagenesIds()),
            "imagen_alt" => $p->imagen_alt,
            "opcion1_nombre" => $p->opcion1_nombre,
            "opcion1_valores" => $p->opcion1_valores ?? [],
            "opcion2_nombre" => $p->opcion2_nombre,
            "opcion2_valores" => $p->opcion2_valores ?? [],
            "opcion3_nombre" => $p->opcion3_nombre,
            "opcion3_valores" => $p->opcion3_valores ?? [],
            "variantes" => $p->variantes ?? [],
            "requiere_envio" => $p->requiere_envio,
            "es_gift_card" => $p->es_gift_card,
        ];
    }

    private function marcarRespuestaProductos(AgenciaOnboardingProyecto $proyecto, string $seccionKey, string $campoKey): void
    {
        AgenciaOnboardingRespuesta::updateOrCreate(
            [
                "proyecto_id" => $proyecto->id,
                "seccion_key" => $seccionKey,
                "campo_key"   => $campoKey,
            ],
            ["valor" => "productos_cargados"]
        );
    }



    private function resolverProyecto(string $token)
    {
        $proyecto = AgenciaOnboardingProyecto::with(["cliente", "plantilla"])
            ->where("token", $token)
            ->firstOrFail();

        if (!$proyecto->estaActivo()) {
            return response()->view("onboarding.expirado", ["proyecto" => $proyecto], 410);
        }

        return $proyecto;
    }

    private function registrarPrimerAcceso(AgenciaOnboardingProyecto $proyecto): void
    {
        if (!$proyecto->fecha_primer_acceso) {
            $proyecto->update([
                "fecha_primer_acceso" => now(),
                "estado" => "en_progreso",
            ]);
            AgenciaOnboardingEvento::registrar(
                $proyecto->id,
                "abierto",
                "Cliente abrió el onboarding por primera vez"
            );
        }
    }

    private function recalcularAvance(AgenciaOnboardingProyecto $proyecto): void
    {
        $secciones = $proyecto->plantilla->secciones ?? [];
        $totalRequeridos = 0;
        $completados    = 0;

        $respuestas = $proyecto->respuestas()
            ->get()
            ->groupBy("seccion_key");

        foreach ($secciones as $seccion) {
            foreach (($seccion["campos"] ?? []) as $campo) {
                if (($campo["requerido"] ?? false) === true) {
                    $totalRequeridos++;
                    $resp = $respuestas->get($seccion["key"], collect())->firstWhere("campo_key", $campo["key"]);
                    if ($resp && trim((string)$resp->valor) !== "") {
                        $completados++;
                    }
                }
            }
        }

        $porcentaje = $totalRequeridos === 0 ? 0 : (int) round(($completados / $totalRequeridos) * 100);
        $proyecto->update(["porcentaje_avance" => $porcentaje]);
    }

    private function seccionEstaCompleta(AgenciaOnboardingProyecto $proyecto, array $seccion): bool
    {
        $requeridos = array_filter($seccion["campos"] ?? [], fn($c) => ($c["requerido"] ?? false) === true);
        if (empty($requeridos)) return true;

        $respuestas = $proyecto->respuestas()
            ->where("seccion_key", $seccion["key"])
            ->pluck("valor", "campo_key")
            ->toArray();

        foreach ($requeridos as $campo) {
            if (empty($respuestas[$campo["key"]])) {
                return false;
            }
        }
        return true;
    }
}
