<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seccion['titulo'] ?? 'Onboarding' }} · BigStudio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif; }
        .bs-display { font-weight: 900; letter-spacing: -0.02em; }
        .bs-grad { background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%); }
        .bs-grad-text { background: linear-gradient(135deg, #FF9C00 0%, #FF8100 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #FF8100; box-shadow: 0 0 0 3px rgba(255, 129, 0, 0.15); }
        .step-dot.active { background: #FF8100; color: white; }
        .step-dot.done { background: #10b981; color: white; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 pb-32">

    {{-- Header con barra de progreso --}}
    <header class="bs-grad text-white sticky top-0 z-30 shadow-md">
        <div class="max-w-3xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs uppercase tracking-widest opacity-90">BigStudio · Onboarding</div>
                <div class="text-sm font-bold" id="bsProgreso">{{ $proyecto->porcentaje_avance }}% completo</div>
            </div>
            <div class="w-full bg-white/30 rounded-full h-2 overflow-hidden">
                <div id="bsBarraAvance" class="h-2 bg-white rounded-full transition-all duration-300" style="width: {{ $proyecto->porcentaje_avance }}%"></div>
            </div>
            <div class="flex justify-between mt-3 gap-1 overflow-x-auto">
                @foreach($secciones as $i => $s)
                    @php
                        $estado = $i < $indice ? 'done' : ($i === $indice ? 'active' : 'pending');
                    @endphp
                    <a href="{{ route('onboarding.wizard', ['token' => $proyecto->token, 'indice' => $i]) }}"
                       class="step-dot {{ $estado }} flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $estado === 'pending' ? 'bg-white/30 text-white/80' : '' }} hover:opacity-90"
                       title="{{ $s['titulo'] }}">
                        {{ $i + 1 }}
                    </a>
                @endforeach
            </div>
        </div>
    </header>

    {{-- Contenido principal --}}
    <main class="max-w-3xl mx-auto px-4 py-8">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <div class="text-xs uppercase tracking-widest text-orange-600 font-semibold mb-1">Sección {{ $indice + 1 }} de {{ $totalSecciones }}</div>
                <h1 class="bs-display text-2xl text-gray-800">{{ $seccion['titulo'] }}</h1>
                @if(!empty($seccion['subtitulo']))
                    <p class="text-gray-600 mt-1">{{ $seccion['subtitulo'] }}</p>
                @endif
            </div>

            @if(session('success'))
                <div class="px-6 py-2 bg-green-50 text-green-800 text-sm border-b border-green-100">✓ {{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('onboarding.wizard.guardar', ['token' => $proyecto->token, 'indice' => $indice]) }}" class="p-6 space-y-6" id="bsFormWizard">
                @csrf

                @foreach(($seccion['campos'] ?? []) as $campo)
                    @php
                        $valorActual = $respuestas[$campo['key']] ?? '';
                        $nombre = 'campos.' . $campo['key'];
                        $idCampo = 'campo_' . $campo['key'];
                        $requerido = ($campo['requerido'] ?? false);
                        $tipo = $campo['tipo'] ?? 'texto';
                    @endphp
                    <div class="bs-campo" data-campo-key="{{ $campo['key'] }}">
                        <label for="{{ $idCampo }}" class="block text-sm font-semibold text-gray-800 mb-1">
                            {{ $campo['label'] }}
                            @if($requerido) <span class="text-orange-500">*</span> @endif
                        </label>

                        @switch($tipo)
                            @case('texto_corto')
                                <input type="text" id="{{ $idCampo }}" name="{{ $nombre }}" value="{{ $valorActual }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                       @if($requerido) required @endif>
                                @break

                            @case('texto')
                                <input type="text" id="{{ $idCampo }}" name="{{ $nombre }}" value="{{ $valorActual }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                       @if($requerido) required @endif>
                                @break

                            @case('texto_largo')
                                <textarea id="{{ $idCampo }}" name="{{ $nombre }}" rows="5"
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                          @if($requerido) required @endif>{{ $valorActual }}</textarea>
                                @break

                            @case('select')
                                <select id="{{ $idCampo }}" name="{{ $nombre }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white"
                                        @if($requerido) required @endif>
                                    <option value="">— Selecciona —</option>
                                    @foreach(($campo['opciones'] ?? []) as $opcion)
                                        <option value="{{ $opcion }}" {{ $valorActual === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                                    @endforeach
                                </select>
                                @break

                            @case('confirmacion')
                                <label class="inline-flex items-start gap-2 cursor-pointer bg-orange-50 border border-orange-200 p-4 rounded-lg w-full">
                                    <input type="checkbox" id="{{ $idCampo }}" name="{{ $nombre }}" value="1"
                                           class="mt-1 rounded text-orange-500"
                                           {{ $valorActual ? 'checked' : '' }}
                                           @if($requerido) required @endif>
                                    <span class="text-sm text-gray-700">{{ $campo['label'] }}</span>
                                </label>
                                @break

                            @case('archivo_unico')
                            @case('archivo_multiple')
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                                    📎 Subida de archivos disponible en el siguiente sprint. Por ahora, escribe en
                                    <strong>{{ $campo['key'] === 'logo_principal' || $campo['key'] === 'isotipo' || $campo['key'] === 'manual_marca' ? 'Quiénes somos' : 'observaciones' }}</strong>
                                    un link de Drive/Dropbox con el material y lo procesamos manualmente.
                                </div>
                                <input type="hidden" name="{{ $nombre }}" value="pendiente_sprint_3">
                                @break

                            @default
                                <input type="text" id="{{ $idCampo }}" name="{{ $nombre }}" value="{{ $valorActual }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @endswitch
                    </div>
                @endforeach

                @if($esUltima)
                    <div class="bg-gradient-to-br from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-5">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="material_listo" value="1" class="mt-1 rounded text-orange-500 w-5 h-5">
                            <div>
                                <div class="font-bold text-gray-800 mb-1">✅ Material listo — arrancar el reloj del proyecto</div>
                                <div class="text-sm text-gray-700">Al marcar esta casilla, confirmo que el material está completo y BigStudio puede iniciar las 15 a 20 días hábiles de entrega. Recibiremos un aviso por email para empezar.</div>
                            </div>
                        </label>
                    </div>
                @endif

                {{-- Navegación --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    @if(!$esPrimera)
                        <button type="submit" name="accion" value="anterior"
                                class="px-5 py-2.5 text-gray-600 hover:text-gray-900 font-semibold">← Anterior</button>
                    @else
                        <span></span>
                    @endif

                    <button type="submit" name="accion" value="siguiente"
                            class="bs-grad text-white font-bold px-8 py-3 rounded-xl shadow-lg hover:shadow-xl transition">
                        @if($esUltima) Guardar y finalizar @else Siguiente → @endif
                    </button>
                </div>
            </form>
        </div>

        {{-- Indicador de autosave --}}
        <div id="bsAutosaveIndicator" class="fixed bottom-6 right-6 bg-gray-800 text-white text-sm px-4 py-2 rounded-lg shadow-lg opacity-0 transition-opacity duration-300">
            <span id="bsAutosaveText">Guardado ✓</span>
        </div>

    </main>

    <footer class="text-center text-xs text-gray-400 py-4">
        BigStudio · ¿Dudas? <a href="mailto:hola@bigstudio.cl" class="text-orange-600 hover:underline">hola@bigstudio.cl</a>
    </footer>

    <script>
        // Autosave por campo al hacer blur (excepto archivos)
        (function () {
            const token = '{{ $proyecto->token }}';
            const indice = {{ $indice }};
            const url = `/o/${token}/w/${indice}/autoguardar`;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const indicador = document.getElementById('bsAutosaveIndicator');
            const indicadorText = document.getElementById('bsAutosaveText');
            const barra = document.getElementById('bsBarraAvance');
            const labelProgreso = document.getElementById('bsProgreso');

            function mostrarIndicador(texto, color = 'bg-gray-800') {
                indicadorText.textContent = texto;
                indicador.className = `fixed bottom-6 right-6 ${color} text-white text-sm px-4 py-2 rounded-lg shadow-lg transition-opacity duration-300`;
                indicador.style.opacity = '1';
                clearTimeout(window.bsHideTimer);
                window.bsHideTimer = setTimeout(() => { indicador.style.opacity = '0'; }, 2000);
            }

            async function guardarCampo(campoKey, valor) {
                try {
                    const r = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ campo_key: campoKey, valor })
                    });
                    const json = await r.json();
                    if (json.ok) {
                        mostrarIndicador('Guardado ✓', 'bg-green-600');
                        if (typeof json.porcentaje !== 'undefined') {
                            barra.style.width = json.porcentaje + '%';
                            labelProgreso.textContent = json.porcentaje + '% completo';
                        }
                    } else {
                        mostrarIndicador('Error al guardar', 'bg-red-600');
                    }
                } catch (e) {
                    mostrarIndicador('Sin conexión', 'bg-red-600');
                }
            }

            document.querySelectorAll('.bs-campo').forEach(div => {
                const campoKey = div.dataset.campoKey;
                const input = div.querySelector('input[type="text"], textarea, select, input[type="checkbox"]');
                if (!input || input.type === 'hidden') return;

                input.addEventListener('blur', () => {
                    const valor = input.type === 'checkbox' ? (input.checked ? '1' : '') : input.value;
                    if (campoKey) guardarCampo(campoKey, valor);
                });
            });
        })();
    </script>

</body>
</html>
