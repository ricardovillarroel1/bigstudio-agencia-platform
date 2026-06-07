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
                <div class="px-6 py-2 bg-green-50 text-green-800 text-sm border-b border-green-100 font-semibold">✓ {{ session('success') }}</div>
            @endif

            {{-- Banner informativo de auto-guardado --}}
            <div class="px-6 py-3 bg-orange-50 border-b border-orange-100 flex items-start gap-3">
                <div class="text-orange-500 text-lg flex-shrink-0">💾</div>
                <div class="text-xs text-gray-700">
                    <strong class="text-orange-700">Tus respuestas se guardan automáticamente.</strong>
                    Cada vez que pasás al siguiente campo, todo queda registrado.
                    Podés cerrar la página y volver más tarde — el progreso se mantiene.
                </div>
            </div>

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

                            @case('productos_constructor')
                                @php
                                    $productosCargados = \App\Models\AgenciaOnboardingProducto::with('imagen')
                                        ->where('proyecto_id', $proyecto->id)
                                        ->where('seccion_key', $seccion['key'])
                                        ->where('campo_key', $campo['key'])
                                        ->orderBy('orden')->orderBy('id')->get();
                                    $rr = $proyecto->respuestas->keyBy(fn($r) => $r->campo_key);
                                    $origenActual = $rr->get($campo['key'].'__origen')?->valor ?: 'manual';
                                    $syncEmail = $rr->get($campo['key'].'__sync_email')?->valor ?? '';
                                    $tienePass = !empty($rr->get($campo['key'].'__sync_password')?->valor);
                                @endphp

                                {{-- Selector de origen del catalogo --}}
                                <div class="bs-origen-selector mb-4" data-token="{{ $proyecto->token }}" data-indice="{{ $indice }}" data-campo-key="{{ $campo['key'] }}">
                                    <p class="text-sm text-gray-700 mb-2 font-semibold">¿Dónde tienes tus productos?</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        @foreach(['manual' => ['Cargarlos aquí', 'Los subo uno por uno'], 'bsale' => ['Bsale', 'Comparto mis accesos'], 'lioren' => ['Lioren', 'Comparto mis accesos']] as $op => $txt)
                                            <button type="button" class="bs-origen-opt border-2 rounded-xl p-3 text-left transition {{ $origenActual === $op ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }}" data-origen="{{ $op }}">
                                                <div class="font-bold text-gray-800">{{ $txt[0] }}</div>
                                                <div class="text-xs text-gray-500">{{ $txt[1] }}</div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Form de credenciales (bsale / lioren) --}}
                                <div class="bs-sync-form mb-4 {{ in_array($origenActual, ['bsale','lioren']) ? '' : 'hidden' }}"
                                     data-token="{{ $proyecto->token }}" data-indice="{{ $indice }}" data-campo-key="{{ $campo['key'] }}">
                                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                        <p class="text-sm text-blue-900 mb-3">
                                            🔐 Comparte los accesos de tu cuenta <strong class="bs-sync-nombre">{{ ucfirst($origenActual) }}</strong> para que sincronicemos tus productos. Tus credenciales se guardan <strong>encriptadas</strong> y solo las usa el equipo de BigStudio.
                                        </p>
                                        <div class="space-y-2">
                                            <input type="email" class="bs-sync-email w-full border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Correo de acceso" value="{{ $syncEmail }}">
                                            <input type="password" class="bs-sync-password w-full border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="{{ $tienePass ? '•••••••• (guardada, escribe para cambiar)' : 'Contraseña' }}">
                                            <button type="button" class="bs-sync-guardar bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded-lg text-sm w-full">Guardar accesos</button>
                                            <p class="bs-sync-msg text-xs text-center"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bs-productos-app {{ $origenActual === 'manual' ? '' : 'hidden' }}"
                                     data-seccion-key="{{ $seccion['key'] }}"
                                     data-campo-key="{{ $campo['key'] }}"
                                     data-token="{{ $proyecto->token }}" data-indice="{{ $indice }}">

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-3">
                                        <div class="text-sm text-gray-600 flex-shrink-0">
                                            <span class="bs-productos-count font-bold text-orange-600">{{ $productosCargados->count() }}</span>
                                            producto(s)
                                        </div>
                                        <input type="text" class="bs-productos-search flex-1 border-gray-300 rounded-lg px-3 py-2 text-sm {{ $productosCargados->count() < 4 ? 'hidden' : '' }}"
                                               placeholder="🔍 Buscar producto por nombre...">
                                        <button type="button" class="bs-productos-add bg-orange-500 hover:bg-orange-600 text-white font-bold px-4 py-2 rounded-lg text-sm flex-shrink-0">
                                            + Agregar producto
                                        </button>
                                    </div>

                                    {{-- Lista compacta tipo filas (scroll interno con 6+ productos) --}}
                                    <div class="bs-productos-list border border-gray-200 rounded-xl divide-y divide-gray-100 overflow-y-auto" @if($productosCargados->count() > 10) style="max-height: 700px;" @endif>
                                        @foreach($productosCargados as $p)
                                            @php $min = $p->precioMin(); $max = $p->precioMax(); @endphp
                                            <div class="bs-producto-row flex items-center gap-3 p-2.5 hover:bg-orange-50/40 transition" data-producto-id="{{ $p->id }}" data-nombre="{{ strtolower($p->titulo) }}">
                                                @if($p->imagen_archivo_id)
                                                    <img src="{{ route('onboarding.archivo.descargar', ['token' => $proyecto->token, 'archivo' => $p->imagen_archivo_id]) }}"
                                                         alt="" class="w-12 h-12 object-cover rounded-lg flex-shrink-0 bg-gray-100">
                                                @else
                                                    <div class="w-12 h-12 bg-gray-100 flex items-center justify-center text-gray-300 text-xl rounded-lg flex-shrink-0">📦</div>
                                                @endif

                                                <div class="flex-1 min-w-0">
                                                    <div class="font-semibold text-gray-800 truncate">{{ $p->titulo }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $p->cantidadVariantes() }} variante(s) · stock {{ $p->stockTotal() }}
                                                        @if($min !== null)
                                                            · <span class="text-orange-600 font-semibold">${{ number_format($min, 0, ',', '.') }}@if($max !== null && $max != $min)–${{ number_format($max, 0, ',', '.') }}@endif</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-1 flex-shrink-0">
                                                    <button type="button" class="bs-producto-edit text-orange-600 hover:bg-orange-100 text-sm font-semibold px-3 py-1.5 rounded-lg">Editar</button>
                                                    <button type="button" class="bs-producto-dup text-gray-500 hover:bg-gray-100 hover:text-gray-700 text-sm font-semibold px-2 py-1.5 rounded-lg" title="Duplicar">⧉</button>
                                                    <button type="button" class="bs-producto-delete text-red-400 hover:bg-red-50 hover:text-red-600 text-sm font-semibold px-2 py-1.5 rounded-lg" title="Eliminar">×</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="bs-productos-empty text-center py-10 text-gray-400 border-2 border-dashed border-gray-200 rounded-xl mt-3 {{ $productosCargados->count() ? 'hidden' : '' }}">
                                        <div class="text-5xl mb-2">📦</div>
                                        <div class="text-sm">Aún no agregaste productos.</div>
                                        <div class="text-xs mt-1">Click en "+ Agregar producto" para empezar.</div>
                                    </div>

                                    <div class="bs-productos-noresults text-center py-6 text-gray-400 text-sm hidden">
                                        No se encontraron productos con ese nombre.
                                    </div>
                                </div>
                                @break


                            @case('archivo_unico')
                            @case('archivo_multiple')
                                @php
                                    $multiple = $tipo === 'archivo_multiple' ? 'multiple' : '';
                                    $archivosActuales = $proyecto->archivos()
                                        ->where('seccion_key', $seccion['key'])
                                        ->where('campo_key', $campo['key'])
                                        ->get();
                                @endphp
                                <div class="bs-uploader" data-campo-key="{{ $campo['key'] }}" data-multiple="{{ $multiple ? '1' : '0' }}">
                                    <label class="block border-2 border-dashed border-orange-300 rounded-xl p-6 text-center cursor-pointer hover:bg-orange-50 transition">
                                        <input type="file" {{ $multiple }} class="hidden bs-uploader-input"
                                               accept="image/*,application/pdf,.ai,.eps,.svg,.zip,.rar,.psd,.xlsx,.xls,.csv,.docx,.doc,.ttf,.otf">
                                        <div class="text-orange-500 text-3xl mb-2">📎</div>
                                        <div class="font-semibold text-gray-700">Arrastra archivos aquí o haz clic para seleccionar</div>
                                        <div class="text-xs text-gray-500 mt-1">Hasta 50 MB por archivo · imágenes, PDF, vectoriales, planillas</div>
                                    </label>
                                    <ul class="bs-uploader-list mt-3 space-y-2">
                                        @foreach($archivosActuales as $a)
                                            <li class="bs-uploader-item flex items-center justify-between bg-white border border-gray-200 rounded-lg px-3 py-2" data-archivo-id="{{ $a->id }}">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="text-orange-500 flex-shrink-0">📄</span>
                                                    <a href="{{ route('onboarding.archivo.descargar', ['token' => $proyecto->token, 'archivo' => $a->id]) }}"
                                                       target="_blank"
                                                       class="text-sm text-gray-800 hover:text-orange-600 truncate">
                                                        {{ $a->nombre_original }}
                                                    </a>
                                                    <span class="text-xs text-gray-500 flex-shrink-0">{{ $a->tamanoLegible() }}</span>
                                                </div>
                                                <button type="button" class="bs-uploader-delete text-red-500 hover:text-red-700 text-sm font-semibold ml-2">Eliminar</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
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
                <div class="pt-4 border-t border-gray-100 space-y-3">
                    {{-- Boton Guardar borrador (queda en misma seccion, sin avanzar) --}}
                    <button type="submit" name="accion" value="guardar" formnovalidate
                            class="w-full bg-white border-2 border-orange-400 text-orange-600 hover:bg-orange-50 font-bold px-6 py-3 rounded-xl transition flex items-center justify-center gap-2">
                        💾 Guardar borrador y continuar después
                    </button>

                    <div class="flex items-center justify-between">
                        @if(!$esPrimera)
                            <button type="submit" name="accion" value="anterior" formnovalidate
                                    class="px-5 py-2.5 text-gray-600 hover:text-gray-900 font-semibold">← Anterior</button>
                        @else
                            <span></span>
                        @endif

                        <button type="submit" name="accion" value="siguiente"
                                class="bs-grad text-white font-bold px-8 py-3 rounded-xl shadow-lg hover:shadow-xl transition">
                            @if($esUltima) Guardar y finalizar @else Siguiente → @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Indicador de autosave (mas visible) --}}
        <div id="bsAutosaveIndicator" class="fixed bottom-6 right-6 bg-green-600 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-xl opacity-0 transition-opacity duration-300 flex items-center gap-2 z-40">
            <span class="text-lg">✓</span>
            <span id="bsAutosaveText">Guardado automáticamente</span>
        </div>

    </main>

    <footer class="text-center text-xs text-gray-400 py-4">
        BigStudio · ¿Dudas? <a href="mailto:hola@bigstudio.cl" class="text-orange-600 hover:underline">hola@bigstudio.cl</a>
    </footer>

{{-- Modal Producto --}}
<div id="bsProductoModal" class="fixed inset-0 bg-black/60 z-50 hidden items-start sm:items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-xl w-full max-w-2xl my-8 shadow-2xl flex flex-col max-h-[95vh]">
        <div class="bs-grad text-white px-5 py-4 flex items-center justify-between rounded-t-xl">
            <h2 class="bs-display text-xl m-0" id="bsModalTitulo">Agregar producto</h2>
            <button type="button" class="bs-modal-close text-white/80 hover:text-white text-2xl leading-none">×</button>
        </div>

        <div class="p-5 space-y-4 overflow-y-auto flex-1">
            {{-- Imagenes (galeria) --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Imágenes del producto</label>
                <p class="text-xs text-gray-500 mb-2">Podés subir varias. La primera es la principal. JPG, PNG, WebP · max 10MB c/u.</p>
                <div id="bsModalGaleria" class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-2">
                    {{-- thumbnails dinamicos --}}
                </div>
                <label class="block border-2 border-dashed border-orange-300 rounded-lg p-4 text-center cursor-pointer hover:bg-orange-50">
                    <input type="file" class="hidden" id="bsModalImagenInput" accept="image/*" multiple>
                    <div>
                        <div class="text-orange-500 text-2xl">📷</div>
                        <div class="text-sm text-gray-600">Subir o arrastrar imágenes</div>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Título *</label>
                <input type="text" id="bsModalTituloProducto" required maxlength="255" class="w-full border-gray-300 rounded-lg px-3 py-2" placeholder="Ej: Polera negra The Band">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Descripción</label>
                <textarea id="bsModalDescripcion" rows="3" class="w-full border-gray-300 rounded-lg px-3 py-2" placeholder="Detalles, materiales, beneficios..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Marca / Vendor</label>
                    <input type="text" id="bsModalVendor" class="w-full border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tipo</label>
                    <input type="text" id="bsModalTipo" class="w-full border-gray-300 rounded-lg px-3 py-2" placeholder="Ej: Polera, Mug, Libro">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tags (separados por coma)</label>
                <input type="text" id="bsModalTags" class="w-full border-gray-300 rounded-lg px-3 py-2" placeholder="rock, vintage, unisex">
            </div>

            {{-- Opciones / Variantes --}}
            <div class="border-t border-gray-100 pt-4">
                <div class="text-sm font-bold text-gray-800 mb-2">Opciones y variantes</div>
                <p class="text-xs text-gray-500 mb-3">¿Tu producto tiene tallas, colores, sabores? Agregá las opciones y el sistema arma las combinaciones.</p>

                <div id="bsModalOpciones" class="space-y-2">
                    {{-- Las opciones se renderizan dinamicamente --}}
                </div>

                <button type="button" id="bsModalAddOpcion" class="text-orange-600 hover:text-orange-800 text-sm font-semibold mt-2">+ Agregar opción</button>

                {{-- Variantes generadas --}}
                <div class="mt-4">
                    <div class="text-xs font-semibold text-gray-600 uppercase mb-2">Variantes (precio + stock por combinación)</div>
                    <div id="bsModalVariantes" class="space-y-1 max-h-72 overflow-y-auto border border-gray-200 rounded-lg p-2">
                        {{-- Se renderiza dinamicamente --}}
                    </div>
                </div>
            </div>

            {{-- Avanzado collapsable --}}
            <details class="border-t border-gray-100 pt-4">
                <summary class="cursor-pointer text-sm font-bold text-gray-800">Avanzado: SEO + categoría</summary>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Categoría Shopify</label>
                        <input type="text" id="bsModalCategoria" class="w-full border-gray-300 rounded-lg px-3 py-2" placeholder="Ej: Apparel & Accessories > Clothing > T-Shirts">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">SEO title</label>
                        <input type="text" id="bsModalSeoTitle" maxlength="255" class="w-full border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">SEO description</label>
                        <textarea id="bsModalSeoDescription" rows="2" maxlength="500" class="w-full border-gray-300 rounded-lg px-3 py-2"></textarea>
                    </div>
                </div>
            </details>
        </div>

        <div class="border-t border-gray-100 p-4 flex justify-end gap-2 rounded-b-xl">
            <button type="button" class="bs-modal-close px-4 py-2 text-gray-600 hover:text-gray-800 font-semibold">Cancelar</button>
            <button type="button" id="bsModalGuardar" class="bs-grad text-white font-bold px-6 py-2 rounded-lg">Guardar producto</button>
        </div>
    </div>
</div>

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

            function mostrarIndicador(texto, color = 'bg-green-600') {
                indicadorText.textContent = texto;
                indicador.className = `fixed bottom-6 right-6 ${color} text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-xl transition-opacity duration-300 flex items-center gap-2 z-40`;
                indicador.innerHTML = `<span class="text-lg">✓</span><span>${texto}</span>`;
                indicador.style.opacity = '1';
                clearTimeout(window.bsHideTimer);
                window.bsHideTimer = setTimeout(() => { indicador.style.opacity = '0'; }, 4000);
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

            // ============ Drag and drop uploads ============
            document.querySelectorAll('.bs-uploader').forEach(uploader => {
                const campoKey = uploader.dataset.campoKey;
                const isMultiple = uploader.dataset.multiple === '1';
                const input = uploader.querySelector('.bs-uploader-input');
                const lista = uploader.querySelector('.bs-uploader-list');
                const dropZone = uploader.querySelector('label');

                async function subirArchivo(file) {
                    if (file.size > 52428800) {
                        mostrarIndicador('Archivo > 50 MB', 'bg-red-600');
                        return;
                    }
                    const itemTemp = document.createElement('li');
                    itemTemp.className = 'flex items-center gap-2 bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 text-sm text-orange-700';
                    itemTemp.innerHTML = '<span>⏳ Subiendo ' + file.name + '...</span>';
                    lista.appendChild(itemTemp);

                    const formData = new FormData();
                    formData.append('archivo', file);

                    try {
                        const r = await fetch(`/o/${token}/u/${indice}/${campoKey}`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            body: formData
                        });
                        const json = await r.json();
                        itemTemp.remove();
                        if (json.ok) {
                            agregarArchivoLista(json.archivo);
                            mostrarIndicador('Archivo subido ✓', 'bg-green-600');
                            if (typeof json.porcentaje !== 'undefined') {
                                barra.style.width = json.porcentaje + '%';
                                labelProgreso.textContent = json.porcentaje + '% completo';
                            }
                        } else {
                            mostrarIndicador('Error: ' + (json.msg || 'desconocido'), 'bg-red-600');
                        }
                    } catch (e) {
                        itemTemp.remove();
                        mostrarIndicador('Error de red', 'bg-red-600');
                    }
                }

                function agregarArchivoLista(a) {
                    const li = document.createElement('li');
                    li.className = 'bs-uploader-item flex items-center justify-between bg-white border border-gray-200 rounded-lg px-3 py-2';
                    li.dataset.archivoId = a.id;
                    li.innerHTML = `
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-orange-500 flex-shrink-0">📄</span>
                            <a href="${a.url}" target="_blank" class="text-sm text-gray-800 hover:text-orange-600 truncate">${a.nombre}</a>
                            <span class="text-xs text-gray-500 flex-shrink-0">${a.tamano}</span>
                        </div>
                        <button type="button" class="bs-uploader-delete text-red-500 hover:text-red-700 text-sm font-semibold ml-2">Eliminar</button>
                    `;
                    lista.appendChild(li);
                    bindDelete(li);
                }

                function bindDelete(li) {
                    li.querySelector('.bs-uploader-delete')?.addEventListener('click', async () => {
                        if (!confirm('¿Eliminar este archivo?')) return;
                        const id = li.dataset.archivoId;
                        try {
                            const r = await fetch(`/o/${token}/a/${id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                            });
                            const json = await r.json();
                            if (json.ok) {
                                li.remove();
                                if (typeof json.porcentaje !== 'undefined') {
                                    barra.style.width = json.porcentaje + '%';
                                    labelProgreso.textContent = json.porcentaje + '% completo';
                                }
                                mostrarIndicador('Eliminado', 'bg-gray-700');
                            }
                        } catch (e) {
                            mostrarIndicador('Error al eliminar', 'bg-red-600');
                        }
                    });
                }

                input.addEventListener('change', e => {
                    const files = Array.from(e.target.files);
                    if (!isMultiple && files.length > 1) {
                        mostrarIndicador('Solo 1 archivo permitido', 'bg-red-600');
                        return;
                    }
                    files.forEach(subirArchivo);
                    input.value = '';
                });

                ['dragenter', 'dragover'].forEach(evt => {
                    dropZone.addEventListener(evt, e => {
                        e.preventDefault();
                        dropZone.classList.add('bg-orange-100', 'border-orange-500');
                    });
                });
                ['dragleave', 'drop'].forEach(evt => {
                    dropZone.addEventListener(evt, e => {
                        e.preventDefault();
                        dropZone.classList.remove('bg-orange-100', 'border-orange-500');
                    });
                });
                dropZone.addEventListener('drop', e => {
                    const files = Array.from(e.dataTransfer.files);
                    if (!isMultiple && files.length > 1) {
                        mostrarIndicador('Solo 1 archivo permitido', 'bg-red-600');
                        return;
                    }
                    files.forEach(subirArchivo);
                });

                lista.querySelectorAll('.bs-uploader-item').forEach(bindDelete);
            });

            // ============ CSV uploaders (catalogo de productos) ============
            document.querySelectorAll('.bs-csv-uploader').forEach(uploader => {
                const campoKey = uploader.dataset.campoKey;
                const input = uploader.querySelector('.bs-csv-input');
                const emptyState = uploader.querySelector('.bs-csv-empty');
                const loadedState = uploader.querySelector('.bs-csv-loaded');
                const replaceBtn = uploader.querySelector('.bs-csv-replace');

                async function subirCsv(file) {
                    if (file.size > 10485760) {
                        mostrarIndicador('CSV > 10 MB', 'bg-red-600');
                        return;
                    }
                    mostrarIndicador('⏳ Subiendo CSV...', 'bg-orange-600');
                    const formData = new FormData();
                    formData.append('archivo', file);

                    try {
                        const r = await fetch(`/o/${token}/csv-productos/${indice}/${campoKey}`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            body: formData
                        });
                        const json = await r.json();
                        if (json.ok) {
                            mostrarIndicador(`✓ ${json.resumen.total_productos} productos / ${json.resumen.total_variantes} variantes`, 'bg-green-600');
                            if (typeof json.porcentaje !== 'undefined') {
                                barra.style.width = json.porcentaje + '%';
                                labelProgreso.textContent = json.porcentaje + '% completo';
                            }
                            // Recargar la pagina para que se renderice el nuevo estado completo
                            setTimeout(() => window.location.reload(), 1200);
                        } else {
                            let msg = json.msg || 'Error al subir CSV';
                            if (json.errores && json.errores.length) {
                                msg = json.errores[0].mensaje || msg;
                            }
                            mostrarIndicador('✗ ' + msg, 'bg-red-600');
                        }
                    } catch (e) {
                        mostrarIndicador('Error de red', 'bg-red-600');
                    }
                }

                if (input) {
                    input.addEventListener('change', e => {
                        const f = e.target.files[0];
                        if (f) subirCsv(f);
                        input.value = '';
                    });
                }

                if (replaceBtn) {
                    replaceBtn.addEventListener('click', () => {
                        if (!confirm('Esto reemplazará el catálogo actual. ¿Continuar?')) return;
                        loadedState.style.display = 'none';
                        emptyState.style.display = 'block';
                        setTimeout(() => input.click(), 100);
                    });
                }

                // Drag & drop tambien en empty state
                const dropZone = emptyState?.querySelector('label');
                if (dropZone) {
                    ['dragenter', 'dragover'].forEach(evt => {
                        dropZone.addEventListener(evt, e => {
                            e.preventDefault();
                            dropZone.classList.add('bg-orange-100', 'border-orange-500');
                        });
                    });
                    ['dragleave', 'drop'].forEach(evt => {
                        dropZone.addEventListener(evt, e => {
                            e.preventDefault();
                            dropZone.classList.remove('bg-orange-100', 'border-orange-500');
                        });
                    });
                    dropZone.addEventListener('drop', e => {
                        const f = e.dataTransfer.files[0];
                        if (f) subirCsv(f);
                    });
                }
            });

            // ============ Productos Constructor (visual) ============
            (function () {
                const modal = document.getElementById('bsProductoModal');
                if (!modal) return;
                const tituloModal = document.getElementById('bsModalTitulo');
                const inputs = {
                    titulo: document.getElementById('bsModalTituloProducto'),
                    descripcion: document.getElementById('bsModalDescripcion'),
                    vendor: document.getElementById('bsModalVendor'),
                    tipo: document.getElementById('bsModalTipo'),
                    tags: document.getElementById('bsModalTags'),
                    categoria: document.getElementById('bsModalCategoria'),
                    seo_title: document.getElementById('bsModalSeoTitle'),
                    seo_description: document.getElementById('bsModalSeoDescription'),
                };
                const opcionesEl = document.getElementById('bsModalOpciones');
                const variantesEl = document.getElementById('bsModalVariantes');
                const imagenInput = document.getElementById('bsModalImagenInput');
                const galeriaEl = document.getElementById('bsModalGaleria');
                const btnAddOpcion = document.getElementById('bsModalAddOpcion');
                const btnGuardar = document.getElementById('bsModalGuardar');

                let estado = {
                    productoId: null,
                    seccionKey: null,
                    campoKey: null,
                    opciones: [], // [{nombre, valores: []}]
                    variantes: [], // [{opcion1_value, opcion2_value, sku, precio, stock, peso_g}]
                    imagenes: [], // [{id, url}]
                    imagenesPendientes: [], // File[] subir tras crear producto
                };

                function abrirModal(producto) {
                    estado.productoId = producto?.id ?? null;
                    estado.seccionKey = producto?.seccionKey ?? null;
                    estado.campoKey = producto?.campoKey ?? null;
                    tituloModal.textContent = estado.productoId ? 'Editar producto' : 'Agregar producto';

                    inputs.titulo.value = producto?.titulo ?? '';
                    inputs.descripcion.value = producto?.descripcion ?? '';
                    inputs.vendor.value = producto?.vendor ?? '';
                    inputs.tipo.value = producto?.tipo ?? '';
                    inputs.tags.value = producto?.tags ?? '';
                    inputs.categoria.value = producto?.categoria ?? '';
                    inputs.seo_title.value = producto?.seo_title ?? '';
                    inputs.seo_description.value = producto?.seo_description ?? '';
                    estado.imagenes = producto?.imagenes ? [...producto.imagenes] : [];
                    estado.imagenesPendientes = [];

                    // Opciones
                    estado.opciones = [];
                    [1, 2, 3].forEach(i => {
                        const nombre = producto?.[`opcion${i}_nombre`];
                        const valores = producto?.[`opcion${i}_valores`] ?? [];
                        if (nombre && valores.length > 0) {
                            estado.opciones.push({ nombre, valores: [...valores] });
                        }
                    });

                    // Variantes existentes
                    estado.variantes = producto?.variantes ? [...producto.variantes] : [];

                    renderOpciones();
                    renderVariantes();
                    renderGaleria();

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function cerrarModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                function renderGaleria() {
                    galeriaEl.innerHTML = '';
                    // Imagenes ya guardadas (con id)
                    estado.imagenes.forEach((img, idx) => {
                        const div = document.createElement('div');
                        div.className = 'relative group aspect-square rounded-lg overflow-hidden border border-gray-200';
                        div.innerHTML = `
                            <img src="${img.url}" class="w-full h-full object-cover">
                            ${idx === 0 ? '<span class="absolute top-1 left-1 bg-orange-500 text-white text-[10px] px-1.5 py-0.5 rounded">Principal</span>' : ''}
                            <button type="button" data-img-id="${img.id}" class="bs-img-del absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full text-xs leading-none hover:bg-red-600">×</button>
                        `;
                        div.querySelector('.bs-img-del').addEventListener('click', () => eliminarImagen(img.id));
                        galeriaEl.appendChild(div);
                    });
                    // Imagenes pendientes (preview local, aun sin subir)
                    estado.imagenesPendientes.forEach((file, idx) => {
                        const div = document.createElement('div');
                        div.className = 'relative aspect-square rounded-lg overflow-hidden border border-orange-200 bg-orange-50 flex items-center justify-center';
                        div.innerHTML = '<span class="text-xs text-orange-500">⏳ pendiente</span>';
                        const reader = new FileReader();
                        reader.onload = () => { div.innerHTML = `<img src="${reader.result}" class="w-full h-full object-cover opacity-60">`; };
                        reader.readAsDataURL(file);
                        galeriaEl.appendChild(div);
                    });
                }

                async function eliminarImagen(imgId) {
                    if (estado.productoId) {
                        const r = await fetch(`/o/${token}/productos/${estado.productoId}/imagen/${imgId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        });
                        const json = await r.json();
                        if (json.ok) { estado.imagenes = json.imagenes || []; renderGaleria(); }
                    } else {
                        estado.imagenes = estado.imagenes.filter(i => i.id !== imgId);
                        renderGaleria();
                    }
                }

                function renderOpciones() {
                    opcionesEl.innerHTML = '';
                    estado.opciones.forEach((opcion, i) => {
                        const div = document.createElement('div');
                        div.className = 'bg-gray-50 rounded-lg p-2 flex gap-2';
                        div.innerHTML = `
                            <input type="text" value="${opcion.nombre}" placeholder="Nombre (Talla)" class="bs-opcion-nombre w-1/3 border-gray-300 rounded px-2 py-1 text-sm">
                            <input type="text" value="${opcion.valores.join(', ')}" placeholder="Valores: S, M, L" class="bs-opcion-valores flex-1 border-gray-300 rounded px-2 py-1 text-sm">
                            <button type="button" class="bs-opcion-delete text-red-500 hover:text-red-700 text-sm font-semibold">×</button>
                        `;
                        const nombreInput = div.querySelector('.bs-opcion-nombre');
                        const valoresInput = div.querySelector('.bs-opcion-valores');
                        nombreInput.addEventListener('change', () => { estado.opciones[i].nombre = nombreInput.value; regenerarVariantes(); });
                        valoresInput.addEventListener('change', () => { estado.opciones[i].valores = valoresInput.value.split(',').map(v => v.trim()).filter(v => v); regenerarVariantes(); });
                        div.querySelector('.bs-opcion-delete').addEventListener('click', () => { estado.opciones.splice(i, 1); renderOpciones(); regenerarVariantes(); });
                        opcionesEl.appendChild(div);
                    });

                    btnAddOpcion.style.display = estado.opciones.length >= 3 ? 'none' : 'inline-block';
                }

                function regenerarVariantes() {
                    // Producto cartesiano de los valores de las opciones
                    let combos = [[]];
                    estado.opciones.forEach(op => {
                        const valores = op.valores.length ? op.valores : [''];
                        const nuevo = [];
                        combos.forEach(parcial => valores.forEach(v => nuevo.push([...parcial, v])));
                        combos = nuevo;
                    });
                    if (estado.opciones.length === 0) combos = [[]];

                    // Mantener datos existentes por clave de combinacion
                    const previas = {};
                    estado.variantes.forEach(v => {
                        const key = [v.opcion1_value || '', v.opcion2_value || '', v.opcion3_value || ''].join('|');
                        previas[key] = v;
                    });

                    estado.variantes = combos.map(combo => {
                        const o1 = combo[0] || null;
                        const o2 = combo[1] || null;
                        const o3 = combo[2] || null;
                        const key = [o1 || '', o2 || '', o3 || ''].join('|');
                        const prev = previas[key] || {};
                        return {
                            opcion1_value: o1,
                            opcion2_value: o2,
                            opcion3_value: o3,
                            sku: prev.sku ?? '',
                            precio: prev.precio ?? '',
                            stock: prev.stock ?? '',
                            peso_g: prev.peso_g ?? '',
                            compare_at: prev.compare_at ?? null,
                            costo: prev.costo ?? null,
                            barcode: prev.barcode ?? null,
                        };
                    });
                    renderVariantes();
                }

                function renderVariantes() {
                    variantesEl.innerHTML = '';
                    if (estado.variantes.length === 0 && estado.opciones.length === 0) {
                        // Producto sin variantes: 1 sola fila
                        estado.variantes = [{ sku: '', precio: '', stock: '', peso_g: '' }];
                    }

                    estado.variantes.forEach((v, i) => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center gap-1 bg-white border border-gray-100 rounded px-2 py-1 text-xs';
                        const label = [v.opcion1_value, v.opcion2_value, v.opcion3_value].filter(x => x).join(' / ') || 'Único';
                        div.innerHTML = `
                            <span class="font-semibold text-gray-700 w-28 truncate">${label}</span>
                            <input type="text" value="${v.sku || ''}" placeholder="SKU" class="bs-v-sku w-24 border-gray-300 rounded px-2 py-1 text-xs">
                            <input type="number" step="0.01" value="${v.precio || ''}" placeholder="Precio" class="bs-v-precio w-24 border-gray-300 rounded px-2 py-1 text-xs">
                            <input type="number" value="${v.stock || ''}" placeholder="Stock" class="bs-v-stock w-20 border-gray-300 rounded px-2 py-1 text-xs">
                            <input type="number" value="${v.peso_g || ''}" placeholder="Peso(g)" class="bs-v-peso w-20 border-gray-300 rounded px-2 py-1 text-xs">
                        `;
                        div.querySelector('.bs-v-sku').addEventListener('change', e => { estado.variantes[i].sku = e.target.value; });
                        div.querySelector('.bs-v-precio').addEventListener('change', e => { estado.variantes[i].precio = e.target.value === '' ? null : parseFloat(e.target.value); });
                        div.querySelector('.bs-v-stock').addEventListener('change', e => { estado.variantes[i].stock = e.target.value === '' ? null : parseInt(e.target.value); });
                        div.querySelector('.bs-v-peso').addEventListener('change', e => { estado.variantes[i].peso_g = e.target.value === '' ? null : parseFloat(e.target.value); });
                        variantesEl.appendChild(div);
                    });
                }

                btnAddOpcion.addEventListener('click', () => {
                    if (estado.opciones.length >= 3) return;
                    estado.opciones.push({ nombre: '', valores: [] });
                    renderOpciones();
                });

                modal.querySelectorAll('.bs-modal-close').forEach(btn => btn.addEventListener('click', cerrarModal));
                modal.addEventListener('click', e => { if (e.target === modal) cerrarModal(); });

                // Upload imagen al cerrar modal con producto creado
                imagenInput.addEventListener('change', async () => {
                    const files = Array.from(imagenInput.files);
                    imagenInput.value = '';
                    if (!files.length) return;
                    if (!estado.productoId) {
                        // Producto aun no creado: guardar como pendientes (se suben tras crear)
                        estado.imagenesPendientes.push(...files);
                        renderGaleria();
                        return;
                    }
                    for (const f of files) {
                        await subirImagenProducto(estado.productoId, f);
                    }
                });

                async function subirImagenProducto(productoId, file) {
                    const fd = new FormData();
                    fd.append('archivo', file);
                    const r = await fetch(`/o/${token}/productos/${productoId}/imagen`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: fd,
                    });
                    const json = await r.json();
                    if (json.ok) {
                        estado.imagenes = json.imagenes || [];
                        renderGaleria();
                        mostrarIndicador('Imagen subida ✓', 'bg-green-600');
                    } else {
                        mostrarIndicador('Error subiendo imagen', 'bg-red-600');
                    }
                }

                btnGuardar.addEventListener('click', async () => {
                    const payload = {
                        titulo: inputs.titulo.value,
                        descripcion: inputs.descripcion.value || null,
                        vendor: inputs.vendor.value || null,
                        tipo: inputs.tipo.value || null,
                        tags: inputs.tags.value || null,
                        categoria: inputs.categoria.value || null,
                        seo_title: inputs.seo_title.value || null,
                        seo_description: inputs.seo_description.value || null,
                        publicado: true,
                        estado: 'active',
                        requiere_envio: true,
                        es_gift_card: false,
                        opcion1_nombre: estado.opciones[0]?.nombre || null,
                        opcion1_valores: estado.opciones[0]?.valores || [],
                        opcion2_nombre: estado.opciones[1]?.nombre || null,
                        opcion2_valores: estado.opciones[1]?.valores || [],
                        opcion3_nombre: estado.opciones[2]?.nombre || null,
                        opcion3_valores: estado.opciones[2]?.valores || [],
                        variantes: estado.variantes,
                    };
                    if (!payload.titulo) { mostrarIndicador('Falta el título', 'bg-red-600'); return; }
                    if (payload.variantes.length === 0) {
                        payload.variantes = [{ sku: '', precio: 0, stock: 0 }];
                    }

                    btnGuardar.disabled = true;
                    btnGuardar.textContent = 'Guardando...';
                    try {
                        const url = estado.productoId
                            ? `/o/${token}/productos/${estado.productoId}`
                            : `/o/${token}/productos/${indice}/${estado.campoKey}`;
                        const method = estado.productoId ? 'PUT' : 'POST';
                        const r = await fetch(url, {
                            method,
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            body: JSON.stringify(payload),
                        });
                        const json = await r.json();
                        if (json.ok) {
                            // Subir imagenes pendientes que se seleccionaron antes de crear el producto
                            if (estado.imagenesPendientes.length && !estado.productoId) {
                                for (const f of estado.imagenesPendientes) {
                                    await subirImagenProducto(json.producto.id, f);
                                }
                            }
                            mostrarIndicador('Producto guardado ✓', 'bg-green-600');
                            if (typeof json.porcentaje !== 'undefined') {
                                barra.style.width = json.porcentaje + '%';
                                labelProgreso.textContent = json.porcentaje + '% completo';
                            }
                            setTimeout(() => window.location.reload(), 800);
                        } else {
                            mostrarIndicador('Error guardando', 'bg-red-600');
                        }
                    } catch (e) {
                        mostrarIndicador('Error de red', 'bg-red-600');
                    } finally {
                        btnGuardar.disabled = false;
                        btnGuardar.textContent = 'Guardar producto';
                    }
                });

                // ===== Selector de origen del catalogo =====
                document.querySelectorAll('.bs-origen-selector').forEach(sel => {
                    const indiceSel = sel.dataset.indice;
                    const campoKeySel = sel.dataset.campoKey;
                    const root = sel.closest('.bs-campo') || sel.parentElement;
                    const syncForm = root.querySelector('.bs-sync-form');
                    const appManual = root.querySelector('.bs-productos-app');

                    async function setOrigen(origen) {
                        // UI
                        sel.querySelectorAll('.bs-origen-opt').forEach(b => {
                            const on = b.dataset.origen === origen;
                            b.classList.toggle('border-orange-500', on);
                            b.classList.toggle('bg-orange-50', on);
                            b.classList.toggle('border-gray-200', !on);
                        });
                        if (origen === 'manual') {
                            appManual?.classList.remove('hidden');
                            syncForm?.classList.add('hidden');
                        } else {
                            appManual?.classList.add('hidden');
                            syncForm?.classList.remove('hidden');
                            const nom = syncForm?.querySelector('.bs-sync-nombre');
                            if (nom) nom.textContent = origen.charAt(0).toUpperCase() + origen.slice(1);
                        }
                        // Guardar origen
                        await fetch(`/o/${token}/productos-origen/${indiceSel}/${campoKeySel}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            body: JSON.stringify({ origen }),
                        }).then(r => r.json()).then(j => {
                            if (j.ok && typeof j.porcentaje !== 'undefined') {
                                barra.style.width = j.porcentaje + '%';
                                labelProgreso.textContent = j.porcentaje + '% completo';
                            }
                        });
                    }

                    sel.querySelectorAll('.bs-origen-opt').forEach(btn => {
                        btn.addEventListener('click', () => setOrigen(btn.dataset.origen));
                    });

                    // Guardar credenciales sync
                    if (syncForm) {
                        const btnG = syncForm.querySelector('.bs-sync-guardar');
                        const msg = syncForm.querySelector('.bs-sync-msg');
                        btnG?.addEventListener('click', async () => {
                            const origenActivo = sel.querySelector('.bs-origen-opt.border-orange-500')?.dataset.origen || 'bsale';
                            const email = syncForm.querySelector('.bs-sync-email').value.trim();
                            const password = syncForm.querySelector('.bs-sync-password').value;
                            if (!email) { msg.textContent = 'Ingresa el correo'; msg.className = 'bs-sync-msg text-xs text-center text-red-600'; return; }
                            btnG.disabled = true; btnG.textContent = 'Guardando...';
                            const r = await fetch(`/o/${token}/productos-origen/${indiceSel}/${campoKeySel}`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                body: JSON.stringify({ origen: origenActivo, email, password }),
                            });
                            const j = await r.json();
                            btnG.disabled = false; btnG.textContent = 'Guardar accesos';
                            if (j.ok) {
                                msg.textContent = '✓ Accesos guardados de forma segura';
                                msg.className = 'bs-sync-msg text-xs text-center text-green-600';
                                if (typeof j.porcentaje !== 'undefined') {
                                    barra.style.width = j.porcentaje + '%';
                                    labelProgreso.textContent = j.porcentaje + '% completo';
                                }
                            } else {
                                msg.textContent = 'Error al guardar';
                                msg.className = 'bs-sync-msg text-xs text-center text-red-600';
                            }
                        });
                    }
                });

                // Inicializar las apps de constructor
                document.querySelectorAll('.bs-productos-app').forEach(app => {
                    const seccionKey = app.dataset.seccionKey;
                    const campoKey = app.dataset.campoKey;

                    app.querySelector('.bs-productos-add').addEventListener('click', () => {
                        abrirModal({ seccionKey, campoKey });
                    });

                    // Buscador en vivo
                    const search = app.querySelector('.bs-productos-search');
                    const noResults = app.querySelector('.bs-productos-noresults');
                    if (search) {
                        search.addEventListener('input', () => {
                            const q = search.value.trim().toLowerCase();
                            let visibles = 0;
                            app.querySelectorAll('.bs-producto-row').forEach(row => {
                                const match = !q || (row.dataset.nombre || '').includes(q);
                                row.style.display = match ? '' : 'none';
                                if (match) visibles++;
                            });
                            if (noResults) noResults.classList.toggle('hidden', visibles > 0 || !q);
                        });
                    }

                    app.querySelectorAll('.bs-producto-row').forEach(row => {
                        const productoId = row.dataset.productoId;

                        row.querySelector('.bs-producto-edit').addEventListener('click', async () => {
                            const r = await fetch(`/o/${token}/productos/${indice}/${campoKey}`);
                            const json = await r.json();
                            if (json.ok) {
                                const p = (json.productos || []).find(p => String(p.id) === String(productoId));
                                if (p) abrirModal({ ...p, seccionKey, campoKey });
                            }
                        });

                        row.querySelector('.bs-producto-dup').addEventListener('click', async () => {
                            mostrarIndicador('Duplicando...', 'bg-orange-600');
                            const r = await fetch(`/o/${token}/productos/${productoId}/duplicar`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            });
                            const json = await r.json();
                            if (json.ok) {
                                mostrarIndicador('Producto duplicado ✓', 'bg-green-600');
                                if (typeof json.porcentaje !== 'undefined') {
                                    barra.style.width = json.porcentaje + '%';
                                    labelProgreso.textContent = json.porcentaje + '% completo';
                                }
                                setTimeout(() => window.location.reload(), 700);
                            } else {
                                mostrarIndicador('Error al duplicar', 'bg-red-600');
                            }
                        });

                        row.querySelector('.bs-producto-delete').addEventListener('click', async () => {
                            if (!confirm(`¿Eliminar este producto?`)) return;
                            const r = await fetch(`/o/${token}/productos/${productoId}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            });
                            const json = await r.json();
                            if (json.ok) {
                                row.remove();
                                mostrarIndicador('Eliminado', 'bg-gray-700');
                                if (typeof json.porcentaje !== 'undefined') {
                                    barra.style.width = json.porcentaje + '%';
                                    labelProgreso.textContent = json.porcentaje + '% completo';
                                }
                                setTimeout(() => window.location.reload(), 500);
                            }
                        });
                    });
                });
            })();
        })();
    </script>


</body>
</html>
