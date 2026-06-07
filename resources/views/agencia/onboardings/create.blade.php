<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bs-card overflow-hidden">
                <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <h2 class="bs-display text-2xl text-white m-0">Nuevo onboarding</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">Generamos un portal personalizado para tu cliente</p>
                </div>

                <form method="POST" action="{{ route("agencia.onboardings.store") }}" class="p-6 space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Cliente</label>
                        <select name="agencia_cliente_id" required class="w-full border-gray-300 rounded-lg">
                            <option value="">— Selecciona un cliente —</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}{{ $c->rut ? " · ".$c->rut : "" }}</option>
                            @endforeach
                        </select>
                        @error("agencia_cliente_id") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Plantilla de onboarding</label>
                        <select name="plantilla_id" required class="w-full border-gray-300 rounded-lg">
                            <option value="">— Selecciona una plantilla —</option>
                            @foreach($plantillas as $pl)
                                <option value="{{ $pl->id }}">{{ $pl->nombre }} ({{ $pl->dias_habiles_estimados }} dias)</option>
                            @endforeach
                        </select>
                        @if($plantillas->isEmpty())
                            <p class="text-amber-600 text-sm mt-1">⚠ No hay plantillas activas. Crea una en /agencia/onboardings/plantillas.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Titulo</label>
                        <input type="text" name="titulo" required maxlength="255"
                               placeholder="Onboarding Shopify - Acme SpA"
                               class="w-full border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email del cliente (para enviar la invitacion)</label>
                        <input type="email" name="email_cliente" maxlength="255"
                               placeholder="contacto@cliente.cl"
                               class="w-full border-gray-300 rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Opcional ahora — podes agregarlo despues desde el detalle del onboarding.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Notas internas (opcional)</label>
                        <textarea name="notas_internas" rows="3" class="w-full border-gray-300 rounded-lg"
                                  placeholder="No visibles para el cliente"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Validez del link (dias)</label>
                        <input type="number" name="dias_validez_token" value="60" min="1" max="365"
                               class="w-32 border-gray-300 rounded-lg">
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                        <a href="{{ route("agencia.onboardings.index") }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancelar</a>
                        <button type="submit"
                                class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-2 rounded-lg">
                            Crear onboarding
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
