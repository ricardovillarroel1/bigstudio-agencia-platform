<?php

namespace App\Http\Controllers;

use App\Models\AgenciaCliente;
use App\Models\AgenciaOnboardingPlantilla;
use App\Models\AgenciaOnboardingProyecto;
use App\Models\AgenciaOnboardingEvento;
use Illuminate\Http\Request;

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
            "notas_internas" => "nullable|string",
            "dias_validez_token" => "nullable|integer|min:1|max:365",
        ]);

        $proyecto = AgenciaOnboardingProyecto::create([
            "agencia_cliente_id" => $data["agencia_cliente_id"],
            "plantilla_id" => $data["plantilla_id"],
            "titulo" => $data["titulo"],
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
}
