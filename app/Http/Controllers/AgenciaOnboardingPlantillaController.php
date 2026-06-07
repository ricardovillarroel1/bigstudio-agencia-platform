<?php

namespace App\Http\Controllers;

use App\Models\AgenciaOnboardingPlantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgenciaOnboardingPlantillaController extends Controller
{
    public function index()
    {
        $plantillas = AgenciaOnboardingPlantilla::withCount("proyectos")
            ->orderBy("nombre")
            ->paginate(25);

        return view("agencia.onboardings.plantillas.index", compact("plantillas"));
    }

    public function create()
    {
        return view("agencia.onboardings.plantillas.create");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "nombre" => "required|string|max:255",
            "slug" => "nullable|string|max:120|alpha_dash|unique:agencia_onboarding_plantillas,slug",
            "tipo_servicio" => "required|string|in:shopify_prototipo,shopify_produccion,meta_ads,seo_mensual,seo_auditoria,mantencion,integracion,otro",
            "descripcion" => "nullable|string|max:1000",
            "dias_habiles_estimados" => "required|integer|min:1|max:365",
            "secciones_json" => "required|string",
            "activo" => "nullable|boolean",
        ]);

        $secciones = json_decode($data["secciones_json"], true);
        if (!is_array($secciones)) {
            return back()->withErrors(["secciones_json" => "JSON inválido"])->withInput();
        }

        $plantilla = AgenciaOnboardingPlantilla::create([
            "nombre" => $data["nombre"],
            "slug" => $data["slug"] ?: Str::slug($data["nombre"]),
            "tipo_servicio" => $data["tipo_servicio"],
            "descripcion" => $data["descripcion"] ?? null,
            "dias_habiles_estimados" => $data["dias_habiles_estimados"],
            "secciones" => $secciones,
            "activo" => (bool)($data["activo"] ?? true),
        ]);

        return redirect()
            ->route("agencia.onboardings.plantillas.index")
            ->with("success", "Plantilla \"{$plantilla->nombre}\" creada.");
    }

    public function edit(AgenciaOnboardingPlantilla $plantilla)
    {
        return view("agencia.onboardings.plantillas.edit", compact("plantilla"));
    }

    public function update(Request $request, AgenciaOnboardingPlantilla $plantilla)
    {
        $data = $request->validate([
            "nombre" => "required|string|max:255",
            "tipo_servicio" => "required|string",
            "descripcion" => "nullable|string|max:1000",
            "dias_habiles_estimados" => "required|integer|min:1|max:365",
            "secciones_json" => "required|string",
            "activo" => "nullable|boolean",
        ]);

        $secciones = json_decode($data["secciones_json"], true);
        if (!is_array($secciones)) {
            return back()->withErrors(["secciones_json" => "JSON inválido"])->withInput();
        }

        $plantilla->update([
            "nombre" => $data["nombre"],
            "tipo_servicio" => $data["tipo_servicio"],
            "descripcion" => $data["descripcion"] ?? null,
            "dias_habiles_estimados" => $data["dias_habiles_estimados"],
            "secciones" => $secciones,
            "activo" => (bool)($data["activo"] ?? false),
        ]);

        return redirect()
            ->route("agencia.onboardings.plantillas.index")
            ->with("success", "Plantilla actualizada.");
    }

    public function toggle(AgenciaOnboardingPlantilla $plantilla)
    {
        $plantilla->update(["activo" => !$plantilla->activo]);
        return back()->with("success", "Plantilla " . ($plantilla->activo ? "activada" : "desactivada") . ".");
    }

    public function destroy(AgenciaOnboardingPlantilla $plantilla)
    {
        if ($plantilla->proyectos()->exists()) {
            return back()->withErrors(["plantilla" => "No se puede eliminar: hay proyectos usando esta plantilla."]);
        }
        $plantilla->delete();
        return redirect()
            ->route("agencia.onboardings.plantillas.index")
            ->with("success", "Plantilla eliminada.");
    }
}
