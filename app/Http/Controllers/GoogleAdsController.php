<?php

namespace App\Http\Controllers;

use App\Models\GoogleAdAccount;
use App\Models\AgenciaCliente;
use App\Services\GoogleAdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAdsController extends Controller
{
    public function reporte(Request $request)
    {
        $cuentas = GoogleAdAccount::with('cliente')->orderBy('nombre_cuenta')->get();
        $cuentaId = $request->input('cuenta_id', optional($cuentas->first())->id);
        $cuenta = $cuentas->firstWhere('id', $cuentaId);

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $usaRango = $desde && $hasta;
        $periodo = $usaRango ? ($desde . '_' . $hasta) : $request->input('periodo', now()->format('Y-m'));

        $resumen = null; $campanas = collect();
        $demoEdad = collect(); $demoGenero = collect(); $demoRegion = collect();
        $autoSync = false;

        if ($cuenta) {
            $resumen = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
            if (!$resumen) {
                try {
                    $svc = new GoogleAdsService();
                    if ($usaRango) { $svc->syncAccountRango($cuenta, $desde, $hasta); }
                    else { $svc->syncAccount($cuenta, $periodo); }
                    $autoSync = true;
                    $resumen = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                        ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
                } catch (\Throwable $e) {
                    Log::error('Google auto-sync: ' . $e->getMessage());
                }
            }
            $campanas = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'campania')->orderByDesc('ventas')->get();
            $demoEdad = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'demo_age')->orderBy('objeto_nombre')->get();
            $demoGenero = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'demo_gender')->orderByDesc('inversion')->get();
            $demoRegion = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                ->where('periodo', $periodo)->where('nivel', 'demo_region')->orderByDesc('inversion')->get();
        }

        // === Mes anterior + análisis ===
        $previo = null; $comparativas = []; $bullets = []; $recomendaciones = [];
        if ($cuenta && !$usaRango && preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            try {
                $periodoPrev = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->startOfMonth()->subMonthNoOverflow()->format('Y-m');
                $previo = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodoPrev)->where('nivel', 'cuenta')->first();
            } catch (\Throwable $e) {}
            if ($resumen) {
                $comparativas = \App\Services\ReporteAnalyzer::comparativas($resumen, $previo);
                $bullets = \App\Services\ReporteAnalyzer::resumenEjecutivo($resumen, $previo, $campanas);
                $recomendaciones = \App\Services\ReporteAnalyzer::recomendaciones($resumen, $previo, $campanas, $demoRegion);
            }
        }

        return view('agencia.reportes.google', compact(
            'cuentas','cuenta','periodo','resumen','campanas','desde','hasta','usaRango','autoSync',
            'demoEdad','demoGenero','demoRegion',
            'previo','comparativas','bullets','recomendaciones'
        ));
    }

    public function index()
    {
        $svc = new GoogleAdsService();
        $cuentas = GoogleAdAccount::with('cliente')->orderBy('nombre_cuenta')->get();
        $clientes = AgenciaCliente::where('estado', 'activo')->orderBy('nombre')->get();
        $canStartOAuth = $svc->canStartOAuth();
        $hasCredentials = $svc->hasCredentials();
        $hasOAuthToken = $svc->hasOAuthToken();
        $hasToken = $svc->hasToken();
        $accessibleCustomers = $hasToken ? $svc->listAccessibleCustomers() : [];

        return view('agencia.reportes.google-conexion', compact('cuentas','clientes','canStartOAuth','hasCredentials','hasOAuthToken','hasToken','accessibleCustomers'));
    }

    public function guardarCredenciales(Request $request)
    {
        $request->validate([
            'google_client_id' => ['required','string','min:10'],
            'google_client_secret' => ['required','string','min:5'],
            'google_developer_token' => ['nullable','string','min:5'],
            'google_login_customer_id' => ['nullable','string','max:30'],
        ]);
        foreach (['google_client_id','google_client_secret','google_developer_token','google_login_customer_id'] as $k) {
            $val = trim((string) $request->input($k));
            DB::table('settings')->updateOrInsert(['key' => $k], ['value' => $val ?: null, 'updated_at' => now()]);
        }
        return redirect()->route('agencia.reportes.google.conexion')
            ->with('success', 'Credenciales guardadas. Ya puedes autorizar OAuth con Google.');
    }

    /** Inicia el flujo OAuth: redirige al consentimiento de Google. */
    public function authStart(Request $request)
    {
        $svc = new GoogleAdsService();
        if (!$svc->canStartOAuth()) {
            return redirect()->route('agencia.reportes.google.conexion')
                ->with('error', 'Primero guarda Client ID y Client Secret.');
        }
        $redirect = route('agencia.reportes.google.callback');
        return redirect()->away($svc->buildAuthUrl($redirect, csrf_token()));
    }

    /** Callback OAuth: intercambia code por tokens. */
    public function authCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('agencia.reportes.google.conexion')
                ->with('error', 'Google rechazó la autorización: ' . $request->input('error'));
        }
        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('agencia.reportes.google.conexion')
                ->with('error', 'Google no devolvió un código de autorización.');
        }
        try {
            $svc = new GoogleAdsService();
            $svc->exchangeCodeForTokens($code, route('agencia.reportes.google.callback'));
            return redirect()->route('agencia.reportes.google.conexion')
                ->with('success', 'Conexión Google Ads autorizada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Google authCallback: ' . $e->getMessage());
            return redirect()->route('agencia.reportes.google.conexion')
                ->with('error', 'Error al intercambiar el código: ' . $e->getMessage());
        }
    }

    public function vincularCuenta(Request $request)
    {
        $request->validate([
            'agencia_cliente_id' => ['nullable','exists:agencia_clientes,id'],
            'nombre_cuenta' => ['required','string','max:255'],
            'customer_id' => ['required','string','max:50'],
            'login_customer_id' => ['nullable','string','max:50'],
            'moneda' => ['nullable','string','max:10'],
        ]);
        $customerId = preg_replace('/[^0-9]/', '', $request->customer_id);
        $loginCustomerId = $request->login_customer_id ? preg_replace('/[^0-9]/', '', $request->login_customer_id) : null;

        GoogleAdAccount::updateOrCreate(
            ['customer_id' => $customerId],
            [
                'agencia_cliente_id' => $request->agencia_cliente_id,
                'nombre_cuenta' => $request->nombre_cuenta,
                'login_customer_id' => $loginCustomerId,
                'moneda' => $request->moneda ?: 'CLP',
                'estado' => 'activa',
            ]
        );
        return redirect()->route('agencia.reportes.google.conexion')
            ->with('success', 'Cuenta Google Ads vinculada correctamente.');
    }

    public function eliminarCuenta(GoogleAdAccount $cuenta)
    {
        $cuenta->insights()->delete();
        $cuenta->delete();
        return redirect()->route('agencia.reportes.google.conexion')
            ->with('success', 'Cuenta desvinculada.');
    }

    public function guardarEnvio(Request $request, GoogleAdAccount $cuenta)
    {
        $request->validate([
            'reporte_emails' => ['nullable','string'],
            'reporte_dias' => ['nullable','string','max:80'],
        ]);
        $raw = preg_split('/[\s,;]+/', (string) $request->reporte_emails, -1, PREG_SPLIT_NO_EMPTY);
        $emails = [];
        foreach ($raw as $e) {
            $e = strtolower(trim($e));
            if (filter_var($e, FILTER_VALIDATE_EMAIL) && !in_array($e, $emails, true)) $emails[] = $e;
        }
        if (empty($cuenta->reporte_token)) $cuenta->reporte_token = Str::random(40);
        $cuenta->reporte_emails = $emails;
        $cuenta->reporte_dias = trim((string) $request->reporte_dias) ?: null;
        $cuenta->reporte_activo = $request->boolean('reporte_activo');
        $cuenta->save();

        $msg = 'Configuración de envío guardada para ' . $cuenta->nombre_cuenta . '.';
        if ($request->filled('periodo') || str_contains((string) $request->headers->get('referer'), '/reportes/google')) {
            return redirect()->back()->with('success', $msg);
        }
        return redirect()->route('agencia.reportes.google.conexion')->with('success', $msg);
    }

    public function enviarAhora(Request $request, GoogleAdAccount $cuenta)
    {
        $emailsRequest = trim((string) $request->input('emails', ''));
        $destinos = [];
        if ($emailsRequest !== '') {
            $raw = preg_split('/[\s,;]+/', $emailsRequest, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($raw as $e) {
                $e = strtolower(trim($e));
                if (filter_var($e, FILTER_VALIDATE_EMAIL) && !in_array($e, $destinos, true)) $destinos[] = $e;
            }
        }
        if (empty($destinos)) $destinos = is_array($cuenta->reporte_emails) ? $cuenta->reporte_emails : [];
        if (empty($destinos) && $cuenta->cliente && filter_var($cuenta->cliente->email, FILTER_VALIDATE_EMAIL)) {
            $destinos = [$cuenta->cliente->email];
        }
        if (empty($destinos)) {
            return back()->with('error', 'No hay correo destinatario. Indica uno o configura el correo del cliente.');
        }

        if ($request->boolean('guardar_emails')) {
            if (empty($cuenta->reporte_token)) $cuenta->reporte_token = Str::random(40);
            $cuenta->reporte_emails = $destinos;
            $cuenta->save();
        }
        $periodo = $request->input('periodo');
        try {
            (new \App\Services\GoogleReporteSender())->enviar($cuenta, $periodo, $destinos);
            return back()->with('success', 'Reporte enviado a: ' . implode(', ', $destinos));
        } catch (\Throwable $e) {
            Log::error('Google enviarAhora: ' . $e->getMessage());
            return back()->with('error', 'Error al enviar: ' . $e->getMessage());
        }
    }

    public function sincronizar(Request $request, GoogleAdAccount $cuenta)
    {
        $periodo = $request->input('periodo', now()->format('Y-m'));
        $svc = new GoogleAdsService();
        $svc->syncAccount($cuenta, $periodo);
        $msg = $svc->hasToken()
            ? "Sincronización con Google Ads completada para {$cuenta->nombre_cuenta}."
            : "Sincronización en modo DEMO (sin token aún). Datos de ejemplo generados.";
        return redirect()->back()->with('success', $msg);
    }

    /** Reporte público vía token. */
    public function reportePublico(Request $request, string $token)
    {
        $cuenta = GoogleAdAccount::with('cliente')->where('reporte_token', $token)->first();
        if (!$cuenta) abort(404);

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $usaRango = $desde && $hasta;
        $periodo = $usaRango ? ($desde . '_' . $hasta) : $request->input('periodo', now()->subMonthNoOverflow()->format('Y-m'));

        $resumen = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
        if (!$resumen) {
            try {
                $svc = new GoogleAdsService();
                if ($usaRango) $svc->syncAccountRango($cuenta, $desde, $hasta);
                else $svc->syncAccount($cuenta, $periodo);
                $resumen = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
            } catch (\Throwable $e) { Log::error('Google reportePublico: ' . $e->getMessage()); }
        }
        $campanas = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'campania')->orderByDesc('ventas')->get();
        $demoEdad = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'demo_age')->orderBy('objeto_nombre')->get();
        $demoGenero = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'demo_gender')->orderByDesc('inversion')->get();
        $demoRegion = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'demo_region')->orderByDesc('inversion')->get();

        $previo = null; $comparativas = []; $bullets = []; $recomendaciones = [];
        if (!$usaRango && preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            try {
                $periodoPrev = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->startOfMonth()->subMonthNoOverflow()->format('Y-m');
                $previo = \App\Models\GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodoPrev)->where('nivel', 'cuenta')->first();
            } catch (\Throwable $e) {}
            if ($resumen) {
                $comparativas = \App\Services\ReporteAnalyzer::comparativas($resumen, $previo);
                $bullets = \App\Services\ReporteAnalyzer::resumenEjecutivo($resumen, $previo, $campanas);
                $recomendaciones = \App\Services\ReporteAnalyzer::recomendaciones($resumen, $previo, $campanas, $demoRegion);
            }
        }

        return view('agencia.reportes.google-publico', compact(
            'cuenta','periodo','resumen','campanas','desde','hasta','usaRango','demoEdad','demoGenero','demoRegion',
            'previo','comparativas','bullets','recomendaciones'
        ));
    }
}
