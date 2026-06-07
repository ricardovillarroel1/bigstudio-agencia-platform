<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bs-card overflow-hidden">
                <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <h2 class="bs-display text-2xl text-white m-0">{{ $proyecto->titulo }}</h2>
                            <p class="text-sm text-white/90 mt-1 mb-0">{{ $proyecto->cliente->nombre ?? "—" }} · {{ $proyecto->plantilla->nombre ?? "—" }}</p>
                        </div>
                        <span class="px-3 py-1.5 rounded-full bg-white text-orange-600 font-bold text-sm">
                            {{ $proyecto->porcentaje_avance }}%
                        </span>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Link publico para el cliente</div>
                        <div class="flex items-center gap-2 bg-gray-50 border rounded-lg px-3 py-2">
                            <code class="text-sm text-gray-700 flex-1 break-all">{{ $proyecto->urlPublica() }}</code>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $proyecto->urlPublica() }}')"
                                    class="text-orange-600 hover:text-orange-800 text-sm font-semibold whitespace-nowrap">Copiar</button>
                        </div>
                        @if($proyecto->token_expira_en)
                            <p class="text-xs text-gray-500 mt-1">Valido hasta {{ $proyecto->token_expira_en->format("d/m/Y") }}</p>
                        @endif
                    </div>

                    <div class="space-y-1 text-sm">
                        <div><span class="text-gray-500">Estado:</span> <strong>{{ str_replace("_", " ", $proyecto->estado) }}</strong></div>
                        <div><span class="text-gray-500">Creado:</span> {{ $proyecto->created_at->format("d/m/Y H:i") }}</div>
                        @if($proyecto->fecha_primer_acceso)
                            <div><span class="text-gray-500">Primer acceso:</span> {{ $proyecto->fecha_primer_acceso->format("d/m/Y H:i") }}</div>
                        @endif
                        @if($proyecto->fecha_completado)
                            <div><span class="text-gray-500">Completado:</span> {{ $proyecto->fecha_completado->format("d/m/Y H:i") }}</div>
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

            {{-- Respuestas --}}
            <div class="bs-card p-6">
                <h3 class="font-bold text-lg mb-3">Respuestas del cliente</h3>
                @if($proyecto->respuestas->isEmpty())
                    <p class="text-gray-500 text-sm">Aun no hay respuestas. El cliente no ha empezado a completar el onboarding.</p>
                @else
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs text-gray-500 uppercase"><th class="py-2">Seccion</th><th>Campo</th><th>Valor</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($proyecto->respuestas as $r)
                                <tr><td class="py-2 font-semibold">{{ $r->seccion_key }}</td><td>{{ $r->campo_key }}</td><td class="text-gray-700">{{ Str::limit($r->valor, 120) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Archivos --}}
            <div class="bs-card p-6">
                <h3 class="font-bold text-lg mb-3">Archivos subidos</h3>
                @if($proyecto->archivos->isEmpty())
                    <p class="text-gray-500 text-sm">Sin archivos aun.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($proyecto->archivos as $a)
                            <li class="py-2 flex items-center justify-between">
                                <div>
                                    <a href="{{ $a->urlPublica() }}" target="_blank" class="text-orange-600 hover:underline font-semibold">{{ $a->nombre_original }}</a>
                                    <span class="text-xs text-gray-500 ml-2">{{ $a->seccion_key }} · {{ $a->tamanoLegible() }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Eventos --}}
            <div class="bs-card p-6">
                <h3 class="font-bold text-lg mb-3">Historial</h3>
                <ol class="space-y-2 text-sm">
                    @foreach($proyecto->eventos as $e)
                        <li class="flex gap-3">
                            <span class="text-gray-400 whitespace-nowrap">{{ $e->created_at?->format("d/m H:i") }}</span>
                            <span><strong class="text-orange-600">{{ $e->tipo }}</strong> — {{ $e->descripcion ?? "" }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</x-app-layout>
