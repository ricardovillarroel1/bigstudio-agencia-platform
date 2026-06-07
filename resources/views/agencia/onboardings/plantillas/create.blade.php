<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bs-card overflow-hidden">
                <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <h2 class="bs-display text-2xl text-white m-0">Nueva plantilla de onboarding</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">Define las secciones que el cliente va a completar</p>
                </div>

                @include('agencia.onboardings.plantillas._form', [
                    'action' => route('agencia.onboardings.plantillas.store'),
                    'method' => 'POST',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
