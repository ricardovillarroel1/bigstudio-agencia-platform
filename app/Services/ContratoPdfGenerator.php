<?php

namespace App\Services;

use App\Models\AgenciaOnboardingProyecto;
use App\Models\AgenciaContratoPlantilla;

class ContratoPdfGenerator
{
    /**
     * Genera el PDF del contrato y devuelve el binario (string).
     */
    public function generar(AgenciaOnboardingProyecto $proyecto, AgenciaContratoPlantilla $contrato): string
    {
        $proyecto->loadMissing("cliente");
        $cliente = $proyecto->cliente;

        // Reemplazar placeholders en clausulas con datos del cliente
        $clienteNombre = $cliente->nombre ?? "Cliente";
        $clienteRut = $cliente->rut ?? "";
        $clienteEmail = $proyecto->email_cliente ?: ($cliente->email ?? "");

        $clausulas = collect($contrato->clausulas ?? [])->map(function ($cl) use ($clienteNombre, $clienteRut) {
            $cont = $cl["contenido"] ?? "";
            $cont = str_replace("{{CLIENTE_NOMBRE}}", e($clienteNombre), $cont);
            $cont = str_replace("{{CLIENTE_RUT}}", $clienteRut ? (", RUT " . e($clienteRut)) : "", $cont);
            $cl["contenido"] = $cont;
            return $cl;
        })->all();

        // Clonar el contrato con clausulas procesadas (sin tocar la BD)
        $contratoView = clone $contrato;
        $contratoView->clausulas = $clausulas;

        $html = view("pdf.contrato", [
            "contrato" => $contratoView,
            "proyecto" => $proyecto,
            "clienteNombre" => $clienteNombre,
            "clienteRut" => $clienteRut,
            "clienteEmail" => $clienteEmail,
            "fecha" => now()->format("d/m/Y"),
        ])->render();

        $mpdf = new \Mpdf\Mpdf([
            "tempDir" => storage_path("app/mpdf-tmp"),
            "format" => "A4",
            "margin_top" => 14,
            "margin_bottom" => 14,
            "margin_left" => 15,
            "margin_right" => 15,
        ]);
        $mpdf->SetTitle("Contrato " . $contrato->nombre);
        $mpdf->WriteHTML($html);

        return $mpdf->Output("", \Mpdf\Output\Destination::STRING_RETURN);
    }
}
