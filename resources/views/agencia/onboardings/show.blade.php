<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="bs-card overflow-hidden">
                <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <a href="{{ route("agencia.onboardings.index") }}" class="text-white/80 hover:text-white text-sm">← Volver a Onboardings</a> · <a href="{{ route("agencia.onboardings.imprimir", $proyecto) }}" target="_blank" class="text-white/80 hover:text-white text-sm">🖨 Imprimir / PDF</a> · <a href="{{ route("agencia.onboardings.zip", $proyecto) }}" class="text-white/80 hover:text-white text-sm">📦 Descargar ZIP</a> · <a href="{{ route("agencia.onboardings.edit", $proyecto) }}" class="text-white/80 hover:text-white text-sm">✏️ Editar</a>
                            <h2 class="bs-display text-2xl text-white m-0 mt-1">{{ $proyecto->titulo }}</h2>
                            <p class="text-sm text-white/90 mt-1 mb-0">
                                {{ $proyecto->cliente->nombre ?? '—' }} · {{ $proyecto->plantilla->nombre ?? '—' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1.5 rounded-full bg-white text-orange-600 font-bold text-lg">
                                {{ $proyecto->porcentaje_avance }}%
                            </span>
                            @php $colors = ["no_iniciado"=>"yellow","en_progreso"=>"orange","completado"=>"green","archivado"=>"gray"]; @endphp
                            <div class="mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full bg-{{ $colors[$proyecto->estado] ?? 'gray' }}-100 text-{{ $colors[$proyecto->estado] ?? 'gray' }}-800">
                                {{ str_replace('_', ' ', $proyecto->estado) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Link público para el cliente</div>
                        <div class="flex items-center gap-2 bg-gray-50 border rounded-lg px-3 py-2">
                            <code class="text-sm text-gray-700 flex-1 break-all">{{ $proyecto->urlPublica() }}</code>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $proyecto->urlPublica() }}');this.textContent='✓';setTimeout(()=>this.textContent='Copiar',1500)"
                                    class="text-orange-600 hover:text-orange-800 text-sm font-semibold whitespace-nowrap">Copiar</button>
                        </div>
                        @if($proyecto->token_expira_en)
                            <p class="text-xs text-gray-500 mt-1">Vence el {{ $proyecto->token_expira_en->format('d/m/Y') }}</p>
                        @endif
                    </div>

                    <div class="space-y-1 text-sm">
                        <div><span class="text-gray-500 inline-block w-32">Creado:</span> {{ $proyecto->created_at->format('d/m/Y H:i') }}</div>
                        @if($proyecto->fecha_envio)
                            <div><span class="text-gray-500 inline-block w-32">Email enviado:</span> {{ $proyecto->fecha_envio->format('d/m/Y H:i') }}</div>
                        @endif
                        @if($proyecto->fecha_primer_acceso)
                            <div><span class="text-gray-500 inline-block w-32">Primer acceso:</span> {{ $proyecto->fecha_primer_acceso->format('d/m/Y H:i') }}</div>
                        @endif
                        @if($proyecto->fecha_completado)
                            <div><span class="text-gray-500 inline-block w-32">Completado:</span> <strong class="text-green-700">{{ $proyecto->fecha_completado->format('d/m/Y H:i') }}</strong></div>
                        @endif
                        @if($proyecto->email_cliente)
                            <div><span class="text-gray-500 inline-block w-32">Email cliente:</span> <a href="mailto:{{ $proyecto->email_cliente }}" class="text-orange-600">{{ $proyecto->email_cliente }}</a></div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Invitacion al cliente --}}
            <div class="bs-card p-6">
                <h3 class="font-bold text-lg mb-3">Enviar invitación al cliente</h3>
                @if($proyecto->fecha_envio)
                    <p class="text-sm text-green-700 mb-3">✓ Última invitación enviada el {{ $proyecto->fecha_envio->format('d/m/Y H:i') }}</p>
                @else
                    <p class="text-sm text-gray-500 mb-3">Aún no se ha enviado el link al cliente.</p>
                @endif
                <form method="POST" action="{{ route('agencia.onboardings.enviar-invitacion', $proyecto) }}" class="flex gap-2 flex-wrap items-start">
                    @csrf
                    <input type="email" name="email" required value="{{ $proyecto->email_cliente }}"
                           placeholder="email@cliente.cl"
                           class="flex-1 min-w-[240px] border-gray-300 rounded-lg px-3 py-2">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2 rounded-lg whitespace-nowrap">
                        {{ $proyecto->fecha_envio ? 'Reenviar invitación' : 'Enviar invitación' }}
                    </button>
                </form>
                @if($errors->has('email'))
                    <p class="text-red-600 text-sm mt-2">{{ $errors->first('email') }}</p>
                @endif
                @if(session('success'))
                    <p class="text-green-700 text-sm mt-2">✓ {{ session('success') }}</p>
                @endif
            </div>

            {{-- Respuestas agrupadas por seccion, con labels reales de la plantilla --}}
            @php
                $secciones = $proyecto->plantilla->secciones ?? [];
                $respuestasMap = $proyecto->respuestas->keyBy(fn($r) => $r->seccion_key . '|' . $r->campo_key);
                $archivosMap = $proyecto->archivos->groupBy(fn($a) => $a->seccion_key . '|' . $a->campo_key);
            @endphp

            @if(empty($secciones))
                <div class="bs-card p-6 text-center text-gray-500">
                    La plantilla no tiene secciones definidas.
                </div>
            @else
                @foreach($secciones as $idx => $seccion)
                    @php
                        $campos = $seccion['campos'] ?? [];
                        $tieneAlgo = collect($campos)->contains(function($c) use ($seccion, $respuestasMap, $archivosMap) {
                            $key = $seccion['key'] . '|' . $c['key'];
                            $r = $respuestasMap->get($key);
                            $hasResp = $r && trim((string)$r->valor) !== '' && $r->valor !== 'archivos_subidos';
                            $hasArchivos = $archivosMap->has($key);
                            return $hasResp || $hasArchivos;
                        });
                    @endphp

                    <div class="bs-card overflow-hidden">
                        <div class="px-5 py-3 flex items-center justify-between border-b border-gray-100 bg-gradient-to-r from-orange-50 to-amber-50">
                            <div>
                                <div class="text-xs text-orange-600 uppercase font-bold tracking-wider">Sección {{ $idx + 1 }} de {{ count($secciones) }}</div>
                                <div class="font-bold text-gray-800">{{ $seccion['titulo'] }}</div>
                            </div>
                            @if($tieneAlgo)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">✓ Con datos</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-500">Sin datos aún</span>
                            @endif
                        </div>

                        <div class="divide-y divide-gray-100">
                            @foreach($campos as $campo)
                                @php
                                    $key = $seccion['key'] . '|' . $campo['key'];
                                    $resp = $respuestasMap->get($key);
                                    $archivos = $archivosMap->get($key, collect());
                                    $valor = $resp?->valor;
                                    $esArchivo = in_array($campo['tipo'] ?? 'texto', ['archivo_unico','archivo_multiple']);
                                    $vacio = $esArchivo
                                        ? $archivos->isEmpty()
                                        : (empty($valor) || $valor === 'archivos_subidos');
                                @endphp
                                <div class="px-5 py-3 flex items-start gap-3 {{ $vacio ? 'opacity-50' : '' }}">
                                    <div class="w-1/3 flex-shrink-0">
                                        <div class="text-sm font-semibold text-gray-700">{{ $campo['label'] }}</div>
                                        @if($campo['requerido'] ?? false)
                                            <span class="text-xs text-orange-500">requerido</span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        @if($vacio)
                                            <span class="text-sm text-gray-400 italic">— sin respuesta —</span>
                                        @elseif($esArchivo)
                                            <ul class="space-y-1">
                                                @foreach($archivos as $a)
                                                    <li class="flex items-center gap-2 text-sm">
                                                        <span class="text-orange-500">📄</span>
                                                        <a href="{{ route('onboarding.archivo.descargar', ['token' => $proyecto->token, 'archivo' => $a->id]) }}"
                                                           target="_blank"
                                                           class="text-orange-600 hover:text-orange-800 font-semibold hover:underline truncate">
                                                            {{ $a->nombre_original }}
                                                        </a>
                                                        <span class="text-xs text-gray-500">({{ $a->tamanoLegible() }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @elseif(($campo['tipo'] ?? '') === 'confirmacion')
                                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">✓ Confirmado</span>
                                        @elseif(strlen((string)$valor) > 200)
                                            <details class="text-sm text-gray-800">
                                                <summary class="cursor-pointer text-orange-600 hover:text-orange-800">{{ Str::limit($valor, 200) }}</summary>
                                                <div class="mt-2 whitespace-pre-wrap bg-gray-50 border border-gray-200 rounded p-3">{{ $valor }}</div>
                                            </details>
                                        @else
                                            <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ $valor }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- Productos cargados (constructor visual) --}}
            @php
                $productosCargados = \App\Models\AgenciaOnboardingProducto::with('imagen')
                    ->where('proyecto_id', $proyecto->id)
                    ->orderBy('seccion_key')->orderBy('orden')->orderBy('id')->get();
            @endphp
            @if($productosCargados->count())
                <div class="bs-card overflow-hidden">
                    <div class="px-5 py-3 flex items-center justify-between border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50 flex-wrap gap-2">
                        <div>
                            <div class="text-xs text-green-700 uppercase font-bold tracking-wider">📦 Catálogo de productos</div>
                            <div class="font-bold text-gray-800">
                                {{ $productosCargados->count() }} productos · {{ $productosCargados->sum(fn($p) => $p->cantidadVariantes()) }} variantes totales
                            </div>
                        </div>
                        <a href="{{ route('agencia.onboardings.csv-shopify', $proyecto) }}"
                           class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                            ⬇️ Descargar CSV Shopify
                        </a>
                    </div>

                    <div class="overflow-x-auto overflow-y-auto {{ $productosCargados->count() > 10 ? 'max-h-[640px]' : '' }}">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Img</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Producto</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Vendor</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Precio</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Stock</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Variantes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach($productosCargados as $p)
                                <tr>
                                    <td class="px-3 py-2">
                                        @if($p->imagen_archivo_id)
                                            <img src="{{ route('onboarding.archivo.descargar', ['token' => $proyecto->token, 'archivo' => $p->imagen_archivo_id]) }}"
                                                 alt="" class="w-12 h-12 object-cover rounded">
                                        @else
                                            <div class="w-12 h-12 bg-gray-100 flex items-center justify-center text-gray-300 rounded">📦</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="font-semibold text-gray-800">{{ $p->titulo }}</div>
                                        @if($p->tags)
                                            <div class="text-xs text-gray-400 mt-1">{{ \Illuminate\Support\Str::limit($p->tags, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-xs">{{ $p->vendor ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        @php $min = $p->precioMin(); $max = $p->precioMax(); @endphp
                                        @if($min === null)
                                            <span class="text-gray-400">—</span>
                                        @elseif($min == $max)
                                            ${{ number_format($min, 0, ',', '.') }}
                                        @else
                                            ${{ number_format($min, 0, ',', '.') }} - ${{ number_format($max, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">{{ $p->stockTotal() }}</td>
                                    <td class="px-3 py-2">{{ $p->cantidadVariantes() }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Historial --}}
            @php
                $iconos = [
                    'creado' => '🆕', 'enviado' => '📧', 'abierto' => '👁',
                    'seccion_completada' => '✅', 'archivo_subido' => '📎',
                    'catalogo_csv_subido' => '📊', 'producto_creado' => '🛍️',
                    'producto_duplicado' => '⧉', 'editado' => '✏️',
                    'completado' => '🎉', 'notificacion_enviada' => '🔔',
                    'notificacion_fallida' => '⚠️', 'envio_fallido' => '⚠️',
                    'webhook_enviado' => '🔗', 'webhook_fallido' => '⚠️',
                    'recordatorio_enviado' => '⏰',
                ];
                // Deduplicar eventos consecutivos identicos (mismo tipo + descripcion)
                $eventosDedup = [];
                $prevKey = null;
                foreach ($proyecto->eventos as $e) {
                    $key = $e->tipo . '|' . $e->descripcion;
                    if ($key === $prevKey) {
                        $eventosDedup[count($eventosDedup) - 1]['count']++;
                        $eventosDedup[count($eventosDedup) - 1]['ultimo'] = $e->created_at;
                    } else {
                        $eventosDedup[] = ['evento' => $e, 'count' => 1, 'ultimo' => $e->created_at];
                        $prevKey = $key;
                    }
                }
                $totalDedup = count($eventosDedup);
                $visibles = array_slice($eventosDedup, 0, 8);
                $ocultos = array_slice($eventosDedup, 8);
            @endphp
            <div class="bs-card p-6">
                <h3 class="font-bold text-lg mb-3">
                    Historial de actividad
                    <span class="text-sm font-normal text-gray-400">({{ $totalDedup }} {{ $totalDedup === 1 ? 'evento' : 'eventos' }})</span>
                </h3>
                @if($proyecto->eventos->isEmpty())
                    <p class="text-gray-500 text-sm">Sin eventos registrados.</p>
                @else
                    <ol class="space-y-2 text-sm">
                        @foreach($visibles as $item)
                            @php $e = $item['evento']; $ic = $iconos[$e->tipo] ?? '•'; @endphp
                            <li class="flex gap-3">
                                <span class="flex-shrink-0">{{ $ic }}</span>
                                <span class="text-gray-400 whitespace-nowrap">{{ $item['ultimo']?->format('d/m H:i') }}</span>
                                <span>
                                    <strong class="text-orange-600">{{ str_replace('_', ' ', $e->tipo) }}</strong>
                                    @if($item['count'] > 1)<span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">×{{ $item['count'] }}</span>@endif
                                    — {{ $e->descripcion ?? '' }}
                                </span>
                            </li>
                        @endforeach
                    </ol>

                    @if(count($ocultos) > 0)
                        <details class="mt-3">
                            <summary class="cursor-pointer text-orange-600 hover:text-orange-800 text-sm font-semibold">
                                Ver {{ count($ocultos) }} evento(s) anterior(es)
                            </summary>
                            <ol class="space-y-2 text-sm mt-3 pt-3 border-t border-gray-100">
                                @foreach($ocultos as $item)
                                    @php $e = $item['evento']; $ic = $iconos[$e->tipo] ?? '•'; @endphp
                                    <li class="flex gap-3 opacity-75">
                                        <span class="flex-shrink-0">{{ $ic }}</span>
                                        <span class="text-gray-400 whitespace-nowrap">{{ $item['ultimo']?->format('d/m H:i') }}</span>
                                        <span>
                                            <strong class="text-orange-600">{{ str_replace('_', ' ', $e->tipo) }}</strong>
                                            @if($item['count'] > 1)<span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">×{{ $item['count'] }}</span>@endif
                                            — {{ $e->descripcion ?? '' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        </details>
                    @endif
                @endif
            </div>

            @if(!empty($proyecto->notas_internas))
                <div class="bs-card p-6 bg-yellow-50 border border-yellow-200">
                    <h3 class="font-bold text-sm text-yellow-800 mb-2">📝 Notas internas (no visibles al cliente)</h3>
                    <div class="text-sm text-yellow-900 whitespace-pre-wrap">{{ $proyecto->notas_internas }}</div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
