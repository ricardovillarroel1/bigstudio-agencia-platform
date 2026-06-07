<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planes Disponibles') }}
        </h2>
    </x-slot>

    <style>
        .plan-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            position: relative;
        }

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.2);
        }

        .plan-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%);
        }
        .plan-card.plan-activo {
            border: 3px solid #10b981;
            box-shadow: 0 10px 30px -5px rgba(16, 185, 129, 0.3);
        }
        .plan-card.plan-activo::before {
            height: 6px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .plan-card.plan-activo:hover {
            box-shadow: 0 20px 40px -5px rgba(16, 185, 129, 0.4);
        }
        .plan-activo-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.875rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-weight: 800;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            animation: pulseGlow 2s ease-in-out infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 4px 20px rgba(16, 185, 129, 0.7); }
        }
        .plan-activo-info {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #a7f3d0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .plan-activo-info p {
            color: #065f46;
            font-weight: 600;
            font-size: 0.8rem;
            margin: 0;
        }

        .plan-header {
            padding: 2rem;
            text-align: center;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        }

        .plan-empresa {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%);
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-radius: 9999px;
            margin-bottom: 1rem;
        }

        .plan-nombre {
            font-size: 1.75rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .plan-precio {
            font-size: 3rem;
            font-weight: 900;
            color: #FFC800;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .plan-precio-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 600;
        }

        .plan-precio-uf {
            font-size: 0.8rem;
            color: #9ca3af;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .plan-body {
            padding: 2rem;
        }

        .plan-descripcion {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .caracteristica-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .caracteristica-item:hover {
            background: #f3f4f6;
            transform: translateX(5px);
        }

        .caracteristica-icon {
            color: #10b981;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .btn-solicitar {
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%);
            color: #000;
            font-weight: 800;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            box-shadow: 0 10px 25px -5px rgba(255, 193, 7, 0.4);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-solicitar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-solicitar:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(255, 193, 7, 0.6);
        }

        .btn-solicitar:hover::before {
            left: 100%;
        }

        .btn-solicitar:active {
            transform: translateY(0);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .uf-info-banner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .uf-info-banner p {
            color: #92400e;
            font-weight: 600;
            font-size: 0.875rem;
            margin: 0;
        }

        /* Toggle Mensual/Anual */
        .billing-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin: 2rem auto;
            padding: 0.5rem;
            background: #f3f4f6;
            border-radius: 9999px;
            max-width: 400px;
        }
        .billing-toggle-btn {
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            border: none;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
        }
        .billing-toggle-btn.active {
            background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%);
            color: #1a1a1a;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }
        .billing-toggle-btn:not(.active) {
            background: transparent;
            color: #6b7280;
        }
        .billing-toggle-btn:not(.active):hover {
            background: #e5e7eb;
            color: #374151;
        }
        .descuento-badge {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            margin-left: 0.5rem;
            vertical-align: middle;
            box-shadow: 0 2px 6px rgba(16,185,129,0.4);
            animation: bsPulseBadge 2s ease-in-out infinite;
        }
        @keyframes bsPulseBadge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        /* Banner de ahorro al activar Anual */
        .bs-anual-banner {
            display: none;
            max-width: 560px;
            margin: 0 auto 1.5rem;
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
            border: 1px solid #6EE7B7;
            border-radius: 1rem;
            text-align: center;
            color: #065F46;
            font-size: 0.95rem;
            font-weight: 600;
            animation: bsSlideDown 0.35s ease-out;
        }
        .bs-anual-banner i { color: #059669; margin-right: 0.4rem; }
        @keyframes bsSlideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Ahorro por tarjeta (modo anual) */
        .plan-ahorro-anual {
            display: none;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #059669;
        }
        .plan-ahorro-anual i { margin-right: 0.25rem; }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 1rem;">
                <h1 style="font-size: 2.5rem; font-weight: 800; color: #111827; margin-bottom: 1rem;">
                    Elige el Plan Perfecto para tu Negocio
                </h1>
                <p style="font-size: 1.125rem; color: #6b7280; max-width: 600px; margin: 0 auto;">
                    Conecta tu tienda con las mejores plataformas de gesti&oacute;n empresarial
                </p>
            </div>

            <!-- Toggle Mensual / Anual -->
            <div class="billing-toggle" id="billingToggle">
                <button class="billing-toggle-btn active" id="btnMensual" onclick="cambiarPeriodo('mensual')">
                    <i class="fas fa-calendar-day"></i> Mensual
                </button>
                <button class="billing-toggle-btn" id="btnAnual" onclick="cambiarPeriodo('anual')">
                    <i class="fas fa-calendar-alt"></i> Anual <span class="descuento-badge">Ahorra hasta 2 meses</span>
                </button>
            </div>

            <!-- Banner de ahorro (visible al elegir Anual) -->
            <div class="bs-anual-banner" id="bsAnualBanner">
                <i class="fas fa-gift"></i> ¡Con el plan <strong>Anual</strong> pagas menos y aseguras tu precio todo el año!
            </div>

            @if($planes->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
                    No hay planes disponibles
                </h3>
                <p style="color: #6b7280;">
                    Pronto tendremos nuevos planes para ti
                </p>
            </div>
            @else
            <!-- Grid de Planes -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
                @foreach($planes as $plan)
                <div class="plan-card {{ isset($planActivoId) && $planActivoId == $plan->id ? 'plan-activo' : '' }}"
                     data-precio-mensual="{{ $plan->precio_clp }}"
                     data-precio-anual="{{ $plan->precio_anual_clp ?? '' }}"
                     data-plan-anual-activo="{{ $plan->plan_anual_activo ? '1' : '0' }}"
                     data-descuento="{{ $plan->descuento_anual ?? 0 }}">
                    @if(isset($planActivoId) && $planActivoId == $plan->id)
                    <div class="plan-activo-badge">
                        <i class="fas fa-check-circle"></i> TU PLAN ACTUAL
                    </div>
                    @endif
                    <!-- Header del Plan -->
                    <div class="plan-header">
                        <div class="plan-empresa">
                            <i class="fas fa-building"></i> {{ $plan->empresa->nombre }}
                        </div>
                        <h3 class="plan-nombre">{{ $plan->nombre }}</h3>
                        <div class="plan-precio plan-precio-display">
                            ${{ number_format($plan->precio_clp, 0, ',', '.') }}
                        </div>
                        <div class="plan-precio-label plan-periodo-label">CLP / mes</div>
                        @if($plan->plan_anual_activo && $plan->descuento_anual)
                        <div class="plan-descuento-info" style="display: none; margin-top: 0.5rem;">
                            <span style="background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 9999px;">
                                <i class="fas fa-tag"></i> {{ $plan->descuento_anual }}% descuento
                            </span>
                        </div>
                        @endif
                        @if($plan->plan_anual_activo && $plan->precio_anual_clp)
                        @php $ahorroAnual = max(0, ($plan->precio_clp * 12) - $plan->precio_anual_clp); @endphp
                        <div class="plan-ahorro-anual" data-ahorro="{{ $ahorroAnual }}">
                            <i class="fas fa-piggy-bank"></i> Ahorras ${{ number_format($ahorroAnual, 0, ',', '.') }} al año
                        </div>
                        @endif
                    </div>

                    <!-- Body del Plan -->
                    <div class="plan-body">
                        <p class="plan-descripcion" data-original="{{ $plan->descripcion }}">{{ $plan->descripcion }}</p>

                        <!-- Caracter&iacute;sticas -->
                        <div style="margin-bottom: 2rem;">
                            <h4 style="font-size: 0.875rem; font-weight: 700; color: #111827; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;">
                                <i class="fas fa-check-circle" style="color: #10b981;"></i> Incluye:
                            </h4>
                            @php
                                $caracList = is_array($plan->caracteristicas) ? $plan->caracteristicas : (is_string($plan->caracteristicas) ? json_decode($plan->caracteristicas, true) ?? [] : []);
                                $tieneBoletasEnCarac = false;
                                foreach ($caracList as $c) {
                                    if (stripos($c, 'boletas') !== false) {
                                        $tieneBoletasEnCarac = true;
                                        break;
                                    }
                                }
                            @endphp
                            @foreach($caracList as $caracteristica)
                            <div class="caracteristica-item">
                                <i class="fas fa-check-circle caracteristica-icon"></i>
                                <span style="color: #374151; font-weight: 500;">{{ $caracteristica }}</span>
                            </div>
                            @endforeach
                            @if($plan->boletas_enabled && !$tieneBoletasEnCarac)
                            <div class="caracteristica-item">
                                <i class="fas fa-check-circle caracteristica-icon"></i>
                                <span style="color: #374151; font-weight: 500;">&#x1F9FE; Emisi&oacute;n de boletas electr&oacute;nicas</span>
                            </div>
                            @endif
                        </div>

                        <!-- Botones de Acci&oacute;n -->
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <button onclick="verInformacion({{ $plan->id }}, '{{ $plan->nombre }}', '{{ addslashes($plan->descripcion) }}', '{{ $plan->empresa->nombre }}', {{ $plan->precio_clp }}, 'CLP', {{ json_encode($plan->caracteristicas) }}, {{ $plan->precio_original_uf ?? 'null' }})" style="flex: 1; padding: 0.75rem; background: #f3f4f6; color: #374151; font-weight: 700; font-size: 0.875rem; text-transform: uppercase; border: 2px solid #d1d5db; border-radius: 0.75rem; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#e5e7eb'; this.style.borderColor='#9ca3af'" onmouseout="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db'">
                                <i class="fas fa-info-circle"></i> M&aacute;s Info
                            </button>
                        </div>
     
                        @if(isset($planActivoId) && $planActivoId == $plan->id)
                        <div class="plan-activo-info">
                            <p><i class="fas fa-check-circle" style="color: #10b981;"></i> Este es tu plan activo @if(isset($suscripcionActiva) && $suscripcionActiva->fecha_fin) &mdash; Vence: {{ \Carbon\Carbon::parse($suscripcionActiva->fecha_fin)->format('d/m/Y') }} @endif</p>
                        </div>
                        <a href="{{ route('cliente.planes-activos') }}" style="display: block; width: 100%; padding: 1rem 2rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-weight: 800; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; border: none; border-radius: 0.75rem; cursor: pointer; text-align: center; text-decoration: none; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4); transition: all 0.3s;">
                            <i class="fas fa-eye"></i> Ver Mi Suscripci&oacute;n
                        </a>
                        @else
                        <button onclick="solicitarPlan({{ $plan->id }}, '{{ addslashes($plan->nombre) }}', '{{ addslashes($plan->empresa->nombre) }}')" class="btn-solicitar">
                            <i class="fas fa-paper-plane"></i> Solicitar Plan
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Informaci&oacute;n Adicional -->
            <div style="margin-top: 4rem; padding: 2rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 1rem; text-align: center;">
                <h3 style="font-size: 1.5rem; font-weight: 700; color: #1e40af; margin-bottom: 1rem;">
                    <i class="fas fa-info-circle"></i> &iquest;Necesitas ayuda?
                </h3>
                <p style="color: #1e40af; font-size: 1.125rem;">
                    Nuestro equipo est&aacute; listo para ayudarte a elegir el mejor plan para tu negocio
                </p>
            </div>
        </div>
    </div>

    <!-- Modal de Solicitud -->
    <div id="solicitudModal" style="display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
        <div style="background: white; margin: 5% auto; padding: 0; border-radius: 1rem; width: 90%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideDown 0.3s ease;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%); padding: 2rem; border-radius: 1rem 1rem 0 0; text-align: center;">
                <i class="fas fa-paper-plane" style="font-size: 3rem; color: #1a1a1a; margin-bottom: 1rem;"></i>
                <h3 style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin: 0;">Solicitar Plan</h3>
            </div>

            <!-- Contenido -->
            <div style="padding: 2rem;">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <p style="font-size: 1.125rem; color: #374151; margin-bottom: 0.5rem;">
                        Est&aacute;s solicitando el plan:
                    </p>
                    <h4 id="solicitudPlanNombre" style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;"></h4>
                    <p id="solicitudEmpresa" style="color: #6b7280;"></p>
                </div>

                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; border-left: 4px solid #FFC800;">
                    <p style="color: #374151; line-height: 1.6; margin: 0;">
                        <i class="fas fa-info-circle" style="color: #FFC800;"></i>
                        Tu solicitud ser&aacute; revisada por nuestro equipo y nos pondremos en contacto contigo pronto.
                    </p>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: 1rem;">
                    <button onclick="closeSolicitudModal()" style="flex: 1; padding: 0.75rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                        Cancelar
                    </button>
                    <button onclick="confirmarSolicitud()" style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(248, 184, 0, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Informaci&oacute;n -->
    <div id="infoModal" style="display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
        <div style="background: white; margin: 2% auto; padding: 0; border-radius: 1rem; width: 90%; max-width: 700px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideDown 0.3s ease;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%); padding: 2rem; border-radius: 1rem 1rem 0 0; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: rgba(0,0,0,0.1); padding: 0.75rem; border-radius: 0.75rem;">
                        <i class="fas fa-info-circle" style="font-size: 2rem; color: #1a1a1a;"></i>
                    </div>
                    <h3 id="infoModalTitle" style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin: 0;"></h3>
                </div>
                <button onclick="closeInfoModal()" style="color: #1a1a1a; background: rgba(0,0,0,0.1); border: none; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.2)'" onmouseout="this.style.background='rgba(0,0,0,0.1)'">
                    <i class="fas fa-times" style="font-size: 1.5rem;"></i>
                </button>
            </div>

            <!-- Contenido -->
            <div style="padding: 2rem;">
                <!-- Empresa y Precio -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; border-left: 4px solid #FFC800;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.5rem;">
                            <i class="fas fa-building"></i> Empresa
                        </div>
                        <div id="infoEmpresa" style="font-size: 1rem; font-weight: 600; color: #111827;"></div>
                    </div>
                    <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; border-left: 4px solid #10b981;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.5rem;">
                            <i class="fas fa-dollar-sign"></i> Precio
                        </div>
                        <div id="infoPrecio" style="font-size: 1.5rem; font-weight: 700; color: #10b981;"></div>
                    </div>
                </div>

                <!-- Descripci&oacute;n -->
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        <i class="fas fa-align-left"></i> Descripci&oacute;n
                    </h4>
                    <p id="infoDescripcion" style="color: #374151; line-height: 1.6;"></p>
                </div>

                <!-- Caracter&iacute;sticas -->
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFC800;">
                        <i class="fas fa-list-check"></i> Caracter&iacute;sticas Incluidas
                    </h4>
                    <ul id="infoCaracteristicas" style="list-style: none; padding: 0; margin: 0;"></ul>
                </div>

                <!-- Opciones de Contacto -->
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem; text-align: center;">
                        <i class="fas fa-comments"></i> &iquest;Necesitas m&aacute;s informaci&oacute;n?
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <!-- Chat Interno -->
                        <button onclick="abrirChatInterno()" style="padding: 1.5rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; font-weight: 700; border: none; border-radius: 1rem; cursor: pointer; box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4); transition: all 0.3s; display: flex; flex-direction: column; align-items: center; gap: 0.75rem;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 35px -5px rgba(59, 130, 246, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px -5px rgba(59, 130, 246, 0.4)'">
                            <i class="fas fa-comments" style="font-size: 2.5rem;"></i>
                            <span style="font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em;">Chat Interno</span>
                            <span style="font-size: 0.75rem; opacity: 0.9;">Contacta con un asesor en l&iacute;nea</span>
                        </button>

                        <!-- WhatsApp -->
                        <button onclick="abrirWhatsApp()" style="padding: 1.5rem; background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); color: white; font-weight: 700; border: none; border-radius: 1rem; cursor: pointer; box-shadow: 0 10px 25px -5px rgba(37, 211, 102, 0.4); transition: all 0.3s; display: flex; flex-direction: column; align-items: center; gap: 0.75rem;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 35px -5px rgba(37, 211, 102, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px -5px rgba(37, 211, 102, 0.4)'">
                            <i class="fab fa-whatsapp" style="font-size: 2.5rem;"></i>
                            <span style="font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em;">WhatsApp</span>
                            <span style="font-size: 0.75rem; opacity: 0.9;">Contacto directo</span>
                        </button>
                    </div>
                </div>

                <!-- Botones de Acci&oacute;n -->
                <div style="display: flex; gap: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <button onclick="closeInfoModal()" style="flex: 1; padding: 0.75rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                        Cerrar
                    </button>
                    <button onclick="solicitarDesdeInfo()" style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(248, 184, 0, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'">
                        <i class="fas fa-paper-plane"></i> Solicitar Plan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Wizard de Solicitud -->
    <div id="wizardModal" style="display: none; position: fixed; z-index: 60; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
        <div style="background: white; margin: 2% auto; padding: 0; border-radius: 1rem; width: 90%; max-width: 700px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">

            <!-- Paso 1: Confirmaci&oacute;n -->
            <div id="wizardStep1" class="wizard-step">
                <div style="background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%); padding: 2rem; border-radius: 1rem 1rem 0 0; text-align: center;">
                    <i class="fas fa-clipboard-check" style="font-size: 3rem; color: #1a1a1a; margin-bottom: 1rem;"></i>
                    <h3 style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a;">Confirmar Selecci&oacute;n de Plan</h3>
                </div>
                <div style="padding: 2rem;">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <p style="font-size: 1.125rem; color: #374151; margin-bottom: 0.5rem;">Has seleccionado:</p>
                        <h4 id="wizardPlanNombre" style="font-size: 1.75rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;"></h4>
                        <p id="wizardEmpresa" style="color: #6b7280; font-size: 1rem;"></p>
                    </div>
                    <div style="background: #dbeafe; padding: 1.5rem; border-radius: 0.75rem; border-left: 4px solid #3b82f6; margin-bottom: 2rem;">
                        <p style="color: #1e40af; line-height: 1.6; margin: 0;">
                            <i class="fas fa-info-circle"></i> Tu solicitud ser&aacute; revisada por nuestro equipo y nos pondremos en contacto contigo pronto.
                        </p>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button onclick="document.getElementById('wizardModal').style.display='none'" style="flex: 1; padding: 0.75rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer;">Cancelar</button>
                        <button onclick="siguienteWizard()" style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer;">Siguiente <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>

            <!-- Paso 2: Pago -->
            <div id="wizardStep2" class="wizard-step" style="display: none;">
                <div style="background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%); padding: 2rem; border-radius: 1rem 1rem 0 0; text-align: center;">
                    <i class="fas fa-credit-card" style="font-size: 3rem; color: #1a1a1a; margin-bottom: 1rem;"></i>
                    <h3 style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a;">Proceder al Pago</h3>
                </div>
                <div style="padding: 2rem;">
                    <!-- Resumen del Plan -->
                    <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; border-left: 4px solid #FFC800;">
                        <h4 style="font-size: 1rem; font-weight: 700; color: #111827; margin-bottom: 1rem;">Resumen de tu compra</h4>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6b7280;">Plan:</span>
                            <span id="wizardResumenPlan" style="font-weight: 600; color: #111827;"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6b7280;">Empresa:</span>
                            <span id="wizardResumenEmpresa" style="font-weight: 600; color: #111827;"></span>
                        </div>
                        <div style="border-top: 2px solid #e5e7eb; margin: 1rem 0; padding-top: 1rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-size: 1.125rem; font-weight: 700; color: #111827;">Total:</span>
                                <span id="wizardResumenPrecio" style="font-size: 1.5rem; font-weight: 700; color: #FFC800;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs de m&eacute;todo de pago -->
                    <div style="display: flex; gap: 0; margin-bottom: 1.5rem; border-radius: 0.5rem; overflow: hidden; border: 2px solid #e5e7eb;">
                        <button id="tabFlow" onclick="cambiarMetodoPago('flow')" style="flex: 1; padding: 0.75rem; background: #10b981; color: white; font-weight: 700; border: none; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-credit-card"></i> Pagar con Flow
                        </button>
                        <button id="tabTransferencia" onclick="cambiarMetodoPago('transferencia')" style="flex: 1; padding: 0.75rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-university"></i> Transferencia
                        </button>
                    </div>

                    <!-- Contenido Flow -->
                    <div id="pagoFlowContent">
                        <div style="background: #dbeafe; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; border-left: 4px solid #3b82f6;">
                            <p style="color: #1e40af; line-height: 1.6; margin: 0;">
                                <i class="fas fa-shield-alt"></i> Ser&aacute;s redirigido a Flow para completar tu pago de forma segura. Una vez confirmado el pago, podr&aacute;s configurar tu integraci&oacute;n.
                            </p>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button onclick="atrasWizard()" style="flex: 1; padding: 0.75rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer;"><i class="fas fa-arrow-left"></i> Atr&aacute;s</button>
                            <button onclick="procesarPago()" style="flex: 2; padding: 0.75rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);"><i class="fas fa-lock"></i> Pagar con Flow</button>
                        </div>
                    </div>

                    <!-- Contenido Transferencia -->
                    <div id="pagoTransferenciaContent" style="display: none;">
                        <div style="background: #fef3c7; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border-left: 4px solid #f59e0b;">
                            <h4 style="font-size: 0.95rem; font-weight: 700; color: #92400e; margin-bottom: 0.75rem;"><i class="fas fa-university"></i> Datos para Transferencia Bancaria</h4>
                            <div style="display: grid; gap: 0.5rem; font-size: 0.9rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #78350f; font-weight: 600;">Empresa:</span>
                                    <span style="color: #451a03; font-weight: 700;">Big Studio SpA</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #78350f; font-weight: 600;">RUT:</span>
                                    <span style="color: #451a03; font-weight: 700;">78.153.109-K</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #78350f; font-weight: 600;">Banco:</span>
                                    <span style="color: #451a03; font-weight: 700;">Bci</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #78350f; font-weight: 600;">Cuenta Corriente:</span>
                                    <span style="color: #451a03; font-weight: 700;">97580848</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #78350f; font-weight: 600;">Correo:</span>
                                    <span style="color: #451a03; font-weight: 700;">hola@bigstudio.cl</span>
                                </div>
                            </div>
                        </div>

                        <!-- Subir comprobante -->
                        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border: 2px dashed #86efac;">
                            <h4 style="font-size: 0.95rem; font-weight: 700; color: #166534; margin-bottom: 0.75rem;"><i class="fas fa-upload"></i> Subir Comprobante de Pago</h4>
                            <p style="color: #15803d; font-size: 0.85rem; margin-bottom: 1rem;">Una vez realizada la transferencia, sube tu comprobante para que podamos verificar el pago.</p>
                            <div id="dropZone" onclick="document.getElementById('comprobanteInput').click()" style="border: 2px dashed #a7f3d0; border-radius: 0.5rem; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s; background: white;">
                                <input type="file" id="comprobanteInput" accept="image/*,.pdf" style="display: none;" onchange="previewComprobante(this)">
                                <div id="dropZoneContent">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #86efac; margin-bottom: 0.5rem;"></i>
                                    <p style="color: #6b7280; margin: 0; font-size: 0.9rem;">Haz clic o arrastra tu comprobante aqu&iacute;</p>
                                    <p style="color: #9ca3af; margin: 0.25rem 0 0; font-size: 0.8rem;">Formatos: JPG, PNG, PDF (m&aacute;x. 5MB)</p>
                                </div>
                                <div id="previewContent" style="display: none;">
                                    <i class="fas fa-check-circle" style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem;"></i>
                                    <p id="previewFileName" style="color: #111827; font-weight: 600; margin: 0;"></p>
                                    <p style="color: #10b981; margin: 0.25rem 0 0; font-size: 0.8rem;">Archivo seleccionado correctamente</p>
                                </div>
                            </div>
                        </div>

                        <div style="background: #dbeafe; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6;">
                            <p style="color: #1e40af; line-height: 1.5; margin: 0; font-size: 0.85rem;">
                                <i class="fas fa-info-circle"></i> Una vez enviado el comprobante, nuestro equipo verificar&aacute; el pago y activar&aacute; tu plan. Este proceso puede tomar hasta 24 horas h&aacute;biles.
                            </p>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button onclick="atrasWizard()" style="flex: 1; padding: 0.75rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer;"><i class="fas fa-arrow-left"></i> Atr&aacute;s</button>
                            <button id="btnEnviarComprobante" onclick="enviarComprobante()" disabled style="flex: 2; padding: 0.75rem; background: #d1d5db; color: #6b7280; font-weight: 700; border: none; border-radius: 0.5rem; cursor: not-allowed; transition: all 0.3s;"><i class="fas fa-paper-plane"></i> Enviar Comprobante</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paso 3: &Eacute;xito -->
            <div id="wizardStep3" class="wizard-step" style="display: none;">
                <div style="padding: 3rem; text-align: center;">
                    <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
                        <i class="fas fa-check" style="font-size: 3rem; color: #065f46;"></i>
                    </div>
                    <h3 style="font-size: 2rem; font-weight: 700; color: #111827; margin-bottom: 1rem;">&iexcl;Pago Confirmado!</h3>
                    <p style="font-size: 1.125rem; color: #6b7280; line-height: 1.6; margin-bottom: 2rem;">
                        Tu pago ha sido procesado exitosamente. Ahora puedes configurar tu integraci&oacute;n en la secci&oacute;n <strong>"Planes Activos"</strong>.
                    </p>
                    <div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; border-left: 4px solid #f59e0b;">
                        <p style="color: #92400e; margin: 0;">
                            <i class="fas fa-info-circle"></i> Te enviaremos un correo con los pr&oacute;ximos pasos para completar la configuraci&oacute;n de tu tienda.
                        </p>
                    </div>
                    <button onclick="cerrarWizard()" style="padding: 1rem 2rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.75rem; cursor: pointer; font-size: 1rem; text-transform: uppercase;">
                        <i class="fas fa-check"></i> Ir a Planes Activos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let planIdSeleccionado = null;
        let planNombreTemp = '';
        let empresaTemp = '';
        let periodoActual = 'mensual';

        function formatCLP(numero) {
            return '$' + Number(numero).toLocaleString('es-CL');
        }

        // Toggle Mensual / Anual
        function cambiarPeriodo(periodo) {
            periodoActual = periodo;
            const btnMensual = document.getElementById('btnMensual');
            const btnAnual = document.getElementById('btnAnual');

            if (periodo === 'mensual') {
                btnMensual.classList.add('active');
                btnAnual.classList.remove('active');
            } else {
                btnAnual.classList.add('active');
                btnMensual.classList.remove('active');
            }

            // Banner de ahorro global (visible solo en Anual)
            const bsBanner = document.getElementById('bsAnualBanner');
            if (bsBanner) bsBanner.style.display = (periodo === 'anual') ? 'block' : 'none';

            // Update all plan cards
            document.querySelectorAll('.plan-card').forEach(function(card) {
                const precioMensual = parseInt(card.dataset.precioMensual) || 0;
                const precioAnual = parseInt(card.dataset.precioAnual) || 0;
                const anualActivo = card.dataset.planAnualActivo === '1';
                const descuento = parseInt(card.dataset.descuento) || 0;
                const precioDisplay = card.querySelector('.plan-precio-display');
                const periodoLabel = card.querySelector('.plan-periodo-label');
                const descuentoInfo = card.querySelector('.plan-descuento-info');
                const ahorroBox = card.querySelector('.plan-ahorro-anual');

                if (periodo === 'anual' && anualActivo && precioAnual > 0) {
                    precioDisplay.textContent = formatCLP(precioAnual);
                    periodoLabel.textContent = 'CLP / año';
                    const descLabel = card.querySelector('.plan-descripcion');
                    if (descLabel) descLabel.textContent = 'Pago anual';
                    if (descuentoInfo) descuentoInfo.style.display = 'block';
                    if (ahorroBox) ahorroBox.style.display = 'block';
                } else {
                    precioDisplay.textContent = formatCLP(precioMensual);
                    periodoLabel.textContent = 'CLP / mes';
                    const descLabelM = card.querySelector('.plan-descripcion');
                    if (descLabelM) descLabelM.textContent = descLabelM.dataset.original || descLabelM.textContent;
                    if (descuentoInfo) descuentoInfo.style.display = 'none';
                    if (ahorroBox) ahorroBox.style.display = 'none';
                }
            });
        }

        function verInformacion(planId, nombre, descripcion, empresa, precio, moneda, caracteristicas, precioUF) {
            planIdSeleccionado = planId;
            planNombreTemp = nombre;
            empresaTemp = empresa;

            document.getElementById('infoModalTitle').textContent = nombre;
            document.getElementById('infoEmpresa').textContent = empresa;
            document.getElementById('infoPrecio').textContent = formatCLP(precio) + ' CLP/mes';
            document.getElementById('infoDescripcion').textContent = descripcion;

            // Caracter&iacute;sticas
            const caracteristicasList = document.getElementById('infoCaracteristicas');
            caracteristicasList.innerHTML = '';
            caracteristicas.forEach(car => {
                const li = document.createElement('li');
                li.style.cssText = 'padding: 0.75rem; background: #f9fafb; border-radius: 0.5rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem;';
                li.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981; font-size: 1.25rem;"></i><span style="color: #374151;">' + car + '</span>';
                caracteristicasList.appendChild(li);
            });

            document.getElementById('infoModal').style.display = 'block';
        }

        function closeInfoModal() {
            document.getElementById('infoModal').style.display = 'none';
        }

        function solicitarDesdeInfo() {
            closeInfoModal();
            solicitarPlan(planIdSeleccionado, planNombreTemp, empresaTemp);
        }

        function abrirChatInterno() {
            closeInfoModal();
            fetch('{{ route("cliente.chats.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_id: planIdSeleccionado
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/chats/' + data.chat_id;
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function abrirWhatsApp() {
            const mensaje = 'Hola! Me interesa obtener m\u00e1s informaci\u00f3n sobre el plan "' + planNombreTemp + '" de ' + empresaTemp + '.';
            const numeroWhatsApp = '56932141504';
            const url = 'https://wa.me/' + numeroWhatsApp + '?text=' + encodeURIComponent(mensaje);
            window.open(url, '_blank');
        }

        function solicitarPlan(planId, planNombre, empresa) {
            console.log('solicitarPlan llamado con:', planId, planNombre, empresa);
            planIdSeleccionado = planId;
            planNombreTemp = planNombre;
            empresaTemp = empresa;
            document.getElementById('solicitudPlanNombre').textContent = planNombre;
            document.getElementById('solicitudEmpresa').textContent = empresa;
            document.getElementById('solicitudModal').style.display = 'block';
        }

        function closeSolicitudModal() {
            document.getElementById('solicitudModal').style.display = 'none';
        }

        function confirmarSolicitud() {
            closeSolicitudModal();
            abrirWizard();
        }

        // Wizard de 3 pasos
        let wizardStep = 1;
        let wizardData = {};

        function abrirWizard() {
            wizardStep = 1;

            const planes = @json($planes);
            const planSeleccionado = planes.find(p => p.id === planIdSeleccionado);

            // Determinar precio segun periodo seleccionado (mensual o anual)
            let precioWizard = planSeleccionado ? planSeleccionado.precio_clp : 0;
            if (periodoActual === 'anual' && planSeleccionado && planSeleccionado.precio_anual_clp > 0) {
                precioWizard = planSeleccionado.precio_anual_clp;
            }

            wizardData = {
                plan_id: planIdSeleccionado,
                plan_nombre: planNombreTemp,
                empresa: empresaTemp,
                precio: precioWizard,
                precio_original_uf: planSeleccionado ? planSeleccionado.precio_original_uf : null,
                moneda: 'CLP',
                periodo: periodoActual
            };

            document.getElementById('wizardModal').style.display = 'block';
            mostrarPasoWizard(1);
        }

        function mostrarPasoWizard(paso) {
            document.querySelectorAll('.wizard-step').forEach(el => el.style.display = 'none');
            document.getElementById('wizardStep' + paso).style.display = 'block';

            if (paso === 1) {
                document.getElementById('wizardPlanNombre').textContent = wizardData.plan_nombre;
                document.getElementById('wizardEmpresa').textContent = wizardData.empresa;
            } else if (paso === 2) {
                document.getElementById('wizardResumenPlan').textContent = wizardData.plan_nombre;
                document.getElementById('wizardResumenEmpresa').textContent = wizardData.empresa;
                const periodoTexto = wizardData.periodo === 'anual' ? 'CLP/anual' : 'CLP/mes';
                document.getElementById('wizardResumenPrecio').textContent = formatCLP(wizardData.precio) + ' ' + periodoTexto;
            }
        }

        function siguienteWizard() {
            if (wizardStep < 3) {
                wizardStep++;
                mostrarPasoWizard(wizardStep);
            }
        }

        function atrasWizard() {
            if (wizardStep > 1) {
                wizardStep--;
                mostrarPasoWizard(wizardStep);
            }
        }

        let procesandoPago = false;

        function procesarPago() {
            if (procesandoPago) return;

            procesandoPago = true;
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

            if (!wizardData.plan_id) {
                alert('Error: No se ha seleccionado un plan v\u00e1lido');
                procesandoPago = false;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-lock"></i> Pagar con Flow';
                return;
            }

            const formData = {
                plan_id: parseInt(wizardData.plan_id),
                periodo: wizardData.periodo || 'mensual',
            };

            fetch('{{ route("flow.create-plan-payment") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo procesar el pago'));
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    let mensaje = 'Error al procesar el pago';
                    if (error.errors) {
                        mensaje = Object.values(error.errors).flat().join('\n');
                    } else if (error.message) {
                        mensaje = error.message;
                    }
                    alert(mensaje);
                })
                .finally(() => {
                    procesandoPago = false;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-lock"></i> Pagar con Flow';
                });
        }

        function cambiarMetodoPago(metodo) {
            const tabFlow = document.getElementById('tabFlow');
            const tabTransferencia = document.getElementById('tabTransferencia');
            const flowContent = document.getElementById('pagoFlowContent');
            const transferenciaContent = document.getElementById('pagoTransferenciaContent');

            if (metodo === 'flow') {
                tabFlow.style.background = '#10b981';
                tabFlow.style.color = 'white';
                tabFlow.style.fontWeight = '700';
                tabTransferencia.style.background = '#f3f4f6';
                tabTransferencia.style.color = '#374151';
                tabTransferencia.style.fontWeight = '600';
                flowContent.style.display = 'block';
                transferenciaContent.style.display = 'none';
            } else {
                tabTransferencia.style.background = '#f59e0b';
                tabTransferencia.style.color = 'white';
                tabTransferencia.style.fontWeight = '700';
                tabFlow.style.background = '#f3f4f6';
                tabFlow.style.color = '#374151';
                tabFlow.style.fontWeight = '600';
                flowContent.style.display = 'none';
                transferenciaContent.style.display = 'block';
            }
        }

        function previewComprobante(input) {
            const file = input.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                alert('El archivo no puede superar los 5MB');
                input.value = '';
                return;
            }

            document.getElementById('dropZoneContent').style.display = 'none';
            document.getElementById('previewContent').style.display = 'block';
            document.getElementById('previewFileName').textContent = file.name;

            const btn = document.getElementById('btnEnviarComprobante');
            btn.disabled = false;
            btn.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
            btn.style.color = 'white';
            btn.style.cursor = 'pointer';
        }

        let enviandoComprobante = false;

        function enviarComprobante() {
            if (enviandoComprobante) return;

            const fileInput = document.getElementById('comprobanteInput');
            if (!fileInput.files[0]) {
                alert('Por favor selecciona un comprobante de pago');
                return;
            }

            if (!wizardData.plan_id) {
                alert('Error: No se ha seleccionado un plan v\u00e1lido');
                return;
            }

            enviandoComprobante = true;
            const btn = document.getElementById('btnEnviarComprobante');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            const formData = new FormData();
            formData.append('plan_id', parseInt(wizardData.plan_id));
            formData.append('periodo', wizardData.periodo || 'mensual');
            formData.append('comprobante', fileInput.files[0]);

            fetch('{{ route("cliente.pago-transferencia") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mostrar paso de éxito con mensaje de transferencia
                    document.getElementById('wizardStep2').style.display = 'none';
                    document.getElementById('wizardStep3').style.display = 'block';
                    document.querySelector('#wizardStep3 h3').textContent = '\u00a1Comprobante Enviado!';
                    document.querySelector('#wizardStep3 p').innerHTML = 'Tu comprobante de transferencia ha sido enviado exitosamente. Nuestro equipo verificar\u00e1 el pago y activar\u00e1 tu plan. <strong>Este proceso puede tomar hasta 24 horas h\u00e1biles.</strong>';
                } else {
                    alert('Error: ' + (data.message || 'No se pudo enviar el comprobante'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let mensaje = 'Error al enviar el comprobante';
                if (error.errors) {
                    mensaje = Object.values(error.errors).flat().join('\n');
                } else if (error.message) {
                    mensaje = error.message;
                }
                alert(mensaje);
            })
            .finally(() => {
                enviandoComprobante = false;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Comprobante';
            });
        }

        // Drag and drop para comprobante
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('dropZone');
            if (dropZone) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                });
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, function() {
                        dropZone.style.borderColor = '#10b981';
                        dropZone.style.background = '#f0fdf4';
                    });
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, function() {
                        dropZone.style.borderColor = '#a7f3d0';
                        dropZone.style.background = 'white';
                    });
                });
                dropZone.addEventListener('drop', function(e) {
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        document.getElementById('comprobanteInput').files = files;
                        previewComprobante(document.getElementById('comprobanteInput'));
                    }
                });
            }
        });

        function cerrarWizard() {
            document.getElementById('wizardModal').style.display = 'none';
            wizardData = {};
            window.location.href = '{{ route("cliente.planes-activos") }}';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const solicitudModal = document.getElementById('solicitudModal');
            const infoModal = document.getElementById('infoModal');

            if (event.target == solicitudModal) {
                closeSolicitudModal();
            }
            if (event.target == infoModal) {
                closeInfoModal();
            }
        }
    </script>
</x-app-layout>
