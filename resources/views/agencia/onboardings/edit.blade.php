<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bs-card overflow-hidden">
                <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <a href="{{ route('agencia.onboardings.show', $proyecto) }}" class="text-white/80 hover:text-white text-sm">← Volver al detalle</a>
                    <h2 class="bs-display text-2xl text-white m-0 mt-1">Editar onboarding</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">{{ $proyecto->cliente->nombre ?? '—' }}</p>
                </div>

                <form method="POST" action="{{ route('agencia.onboardings.update', $proyecto) }}" class="p-6 space-y-5" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Título</label>
                        <input type="text" name="titulo" required maxlength="255"
                               value="{{ old('titulo', $proyecto->titulo) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2">
                        @error('titulo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Plantilla</label>
                        <select name="plantilla_id" required class="w-full border-gray-300 rounded-lg px-3 py-2 bg-white">
                            @foreach($plantillas as $pl)
                                <option value="{{ $pl->id }}" {{ (int)old('plantilla_id', $proyecto->plantilla_id) === $pl->id ? 'selected' : '' }}>{{ $pl->nombre }} ({{ count($pl->secciones ?? []) }} secciones)</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">⚠ Cambiar la plantilla puede generar respuestas huérfanas si los campos no coinciden.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email del cliente</label>
                        <input type="email" name="email_cliente" maxlength="255"
                               value="{{ old('email_cliente', $proyecto->email_cliente) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Estado</label>
                        <select name="estado" required class="w-full border-gray-300 rounded-lg px-3 py-2 bg-white">
                            @foreach(['no_iniciado' => 'No iniciado', 'en_progreso' => 'En progreso', 'completado' => 'Completado', 'archivado' => 'Archivado'] as $v => $label)
                                <option value="{{ $v }}" {{ old('estado', $proyecto->estado) === $v ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Extender vigencia del token</label>
                        <div class="flex items-center gap-3">
                            <input type="number" name="dias_validez_extra" min="1" max="365"
                                   placeholder="0"
                                   class="w-32 border-gray-300 rounded-lg px-3 py-2">
                            <span class="text-sm text-gray-600">días extra (desde {{ $proyecto->token_expira_en?->format('d/m/Y') ?? 'hoy' }})</span>
                        </div>
                        @if($proyecto->token_expira_en)
                            <p class="text-xs text-gray-500 mt-1">Vence actualmente: {{ $proyecto->token_expira_en->format('d/m/Y') }}</p>
                        @endif
                    </div>

                    {{-- Branding personalizado del cliente --}}
                    <div class="border-t border-gray-100 pt-4">
                        <div class="text-sm font-bold text-gray-800 mb-3">Personalización para el cliente (white-glove)</div>

                        <div class="mb-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Logo del cliente (aparece en su wizard)</label>
                            @if($proyecto->logo_cliente_archivo_id)
                                <div class="flex items-center gap-3 mb-2">
                                    <img src="{{ route('onboarding.archivo.descargar', ['token' => $proyecto->token, 'archivo' => $proyecto->logo_cliente_archivo_id]) }}" class="h-12 bg-gray-100 rounded p-1">
                                    <span class="text-xs text-gray-500">Logo actual</span>
                                </div>
                            @endif
                            <input type="file" name="logo_cliente" accept="image/*,.svg" class="w-full text-sm border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP o SVG · max 5MB. Reemplaza el actual.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Video de bienvenida (Loom / YouTube)</label>
                            <input type="url" name="video_bienvenida_url" value="{{ old('video_bienvenida_url', $proyecto->video_bienvenida_url) }}"
                                   placeholder="https://www.loom.com/share/..." class="w-full border-gray-300 rounded-lg px-3 py-2">
                            <p class="text-xs text-gray-500 mt-1">Se muestra en la pantalla de bienvenida del cliente.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Notas internas (no visibles al cliente)</label>
                        <textarea name="notas_internas" rows="4" class="w-full border-gray-300 rounded-lg px-3 py-2">{{ old('notas_internas', $proyecto->notas_internas) }}</textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                        <a href="{{ route('agencia.onboardings.show', $proyecto) }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancelar</a>
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-2 rounded-lg">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
