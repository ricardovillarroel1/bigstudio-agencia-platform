<?php

namespace App\Http\Controllers;

use App\Models\MetaAdAccount;
use App\Models\AgenciaCliente;
use App\Services\MetaAdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetaAdsController extends Controller
{
    /** Reporte dinámico. Soporta período por mes (YYYY-MM) o rango (desde/hasta). */
    public function reporte(Request $request)
    {
        $cuentas = MetaAdAccount::with('cliente')->orderBy('nombre_cuenta')->get();
        $cuentaId = $request->input('cuenta_id', optional($cuentas->first())->id);
        $cuenta = $cuentas->firstWhere('id', $cuentaId);

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $usaRango = $desde && $hasta;

        // Clave de período: rango custom o mes
        $periodo = $usaRango ? ($desde . '_' . $hasta) : $request->input('periodo', now()->format('Y-m'));

        $resumen = null;
        $campanas = collect();
        $demoEdad = collect();
        $demoGenero = collect();
        $demoRegion = collect();
        $autoSync = false;

        if ($cuenta) {
            $resumen = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();

            // Si no hay datos del período, sincroniza automáticamente (rango O mes).
            if (!$resumen) {
                try {
                    $svc = new MetaAdsService();
                    if ($usaRango) {
                        $svc->syncAccountRango($cuenta, $desde, $hasta);
                    } else {
                        $svc->syncAccount($cuenta, $periodo); // mes YYYY-MM
                    }
                    $autoSync = true;
                    $resumen = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                        ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Meta auto-sync: ' . $e->getMessage());
                }
            }

            $campanas = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'campania')
                ->orderByDesc('ventas')->get();

            // Demograficos
            $demoEdad = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'demo_age')
                ->orderBy('objeto_nombre')->get();
            $demoGenero = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'demo_gender')
                ->orderByDesc('inversion')->get();
            $demoRegion = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'demo_region')
                ->orderByDesc('ventas')->get();
        }

        // === Mes anterior para comparativas (solo si estamos en modo mes YYYY-MM, no rango) ===
        $previo = null;
        $comparativas = [];
        $bullets = [];
        $recomendaciones = [];
        if ($cuenta && !$usaRango && preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            try {
                $periodoPrev = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->startOfMonth()->subMonthNoOverflow()->format('Y-m');
                $previo = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodoPrev)->where('nivel', 'cuenta')->first();
            } catch (\Throwable $e) {}
            if ($resumen) {
                $comparativas = \App\Services\ReporteAnalyzer::comparativas($resumen, $previo);
                $bullets = \App\Services\ReporteAnalyzer::resumenEjecutivo($resumen, $previo, $campanas);
                $recomendaciones = \App\Services\ReporteAnalyzer::recomendaciones($resumen, $previo, $campanas, $demoRegion);
            }
        }

        $periodos = $cuenta
            ? \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                ->where('nivel', 'cuenta')->where('periodo', 'not like', '%\_%')
                ->distinct()->orderByDesc('periodo')->pluck('periodo')->toArray()
            : [];

        return view('agencia.reportes.meta', compact(
            'cuentas', 'cuenta', 'periodo', 'periodos',
            'resumen', 'campanas', 'desde', 'hasta', 'usaRango', 'autoSync',
            'demoEdad', 'demoGenero', 'demoRegion',
            'previo', 'comparativas', 'bullets', 'recomendaciones'
        ));
    }

    /** Pantalla de conexión: token + listado de cuentas vinculadas. */
    public function index()
    {
        $svc = new MetaAdsService();
        $cuentas = MetaAdAccount::with('cliente')->orderBy('nombre_cuenta')->get();
        $clientes = AgenciaCliente::where('estado', 'activo')->orderBy('nombre')->get();
        $tokenSet = $svc->hasToken();
        // Cuentas disponibles en Meta (si hay token) para sugerir al vincular
        $cuentasMeta = $tokenSet ? $svc->listAdAccounts() : [];

        return view('agencia.reportes.conexion', compact('cuentas', 'clientes', 'tokenSet', 'cuentasMeta'));
    }

    /** Guarda (o actualiza) el System User token de Meta en settings. */
    public function guardarToken(Request $request)
    {
        $request->validate(['meta_system_token' => ['required', 'string', 'min:20']]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'meta_system_token'],
            ['value' => trim($request->meta_system_token), 'updated_at' => now()]
        );

        return redirect()->route('agencia.reportes.conexion')
            ->with('success', 'Token de Meta guardado. Ya puedes vincular cuentas publicitarias.');
    }

    /** Vincula una cuenta publicitaria a un cliente. */
    public function vincularCuenta(Request $request)
    {
        $request->validate([
            'agencia_cliente_id' => ['nullable', 'exists:agencia_clientes,id'],
            'nombre_cuenta' => ['required', 'string', 'max:255'],
            'act_id' => ['required', 'string', 'max:100'],
            'moneda' => ['nullable', 'string', 'max:10'],
        ]);

        $actId = trim($request->act_id);
        if (!str_starts_with($actId, 'act_')) {
            $actId = 'act_' . ltrim($actId, 'act_');
        }

        MetaAdAccount::updateOrCreate(
            ['act_id' => $actId],
            [
                'agencia_cliente_id' => $request->agencia_cliente_id,
                'nombre_cuenta' => $request->nombre_cuenta,
                'moneda' => $request->moneda ?: 'CLP',
                'estado' => 'activa',
            ]
        );

        return redirect()->route('agencia.reportes.conexion')
            ->with('success', 'Cuenta publicitaria vinculada correctamente.');
    }

    /** Elimina la vinculación (no borra datos en Meta). */
    public function eliminarCuenta(MetaAdAccount $cuenta)
    {
        $cuenta->insights()->delete();
        $cuenta->delete();
        return redirect()->route('agencia.reportes.conexion')
            ->with('success', 'Cuenta desvinculada.');
    }

    /** Guarda la configuración de envío automático del reporte al cliente. */
    public function guardarEnvio(Request $request, MetaAdAccount $cuenta)
    {
        $request->validate([
            'reporte_emails' => ['nullable', 'string'],
            'reporte_dias' => ['nullable', 'string', 'max:80'],
        ]);

        // Parsear correos separados por coma/espacio/salto y validar.
        $raw = preg_split('/[\s,;]+/', (string) $request->reporte_emails, -1, PREG_SPLIT_NO_EMPTY);
        $emails = [];
        foreach ($raw as $e) {
            $e = strtolower(trim($e));
            if (filter_var($e, FILTER_VALIDATE_EMAIL) && !in_array($e, $emails, true)) {
                $emails[] = $e;
            }
        }

        if (empty($cuenta->reporte_token)) {
            $cuenta->reporte_token = \Illuminate\Support\Str::random(40);
        }
        $cuenta->reporte_emails = $emails;
        $cuenta->reporte_dias = trim((string) $request->reporte_dias) ?: null;
        $cuenta->reporte_activo = $request->boolean('reporte_activo');
        $cuenta->save();

        $msg = 'Configuración de envío guardada para ' . $cuenta->nombre_cuenta . '.';
        if ($cuenta->reporte_activo && $cuenta->reporte_dias && !empty($emails)) {
            $msg .= ' Se enviará automáticamente los días: ' . $cuenta->reporte_dias . '.';
        } elseif ($cuenta->reporte_activo && (empty($cuenta->reporte_dias) || empty($emails))) {
            $msg .= ' ⚠ Está activado pero falta(n): '
                . (empty($cuenta->reporte_dias) ? 'días de envío' : '')
                . (empty($cuenta->reporte_dias) && empty($emails) ? ' y ' : '')
                . (empty($emails) ? 'correos destinatarios' : '')
                . '.';
        }

        // Si la request vino del reporte (campo periodo presente), volver allá. Si no, a conexión.
        if ($request->filled('periodo') || str_contains((string) $request->headers->get('referer'), '/reportes/meta')) {
            return redirect()->back()->with('success', $msg);
        }
        return redirect()->route('agencia.reportes.conexion')->with('success', $msg);
    }

    /** Envía el reporte AHORA (manual) al/los correos configurados. */
    public function enviarAhora(Request $request, MetaAdAccount $cuenta)
    {
        // Si el usuario pasó correos ad-hoc desde el modal, usarlos. Si no, usar los configurados o el del cliente.
        $emailsRequest = trim((string) $request->input('emails', ''));
        $destinos = [];
        if ($emailsRequest !== '') {
            $raw = preg_split('/[\s,;]+/', $emailsRequest, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($raw as $e) {
                $e = strtolower(trim($e));
                if (filter_var($e, FILTER_VALIDATE_EMAIL) && !in_array($e, $destinos, true)) {
                    $destinos[] = $e;
                }
            }
        }
        if (empty($destinos)) {
            $destinos = is_array($cuenta->reporte_emails) ? $cuenta->reporte_emails : [];
        }
        if (empty($destinos) && $cuenta->cliente && filter_var($cuenta->cliente->email, FILTER_VALIDATE_EMAIL)) {
            $destinos = [$cuenta->cliente->email];
        }

        if (empty($destinos)) {
            return back()->with('error', 'No hay correo destinatario. Indica uno o configura el correo del cliente.');
        }

        // Si el usuario pidió "guardar estos correos como destinatarios fijos", actualizamos la cuenta.
        $guardar = $request->boolean('guardar_emails');
        if ($guardar) {
            if (empty($cuenta->reporte_token)) {
                $cuenta->reporte_token = \Illuminate\Support\Str::random(40);
            }
            $cuenta->reporte_emails = $destinos;
            $cuenta->save();
        }

        $periodo = $request->input('periodo'); // opcional; si no se pasa, el sender usa el mes anterior

        try {
            (new \App\Services\MetaReporteSender())->enviar($cuenta, $periodo, $destinos);
            $msg = 'Reporte enviado a: ' . implode(', ', $destinos);
            if ($guardar) {
                $msg .= ' · Correos guardados como destinatarios fijos.';
            }
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Meta enviarAhora: ' . $e->getMessage());
            return back()->with('error', 'Error al enviar: ' . $e->getMessage());
        }
    }

    /**
     * Reporte público (sin auth) accesible vía token único de la cuenta.
     * Ruta: GET /reporte-meta/{token}?periodo=YYYY-MM
     * Si no se pasa periodo, usa el mes anterior por defecto (el que se envía por correo).
     */
    public function reportePublico(Request $request, string $token)
    {
        $cuenta = MetaAdAccount::with('cliente')->where('reporte_token', $token)->first();
        if (!$cuenta) {
            abort(404, 'Reporte no encontrado o link inválido.');
        }

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $usaRango = $desde && $hasta;

        $periodo = $usaRango
            ? ($desde . '_' . $hasta)
            : $request->input('periodo', now()->subMonthNoOverflow()->format('Y-m'));

        $resumen = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();

        // Si no hay datos del período, intenta sincronizar (igual que el reporte privado).
        if (!$resumen) {
            try {
                $svc = new MetaAdsService();
                if ($usaRango) {
                    $svc->syncAccountRango($cuenta, $desde, $hasta);
                } else {
                    $svc->syncAccount($cuenta, $periodo);
                }
                $resumen = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Meta reportePublico auto-sync: ' . $e->getMessage());
            }
        }

        $campanas = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'campania')
            ->orderByDesc('ventas')->get();

        $demoEdad = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'demo_age')
            ->orderBy('objeto_nombre')->get();
        $demoGenero = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'demo_gender')
            ->orderByDesc('inversion')->get();
        $demoRegion = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'demo_region')
            ->orderByDesc('inversion')->get();

        // === Mes anterior + análisis ===
        $previo = null; $comparativas = []; $bullets = []; $recomendaciones = [];
        if (!$usaRango && preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            try {
                $periodoPrev = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->startOfMonth()->subMonthNoOverflow()->format('Y-m');
                $previo = \App\Models\MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodoPrev)->where('nivel', 'cuenta')->first();
            } catch (\Throwable $e) {}
            if ($resumen) {
                $comparativas = \App\Services\ReporteAnalyzer::comparativas($resumen, $previo);
                $bullets = \App\Services\ReporteAnalyzer::resumenEjecutivo($resumen, $previo, $campanas);
                $recomendaciones = \App\Services\ReporteAnalyzer::recomendaciones($resumen, $previo, $campanas, $demoRegion);
            }
        }

        return view('agencia.reportes.meta-publico', compact(
            'cuenta', 'periodo', 'resumen', 'campanas',
            'desde', 'hasta', 'usaRango',
            'demoEdad', 'demoGenero', 'demoRegion',
            'previo', 'comparativas', 'bullets', 'recomendaciones'
        ));
    }

    /** Sincroniza una cuenta para el mes actual (o el indicado). */
    public function sincronizar(Request $request, MetaAdAccount $cuenta)
    {
        $periodo = $request->input('periodo', now()->format('Y-m'));
        $svc = new MetaAdsService();
        $resumen = $svc->syncAccount($cuenta, $periodo);

        $msg = $svc->hasToken()
            ? "Sincronización con Meta completada para {$cuenta->nombre_cuenta}."
            : "Sincronización en modo DEMO (sin token aún). Datos de ejemplo generados.";

        return redirect()->route('agencia.reportes.conexion')->with('success', $msg);
    }
}
