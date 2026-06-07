<?php

namespace App\Http\Controllers;

use App\Mail\OnboardingInvitacionMail;
use App\Models\AgenciaCliente;
use App\Models\AgenciaOnboardingPlantilla;
use App\Models\AgenciaOnboardingProyecto;
use App\Models\AgenciaOnboardingEvento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AgenciaOnboardingController extends Controller
{
    /**
     * Lista de onboardings con su estado.
     */
    public function index(Request $request)
    {
        $q = AgenciaOnboardingProyecto::with(["cliente", "plantilla"])->orderByDesc("created_at");

        if ($buscar = trim((string)$request->input("buscar"))) {
            $q->where(function ($w) use ($buscar) {
                $w->where("titulo", "like", "%{$buscar}%")
                  ->orWhere("email_cliente", "like", "%{$buscar}%")
                  ->orWhereHas("cliente", function ($c) use ($buscar) {
                      $c->where("nombre", "like", "%{$buscar}%")
                        ->orWhere("rut", "like", "%{$buscar}%")
                        ->orWhere("email", "like", "%{$buscar}%");
                  });
            });
        }

        if ($estado = $request->input("estado")) {
            $q->where("estado", $estado);
        }

        if ($plantillaId = $request->input("plantilla_id")) {
            $q->where("plantilla_id", $plantillaId);
        }

        $proyectos = $q->paginate(25)->withQueryString();

        $contadores = [
            "no_iniciado" => AgenciaOnboardingProyecto::where("estado", "no_iniciado")->count(),
            "en_progreso" => AgenciaOnboardingProyecto::where("estado", "en_progreso")->count(),
            "completado"  => AgenciaOnboardingProyecto::where("estado", "completado")->count(),
        ];

        $plantillas = \App\Models\AgenciaOnboardingPlantilla::orderBy("nombre")->get(["id", "nombre"]);

        return view("agencia.onboardings.index", compact("proyectos", "contadores", "plantillas"));
    }

    /**
     * Formulario para crear un nuevo onboarding.
     */
    public function create()
    {
        $clientes = AgenciaCliente::orderBy("nombre")->get();
        $plantillas = AgenciaOnboardingPlantilla::activas()->orderBy("nombre")->get();

        return view("agencia.onboardings.create", compact("clientes", "plantillas"));
    }

    /**
     * Guardar un nuevo onboarding.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            "agencia_cliente_id" => "required|exists:agencia_clientes,id",
            "plantilla_id" => "required|exists:agencia_onboarding_plantillas,id",
            "titulo" => "required|string|max:255",
            "email_cliente" => "nullable|email|max:255",
            "notas_internas" => "nullable|string",
            "dias_validez_token" => "nullable|integer|min:1|max:365",
        ]);

        $proyecto = AgenciaOnboardingProyecto::create([
            "agencia_cliente_id" => $data["agencia_cliente_id"],
            "plantilla_id" => $data["plantilla_id"],
            "titulo" => $data["titulo"],
            "email_cliente" => $data["email_cliente"] ?? null,
            "notas_internas" => $data["notas_internas"] ?? null,
            "token_expira_en" => now()->addDays($data["dias_validez_token"] ?? 60),
        ]);

        AgenciaOnboardingEvento::registrar($proyecto->id, "creado", "Onboarding creado por el equipo BigStudio");

        return redirect()
            ->route("agencia.onboardings.show", $proyecto)
            ->with("success", "Onboarding creado. El link publico es: " . $proyecto->urlPublica());
    }

    /**
     * Detalle de un onboarding: respuestas, archivos, eventos.
     */
    public function show(AgenciaOnboardingProyecto $onboarding)
    {
        $onboarding->load(["cliente", "plantilla", "respuestas", "archivos", "eventos" => fn($q) => $q->latest("created_at")->limit(50)]);

        return view("agencia.onboardings.show", ["proyecto" => $onboarding]);
    }

    /**
     * Formulario para editar campos clave del onboarding.
     */
    public function edit(AgenciaOnboardingProyecto $onboarding)
    {
        $onboarding->load(["cliente", "plantilla"]);
        $plantillas = AgenciaOnboardingPlantilla::activas()->orderBy("nombre")->get();
        return view("agencia.onboardings.edit", ["proyecto" => $onboarding, "plantillas" => $plantillas]);
    }

    /**
     * Actualiza campos administrativos del onboarding (titulo, email, notas, vigencia, estado).
     */
    public function update(Request $request, AgenciaOnboardingProyecto $onboarding)
    {
        $data = $request->validate([
            "titulo" => "required|string|max:255",
            "email_cliente" => "nullable|email|max:255",
            "notas_internas" => "nullable|string",
            "estado" => "required|in:no_iniciado,en_progreso,completado,archivado",
            "dias_validez_extra" => "nullable|integer|min:1|max:365",
            "plantilla_id" => "required|exists:agencia_onboarding_plantillas,id",
        ]);

        $tokenExpiraEn = $onboarding->token_expira_en;
        if (!empty($data["dias_validez_extra"])) {
            $base = $tokenExpiraEn && $tokenExpiraEn->isFuture() ? $tokenExpiraEn : now();
            $tokenExpiraEn = $base->copy()->addDays((int)$data["dias_validez_extra"]);
        }

        $cambiosPlantilla = $onboarding->plantilla_id !== (int)$data["plantilla_id"];

        $onboarding->update([
            "titulo" => $data["titulo"],
            "email_cliente" => $data["email_cliente"] ?? null,
            "notas_internas" => $data["notas_internas"] ?? null,
            "estado" => $data["estado"],
            "plantilla_id" => $data["plantilla_id"],
            "token_expira_en" => $tokenExpiraEn,
        ]);

        AgenciaOnboardingEvento::registrar(
            $onboarding->id,
            "editado",
            "Datos del proyecto editados desde el admin"
            . ($cambiosPlantilla ? " (cambio de plantilla)" : "")
        );

        return redirect()
            ->route("agencia.onboardings.show", $onboarding)
            ->with("success", "Onboarding actualizado.");
    }

    /**
     * Eliminar un onboarding (soft delete via archivado, o hard delete si se pide).
     */
    public function destroy(AgenciaOnboardingProyecto $onboarding)
    {
        $onboarding->update(["estado" => "archivado"]);
        return redirect()->route("agencia.onboardings.index")->with("success", "Onboarding archivado.");
    }

    /**
     * Envia el link del onboarding al email del cliente.
     */
    public function enviarInvitacion(Request $request, AgenciaOnboardingProyecto $onboarding)
    {
        $request->validate([
            "email" => "required|email|max:255",
        ]);

        $email = $request->input("email");

        if ($onboarding->email_cliente !== $email) {
            $onboarding->update(["email_cliente" => $email]);
        }

        try {
            Mail::send(new OnboardingInvitacionMail($onboarding, $email));
            $onboarding->update(["fecha_envio" => now()]);
            AgenciaOnboardingEvento::registrar(
                $onboarding->id,
                "enviado",
                "Email de invitacion enviado a {$email}"
            );
            return back()->with("success", "Invitacion enviada a {$email}.");
        } catch (\Throwable $e) {
            Log::error("Onboarding invitacion failed: " . $e->getMessage());
            AgenciaOnboardingEvento::registrar(
                $onboarding->id,
                "envio_fallido",
                $e->getMessage()
            );
            return back()->withErrors(["email" => "Error al enviar: " . $e->getMessage()]);
        }
    }

    /**
     * Vista print-friendly del onboarding (para imprimir o guardar como PDF).
     */
    public function imprimir(AgenciaOnboardingProyecto $onboarding)
    {
        $onboarding->load(["cliente", "plantilla", "respuestas", "archivos"]);
        return view("agencia.onboardings.imprimir", ["proyecto" => $onboarding]);
    }

    /**
     * Descarga todos los archivos del proyecto como ZIP organizado por seccion.
     */
    public function descargarZip(AgenciaOnboardingProyecto $onboarding)
    {
        $onboarding->load(["cliente", "plantilla", "archivos"]);

        if ($onboarding->archivos->isEmpty()) {
            return back()->with("error", "Este onboarding no tiene archivos subidos.");
        }

        if (!class_exists(\ZipArchive::class)) {
            return back()->with("error", "Extension PHP zip no disponible en el servidor.");
        }

        $clienteSlug = \Illuminate\Support\Str::slug($onboarding->cliente->nombre ?? "cliente");
        $tsZip = now()->format("Ymd_His");
        $zipName = "onboarding_{$onboarding->id}_{$clienteSlug}_{$tsZip}.zip";
        $tmpPath = "/tmp/{$zipName}";

        // Mapear seccion_key -> titulo legible
        $secciones = $onboarding->plantilla->secciones ?? [];
        $seccionLabels = [];
        foreach ($secciones as $s) {
            $seccionLabels[$s["key"]] = $s["titulo"] ?? $s["key"];
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with("error", "No se pudo crear el archivo ZIP.");
        }

        // Incluir un README con metadata
        $readme = "Onboarding #{$onboarding->id}\n";
        $readme .= "Cliente: " . ($onboarding->cliente->nombre ?? "-") . "\n";
        $readme .= "Proyecto: {$onboarding->titulo}\n";
        $readme .= "Plantilla: " . ($onboarding->plantilla->nombre ?? "-") . "\n";
        $readme .= "Avance: {$onboarding->porcentaje_avance}%\n";
        $readme .= "Generado: " . now()->format("d/m/Y H:i") . "\n";
        $zip->addFromString("_README.txt", $readme);

        foreach ($onboarding->archivos as $a) {
            $rutaCompleta = "/var/www/onboarding-storage/" . $a->ruta;
            if (!is_file($rutaCompleta)) continue;

            $carpeta = $seccionLabels[$a->seccion_key] ?? $a->seccion_key;
            $carpeta = \Illuminate\Support\Str::slug($carpeta, "_");
            $nombreEnZip = "{$carpeta}/{$a->nombre_original}";
            // Evitar colisiones de nombre
            $counter = 1;
            $base = pathinfo($a->nombre_original, PATHINFO_FILENAME);
            $ext = pathinfo($a->nombre_original, PATHINFO_EXTENSION);
            while ($zip->locateName($nombreEnZip) !== false) {
                $nombreEnZip = "{$carpeta}/{$base}_{$counter}" . ($ext ? ".{$ext}" : "");
                $counter++;
            }
            $zip->addFile($rutaCompleta, $nombreEnZip);
        }

        $zip->close();

        return response()->download($tmpPath, $zipName)->deleteFileAfterSend(true);
    }
}
