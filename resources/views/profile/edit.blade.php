<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Mi</span> Perfil
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Header BigStudio --}}
            <div class="bs-card overflow-hidden">
                <div style="height: 96px; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);"></div>
                <div class="px-8 pb-6">
                    <div class="flex flex-col items-center sm:flex-row sm:items-start gap-5">
                        {{-- Avatar con foto + boton de camara para cambiarla --}}
                        <form action="{{ route('profile.photo.update') }}" method="POST" enctype="multipart/form-data" class="relative shrink-0 -mt-10" id="bs-hero-photo-form">
                            @csrf
                            <div class="w-20 h-20 rounded-full bg-white border-4 border-white shadow-bs-card flex items-center justify-center overflow-hidden" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                                @if(auth()->user()->profile_photo_path)
                                    <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}" alt="Foto de perfil" class="w-full h-full object-cover">
                                @else
                                    <span class="bs-display text-3xl text-brand-700">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <label for="bs-hero-photo-input" title="Cambiar foto" class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-white shadow-bs-card border-2 border-white flex items-center justify-center cursor-pointer hover:bg-brand-50 transition-colors text-brand-600">
                                <i class="fas fa-camera text-xs"></i>
                            </label>
                            <input type="file" name="photo" id="bs-hero-photo-input" accept="image/*" class="hidden" onchange="if(this.files.length){document.getElementById('bs-hero-photo-form').submit();}">
                        </form>
                        <div class="flex-1 text-center sm:text-left sm:pt-3">
                            <h3 class="bs-display text-2xl text-gray-900 m-0 leading-tight">{{ auth()->user()->name }}</h3>
                            <p class="text-sm text-gray-500 mt-1 mb-2">{{ auth()->user()->email }}</p>
                            <div class="flex gap-2 flex-wrap justify-center sm:justify-start">
                                @if(auth()->user()->hasRole('admin'))
                                    <span class="bs-badge-brand">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                                        Administrador
                                    </span>
                                @else
                                    <span class="bs-badge-info">Cliente</span>
                                @endif
                                <span class="bs-badge-neutral">
                                    Miembro desde {{ auth()->user()->created_at->format('M Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="bs-card overflow-hidden">
                <div class="border-b border-gray-100 px-6 flex gap-1 overflow-x-auto" id="bs-profile-tabs">
                    <button type="button" data-tab="info" class="bs-profile-tab is-active">
                        <i class="fas fa-user-circle mr-2"></i> Informaci&oacute;n personal
                    </button>
                    @if(auth()->user()->role === 'cliente')
                    <button type="button" data-tab="billing" class="bs-profile-tab">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> Datos de facturaci&oacute;n
                    </button>
                    @endif
                    <button type="button" data-tab="security" class="bs-profile-tab">
                        <i class="fas fa-shield-alt mr-2"></i> Seguridad
                    </button>
                    <button type="button" data-tab="danger" class="bs-profile-tab">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Zona peligrosa
                    </button>
                </div>

                {{-- Paneles --}}
                <div class="p-6 sm:p-8">
                    <div class="bs-profile-panel is-active" data-panel="info">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    @if(auth()->user()->role === 'cliente')
                    <div class="bs-profile-panel" data-panel="billing">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-billing-information-form')
                        </div>
                    </div>
                    @endif

                    <div class="bs-profile-panel" data-panel="security">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <div class="bs-profile-panel" data-panel="danger">
                        <div class="max-w-2xl">
                            <div class="rounded-lg p-4 mb-6" style="background:#FEF2F2; border:1px solid #FECACA;">
                                <p class="text-sm font-semibold text-red-800 m-0 flex items-center gap-2">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Esta acci&oacute;n es irreversible
                                </p>
                                <p class="text-xs text-red-700 mt-1.5 mb-0">
                                    Una vez eliminada tu cuenta, todos sus datos ser&aacute;n borrados permanentemente.
                                    Descarga cualquier informaci&oacute;n que necesites antes de continuar.
                                </p>
                            </div>
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <style>
        .bs-profile-tab {
            background: transparent;
            border: none;
            padding: 14px 18px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #6B7280;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
        }
        .bs-profile-tab:hover {
            color: #FF8100;
            background: rgba(255, 200, 0, 0.05);
        }
        .bs-profile-tab.is-active {
            color: #FF8100;
            border-bottom-color: #FF8100;
        }
        .bs-profile-panel { display: none; }
        .bs-profile-panel.is-active { display: block; }

        /* Override de estilos Breeze para inputs/buttons dentro de los partials */
        .bs-profile-panel input[type="text"],
        .bs-profile-panel input[type="email"],
        .bs-profile-panel input[type="password"],
        .bs-profile-panel input[type="tel"],
        .bs-profile-panel select,
        .bs-profile-panel textarea {
            border-color: #E5E7EB !important;
            border-radius: 8px !important;
            transition: all 0.15s;
        }
        .bs-profile-panel input:focus,
        .bs-profile-panel select:focus,
        .bs-profile-panel textarea:focus {
            border-color: #FF8100 !important;
            box-shadow: 0 0 0 3px rgba(255, 129, 0, 0.15) !important;
        }
        .bs-profile-panel label {
            color: #374151 !important;
            font-weight: 600 !important;
        }
        /* Botones primarios dentro de partials -> naranja BigStudio
           (los botones de Breeze usan bg-gray-800; los pintamos brand) */
        .bs-profile-panel button[type="submit"]:not([class*="bg-red"]),
        .bs-profile-panel .inline-flex.items-center.px-4.py-2.bg-gray-800 {
            background: linear-gradient(135deg, #FF9C00 0%, #FF8100 100%) !important;
            border-color: transparent !important;
            color: #fff !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 2px rgba(255, 129, 0, 0.25);
            transition: all 0.15s;
        }
        .bs-profile-panel button[type="submit"]:not([class*="bg-red"]):hover,
        .bs-profile-panel .inline-flex.items-center.px-4.py-2.bg-gray-800:hover {
            background: linear-gradient(135deg, #FF8C00 0%, #E67400 100%) !important;
            box-shadow: 0 4px 12px rgba(255, 129, 0, 0.35) !important;
            transform: translateY(-1px);
        }
        /* Titulos h2 dentro de los partials con Mostin */
        .bs-profile-panel h2 {
            font-family: 'Mostin', system-ui, sans-serif !important;
            font-weight: 700 !important;
            font-size: 1.25rem !important;
            color: #111827 !important;
        }
    </style>

    <script>
    (function() {
        const tabs = document.querySelectorAll('.bs-profile-tab');
        const panels = document.querySelectorAll('.bs-profile-panel');

        // Restaurar tab desde URL hash o localStorage
        const initialTab = (location.hash.replace('#', '') || localStorage.getItem('bs_profile_tab') || 'info');
        activate(initialTab);

        tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.tab)));

        function activate(tabId) {
            let foundActive = false;
            tabs.forEach(t => {
                const active = t.dataset.tab === tabId;
                t.classList.toggle('is-active', active);
                if (active) foundActive = true;
            });
            if (!foundActive) tabId = 'info';
            panels.forEach(p => p.classList.toggle('is-active', p.dataset.panel === tabId));
            localStorage.setItem('bs_profile_tab', tabId);
            history.replaceState(null, '', '#' + tabId);
        }
    })();
    </script>
</x-app-layout>
