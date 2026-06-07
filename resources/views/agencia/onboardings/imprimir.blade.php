<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $proyecto->titulo }} · Onboarding BigStudio</title>
    <style>
        @page { size: A4; margin: 1.5cm; }
        @media print {
            .no-print { display: none !important; }
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .header {
            background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .header .label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; opacity: 0.9; }
        .header h1 { font-size: 22px; margin: 5px 0 0; font-weight: 900; letter-spacing: -0.02em; }
        .header .sub { font-size: 13px; opacity: 0.9; margin-top: 5px; }
        .meta {
            background: #FFF7ED;
            border-left: 4px solid #FF8100;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .meta b { color: #7c2d12; }
        .seccion {
            margin-bottom: 24px;
            break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .seccion-header {
            background: linear-gradient(90deg, #FFF7ED, #FFFBEB);
            padding: 10px 15px;
            border-bottom: 1px solid #FED7AA;
        }
        .seccion-header .num {
            font-size: 10px;
            font-weight: bold;
            color: #FF8100;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .seccion-header .titulo {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-top: 2px;
        }
        .campo {
            padding: 8px 15px;
            border-top: 1px dashed #f3f4f6;
            display: flex;
            gap: 10px;
        }
        .campo:first-child { border-top: none; }
        .campo .label {
            flex: 0 0 35%;
            font-weight: 600;
            color: #6b7280;
            font-size: 11px;
        }
        .campo .valor {
            flex: 1;
            color: #1f2937;
            font-size: 11px;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .campo .vacio {
            color: #d1d5db;
            font-style: italic;
        }
        .campo .archivo {
            display: inline-block;
            background: #FFF7ED;
            border: 1px solid #FED7AA;
            padding: 2px 8px;
            border-radius: 4px;
            margin-right: 4px;
            margin-bottom: 4px;
            font-size: 10px;
            color: #7c2d12;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }
        .actions {
            margin-bottom: 20px;
            display: flex;
            gap: 8px;
        }
        .actions button, .actions a {
            background: #FF8100;
            color: white;
            border: 0;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }
        .actions .secondary { background: #6b7280; }
    </style>
</head>
<body>

    <div class="actions no-print">
        <button onclick="window.print()">🖨 Imprimir / Guardar como PDF</button>
        <a href="{{ route('agencia.onboardings.show', $proyecto) }}" class="secondary">← Volver al admin</a>
    </div>

    <div class="header">
        <div class="label">BigStudio · Onboarding</div>
        <h1>{{ $proyecto->titulo }}</h1>
        <div class="sub">{{ $proyecto->cliente->nombre ?? '—' }} · {{ $proyecto->plantilla->nombre ?? '—' }}</div>
    </div>

    <div class="meta">
        <b>Estado:</b> {{ str_replace('_', ' ', $proyecto->estado) }} ·
        <b>Avance:</b> {{ $proyecto->porcentaje_avance }}% ·
        <b>Creado:</b> {{ $proyecto->created_at->format('d/m/Y') }}
        @if($proyecto->fecha_completado)
            · <b>Completado:</b> {{ $proyecto->fecha_completado->format('d/m/Y') }}
        @endif
        @if($proyecto->email_cliente)
            <br><b>Email cliente:</b> {{ $proyecto->email_cliente }}
        @endif
    </div>

    @php
        $secciones = $proyecto->plantilla->secciones ?? [];
        $respuestasMap = $proyecto->respuestas->keyBy(fn($r) => $r->seccion_key . '|' . $r->campo_key);
        $archivosMap = $proyecto->archivos->groupBy(fn($a) => $a->seccion_key . '|' . $a->campo_key);
    @endphp

    @foreach($secciones as $idx => $seccion)
        <div class="seccion">
            <div class="seccion-header">
                <div class="num">Sección {{ $idx + 1 }} de {{ count($secciones) }}</div>
                <div class="titulo">{{ $seccion['titulo'] }}</div>
            </div>

            @foreach(($seccion['campos'] ?? []) as $campo)
                @php
                    $key = $seccion['key'] . '|' . $campo['key'];
                    $resp = $respuestasMap->get($key);
                    $archivos = $archivosMap->get($key, collect());
                    $valor = $resp?->valor;
                    $esArchivo = in_array($campo['tipo'] ?? 'texto', ['archivo_unico','archivo_multiple']);
                @endphp
                <div class="campo">
                    <div class="label">{{ $campo['label'] }}{{ ($campo['requerido'] ?? false) ? ' *' : '' }}</div>
                    <div class="valor">
                        @if($esArchivo)
                            @if($archivos->isEmpty())
                                <span class="vacio">— sin archivos —</span>
                            @else
                                @foreach($archivos as $a)
                                    <span class="archivo">📄 {{ $a->nombre_original }} ({{ $a->tamanoLegible() }})</span>
                                @endforeach
                            @endif
                        @elseif(empty($valor) || $valor === 'archivos_subidos')
                            <span class="vacio">— sin respuesta —</span>
                        @elseif(($campo['tipo'] ?? '') === 'confirmacion')
                            ✓ Confirmado
                        @else
                            {{ $valor }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="footer">
        BigStudio · hola@bigstudio.cl · www.bigstudio.cl<br>
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
