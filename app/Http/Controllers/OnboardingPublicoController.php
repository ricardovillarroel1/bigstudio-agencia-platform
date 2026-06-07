<?php

namespace App\Http\Controllers;

use App\Models\AgenciaOnboardingProyecto;
use App\Models\AgenciaOnboardingEvento;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OnboardingPublicoController extends Controller
{
    /**
     * Vista publica del onboarding (sin login, acceso por token).
     * Sprint 0: solo verifica token y muestra "hola" con datos basicos.
     * Sprint 2: aqui se monta el wizard completo.
     */
    public function mostrar(Request $request, string $token): Response
    {
        $proyecto = AgenciaOnboardingProyecto::with(["cliente", "plantilla"])
            ->where("token", $token)
            ->firstOrFail();

        // Validar vigencia del token
        if (!$proyecto->estaActivo()) {
            return response()
                ->view("onboarding.expirado", ["proyecto" => $proyecto], 410);
        }

        // Registrar primer acceso
        if (!$proyecto->fecha_primer_acceso) {
            $proyecto->update([
                "fecha_primer_acceso" => now(),
                "estado" => "en_progreso",
            ]);
            AgenciaOnboardingEvento::registrar(
                $proyecto->id,
                "abierto",
                "Cliente abrio el onboarding por primera vez"
            );
        }

        return response()->view("onboarding.bienvenida", [
            "proyecto" => $proyecto,
        ]);
    }
}
