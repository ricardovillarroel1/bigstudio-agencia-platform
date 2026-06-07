<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Configuraci&oacute;n</span> del Sistema
        </h2>
    </x-slot>

    <div style="max-width: 900px; margin: 2rem auto; padding: 0 1rem;">
        <!-- Header con gradiente BigStudio -->
        <div style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%); border-radius: 1rem; padding: 1.5rem 2rem; margin-bottom: 2rem;">
            <h1 class="bs-display" style="font-size: 1.75rem; color: white; margin: 0 0 0.25rem 0;">
                <i class="fas fa-cog"></i> Configuraci&oacute;n
            </h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 0.95rem; margin: 0;">
                Administra las configuraciones generales de la plataforma
            </p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div style="background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 0.75rem; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-check-circle" style="color: #059669; font-size: 1.25rem;"></i>
            <span style="color: #065f46; font-weight: 600;">{{ session('success') }}</span>
        </div>
        @endif

        <!-- Meta Pixel Card -->
        <div style="background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); overflow: hidden; border: 1px solid #e5e7eb;">
            <!-- Card Header -->
            <div style="background: linear-gradient(135deg, #1877F2 0%, #0d5bbf 100%); padding: 1.5rem 2rem; display: flex; align-items: center; gap: 1rem;">
                <div style="background: rgba(255,255,255,0.2); border-radius: 0.75rem; padding: 0.75rem; display: flex; align-items: center; justify-content: center;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
                        <path d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10 10 0 0 0 22 12.06C22 6.53 17.5 2.04 12 2.04Z"/>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin: 0;">
                        Meta Pixel (Facebook Ads)
                    </h2>
                    <p style="color: rgba(255,255,255,0.8); font-size: 0.875rem; margin: 0;">
                        Tracking de conversiones y audiencias
                    </p>
                </div>
            </div>

            <!-- Card Body -->
            <div style="padding: 2rem;">
                <!-- Info Banner -->
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 2rem;">
                    <div style="display: flex; gap: 0.75rem;">
                        <i class="fas fa-info-circle" style="color: #3b82f6; font-size: 1.25rem; margin-top: 0.125rem;"></i>
                        <div>
                            <p style="color: #1e40af; font-weight: 600; margin: 0 0 0.5rem 0;">
                                &iquest;Qu&eacute; es el Meta Pixel?
                            </p>
                            <p style="color: #1e40af; font-size: 0.875rem; margin: 0; line-height: 1.6;">
                                El Meta Pixel es un fragmento de c&oacute;digo que se instala en tu sitio web para medir la efectividad de tus anuncios en Facebook e Instagram. 
                                Al activarlo, podr&aacute;s rastrear cuando un cliente accede al perfil de usuario de la plataforma.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div style="margin-bottom: 2rem;">
                        <label for="meta_pixel_id" style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <i class="fas fa-hashtag" style="color: #FFC800;"></i> Pixel ID
                        </label>
                        <div style="position: relative;">
                            <input 
                                type="text" 
                                id="meta_pixel_id" 
                                name="meta_pixel_id" 
                                value="{{ old('meta_pixel_id', $metaPixelId) }}" 
                                placeholder="Ej: 123456789012345"
                                style="width: 100%; padding: 0.875rem 1rem 0.875rem 3rem; border: 2px solid #d1d5db; border-radius: 0.75rem; font-size: 1rem; font-family: 'Courier New', monospace; letter-spacing: 0.1em; transition: all 0.3s; outline: none; box-sizing: border-box;"
                                onfocus="this.style.borderColor='#1877F2'; this.style.boxShadow='0 0 0 3px rgba(24,119,242,0.1)'"
                                onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"
                                maxlength="20"
                            >
                            <i class="fab fa-facebook" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #1877F2; font-size: 1.25rem;"></i>
                        </div>
                        <p style="color: #9ca3af; font-size: 0.8rem; margin-top: 0.5rem;">
                            <i class="fas fa-question-circle"></i> 
                            Enc&uacute;entralo en <strong>Meta Events Manager</strong> &rarr; <strong>Data Sources</strong> &rarr; Tu Pixel &rarr; <strong>Pixel ID</strong>
                        </p>
                        @error('meta_pixel_id')
                        <p style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status Indicator -->
                    <div style="background: {{ $metaPixelId ? '#d1fae5' : '#fef3c7' }}; border: 1px solid {{ $metaPixelId ? '#6ee7b7' : '#fcd34d' }}; border-radius: 0.75rem; padding: 1rem 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                        @if($metaPixelId)
                            <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></div>
                            <span style="color: #065f46; font-weight: 600;">Pixel activo</span>
                            <span style="color: #065f46; font-size: 0.875rem;">&mdash; ID: {{ $metaPixelId }}</span>
                        @else
                            <div style="width: 12px; height: 12px; background: #f59e0b; border-radius: 50%;"></div>
                            <span style="color: #92400e; font-weight: 600;">Pixel no configurado</span>
                            <span style="color: #92400e; font-size: 0.875rem;">&mdash; Ingresa tu Pixel ID para activar el tracking</span>
                        @endif
                    </div>

                    <!-- Tracking Info -->
                    <div style="background: #f9fafb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 2rem; border: 1px solid #e5e7eb;">
                        <p style="font-weight: 700; color: #374151; margin: 0 0 0.75rem 0; font-size: 0.875rem; text-transform: uppercase;">
                            <i class="fas fa-crosshairs" style="color: #FFC800;"></i> Eventos que se rastrean:
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-check" style="color: #10b981; font-size: 0.75rem;"></i>
                                <span style="color: #4b5563; font-size: 0.875rem;"><strong>PageView</strong> &mdash; Visita a la plataforma</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-check" style="color: #10b981; font-size: 0.75rem;"></i>
                                <span style="color: #4b5563; font-size: 0.875rem;"><strong>ViewContent</strong> &mdash; Vista de planes</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-check" style="color: #10b981; font-size: 0.75rem;"></i>
                                <span style="color: #4b5563; font-size: 0.875rem;"><strong>Lead</strong> &mdash; Solicitud de plan</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-check" style="color: #f59e0b; font-size: 0.75rem;"></i>
                                <span style="color: #4b5563; font-size: 0.875rem;"><strong>Purchase</strong> &mdash; Pago de plan completado (conversi&oacute;n)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" style="flex: 1; padding: 1rem 2rem; background: linear-gradient(135deg, #FFC800 0%, #ff9800 100%); color: #000; font-weight: 700; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; border: none; border-radius: 0.75rem; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 6px rgba(255,200,0,0.3);"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(255,200,0,0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(255,200,0,0.3)'"
                        >
                            <i class="fas fa-save"></i> Guardar Configuraci&oacute;n
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Help Section -->
        <div style="margin-top: 2rem; background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem 2rem; border: 1px solid #e5e7eb;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #374151; margin: 0 0 1rem 0;">
                <i class="fas fa-book" style="color: #FFC800;"></i> &iquest;C&oacute;mo obtener tu Pixel ID?
            </h3>
            <ol style="color: #4b5563; font-size: 0.875rem; line-height: 2; margin: 0; padding-left: 1.25rem;">
                <li>Ingresa a <a href="https://business.facebook.com/events_manager" target="_blank" style="color: #1877F2; text-decoration: underline;">Meta Events Manager</a></li>
                <li>Selecciona tu cuenta publicitaria</li>
                <li>Haz clic en <strong>Data Sources</strong> (Fuentes de datos)</li>
                <li>Selecciona tu Pixel o crea uno nuevo</li>
                <li>Copia el <strong>Pixel ID</strong> (n&uacute;mero de 15-16 d&iacute;gitos)</li>
                <li>P&eacute;galo en el campo de arriba y guarda</li>
            </ol>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</x-app-layout>
