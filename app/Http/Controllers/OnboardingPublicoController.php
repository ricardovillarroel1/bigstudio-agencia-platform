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

    /**
     * Sube y parsea un CSV de Shopify con productos. Devuelve preview JSON.
     */
    public function subirCsvProductos(Request $request, string $token, int $indice, string $campoKey)
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
            "archivo" => "required|file|mimes:csv,txt|max:10240",
        ]);

        $file = $request->file("archivo");
        $directorio = "/var/www/onboarding-storage/{$proyecto->id}/{$seccionKey}";
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $nombreSeguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $file->getClientOriginalName());
        $rutaCompleta = $directorio . "/" . $nombreSeguro;
        $file->move($directorio, $nombreSeguro);

        // Reemplazo total: borrar productos y archivos previos del mismo campo
        AgenciaOnboardingProducto::where("proyecto_id", $proyecto->id)
            ->where("seccion_key", $seccionKey)
            ->where("campo_key", $campoKey)
            ->delete();
        AgenciaOnboardingArchivo::where("proyecto_id", $proyecto->id)
            ->where("seccion_key", $seccionKey)
            ->where("campo_key", $campoKey)
            ->get()->each(function ($a) {
                $rutaVieja = "/var/www/onboarding-storage/" . $a->ruta;
                if (is_file($rutaVieja)) @unlink($rutaVieja);
                $a->delete();
            });

        $archivo = AgenciaOnboardingArchivo::create([
            "proyecto_id"     => $proyecto->id,
            "seccion_key"     => $seccionKey,
            "campo_key"       => $campoKey,
            "nombre_original" => $file->getClientOriginalName(),
            "ruta"            => "{$proyecto->id}/{$seccionKey}/{$nombreSeguro}",
            "mime_type"       => "text/csv",
            "tamano_bytes"    => filesize($rutaCompleta) ?: 0,
        ]);

        $parser = new ShopifyProductCsvParser();
        $resultado = $parser->parse($rutaCompleta);

        $erroresFatal = collect($resultado["errores"] ?? [])
            ->whereIn("tipo", ["csv_no_shopify", "headers_faltantes", "archivo_no_encontrado", "no_lectura"])
            ->count();

        if ($erroresFatal > 0) {
            @unlink($rutaCompleta);
            $archivo->delete();
            return response()->json([
                "ok" => false,
                "msg" => "El CSV no es compatible con la plantilla de Shopify.",
                "errores" => $resultado["errores"],
            ], 422);
        }

        AgenciaOnboardingProducto::create([
            "proyecto_id"     => $proyecto->id,
            "archivo_id"      => $archivo->id,
            "seccion_key"     => $seccionKey,
            "campo_key"       => $campoKey,
            "productos"       => $resultado["productos"],
            "total_productos" => $resultado["total_productos"],
            "total_variantes" => $resultado["total_variantes"],
            "warnings"        => $resultado["warnings"] ?: null,
            "errores"         => $resultado["errores"] ?: null,
            "parseado_at"     => now(),
        ]);

        AgenciaOnboardingRespuesta::updateOrCreate(
            [
                "proyecto_id" => $proyecto->id,
                "seccion_key" => $seccionKey,
                "campo_key"   => $campoKey,
            ],
            ["valor" => "csv_subido:{$resultado['total_productos']}_productos_{$resultado['total_variantes']}_variantes"]
        );

        AgenciaOnboardingEvento::registrar(
            $proyecto->id,
            "catalogo_csv_subido",
            "CSV de productos subido: {$resultado['total_productos']} productos / {$resultado['total_variantes']} variantes",
            ["archivo_id" => $archivo->id]
        );

        $this->recalcularAvance($proyecto);

        return response()->json([
            "ok" => true,
            "archivo" => [
                "id"     => $archivo->id,
                "nombre" => $archivo->nombre_original,
                "tamano" => $archivo->tamanoLegible(),
            ],
            "resumen" => [
                "total_productos" => $resultado["total_productos"],
                "total_variantes" => $resultado["total_variantes"],
                "warnings"        => count($resultado["warnings"]),
                "errores"         => count($resultado["errores"]),
            ],
            "warnings" => array_slice($resultado["warnings"], 0, 10),
            "errores" => $resultado["errores"],
            "productos_preview" => array_slice($resultado["productos"], 0, 5),
            "porcentaje" => $proyecto->fresh()->porcentaje_avance,
        ]);
    }

    /**
     * Endpoint publico para descargar la plantilla CSV oficial de Shopify.
     */
    public function descargarPlantillaCsv()
    {
        $ruta = public_path("templates/shopify_productos_plantilla.csv");
        if (!is_file($ruta)) {
            abort(404, "Plantilla no encontrada.");
        }
        return response()->download($ruta, "shopify_productos_plantilla.csv", [
            "Content-Type" => "text/csv",
        ]);
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
