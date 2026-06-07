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
    public function index()
    {
        $proyectos = AgenciaOnboardingProyecto::with(["cliente", "plantilla"])
            ->orderByDesc("created_at")
            ->paginate(25);

        $contadores = [
            "no_iniciado" => AgenciaOnboardingProyecto::where("estado", "no_iniciado")->count(),
            "en_progreso" => AgenciaOnboardingProyecto::where("estado", "en_progreso")->count(),
            "completado"  => AgenciaOnboardingProyecto::where("estado", "completado")->count(),
        ];

        return view("agencia.onboardings.index", compact("proyectos", "contadores"));
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
}
