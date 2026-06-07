@props(['plantilla' => null, 'action', 'method' => 'POST'])

@php
    $secciones_json = old('secciones_json', $plantilla?->secciones ? json_encode($plantilla->secciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '');
@endphp

<form method="POST" action="{{ $action }}" class="p-6 space-y-5">
    @csrf
    @if(strtoupper($method) !== 'POST') @method($method) @endif

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre *</label>
        <input type="text" name="nombre" required maxlength="255"
               value="{{ old('nombre', $plantilla?->nombre) }}"
               placeholder="Diseño Web Shopify - Prototipo"
               class="w-full border-gray-300 rounded-lg">
        @error('nombre') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    @if(!$plantilla)
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Slug (auto si lo dejas vacío)</label>
        <input type="text" name="slug" maxlength="120"
               value="{{ old('slug') }}"
               placeholder="shopify-prototipo"
               class="w-full border-gray-300 rounded-lg">
        @error('slug') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de servicio *</label>
            <select name="tipo_servicio" required class="w-full border-gray-300 rounded-lg">
                @php
                    $tipos = [
                        'shopify_prototipo' => 'Shopify - Prototipo',
                        'shopify_produccion' => 'Shopify - Producción',
                        'meta_ads' => 'Campañas Meta Ads',
                        'seo_mensual' => 'SEO mensual',
                        'seo_auditoria' => 'SEO auditoría',
                        'mantencion' => 'Mantención Shopify',
                        'integracion' => 'Integración (Bsale, Lioren, etc.)',
                        'otro' => 'Otro',
                    ];
                @endphp
                @foreach($tipos as $val => $label)
                    <option value="{{ $val }}" {{ old('tipo_servicio', $plantilla?->tipo_servicio) === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Días hábiles estimados *</label>
            <input type="number" name="dias_habiles_estimados" required min="1" max="365"
                   value="{{ old('dias_habiles_estimados', $plantilla?->dias_habiles_estimados ?? 20) }}"
                   class="w-full border-gray-300 rounded-lg">
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción</label>
        <textarea name="descripcion" rows="2" maxlength="1000" class="w-full border-gray-300 rounded-lg"
                  placeholder="Para quién es esta plantilla, qué incluye">{{ old('descripcion', $plantilla?->descripcion) }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Secciones (JSON) *</label>
        <p class="text-xs text-gray-500 mb-2">Array de secciones del wizard. Cada sección con <code>key</code> y <code>titulo</code>. En Sprint 2 se enriquece con campos y tipos.</p>
        <textarea name="secciones_json" rows="14" required
                  class="w-full border-gray-300 rounded-lg font-mono text-xs"
                  placeholder='[{"key": "identidad_visual", "titulo": "Identidad visual"}, ...]'>{{ $secciones_json }}</textarea>
        @error('secciones_json') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1" class="rounded text-orange-500"
                   {{ old('activo', $plantilla?->activo ?? true) ? 'checked' : '' }}>
            <span class="text-sm font-semibold text-gray-700">Plantilla activa (disponible para crear onboardings)</span>
        </label>
    </div>

    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
        <a href="{{ route('agencia.onboardings.plantillas.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancelar</a>
        <button type="submit"
                class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-2 rounded-lg">
            {{ $plantilla ? 'Guardar cambios' : 'Crear plantilla' }}
        </button>
    </div>
</form>
