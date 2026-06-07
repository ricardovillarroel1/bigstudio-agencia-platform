<x-app-layout>
<div style="padding: 20px 30px; max-width: 1200px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin: 0;">Correos Integraciones</h1>
            <p style="color: #94a3b8; font-size: 0.9rem; margin: 4px 0 0;">Envia correos individuales o masivos a los clientes de integracion</p>
        </div>
        <span style="color: #64748b; font-size: 0.85rem;">{{ date('d/m/Y') }}</span>
    </div>

    @if(session('success'))
        <div style="background: #065f46; border: 1px solid #10b981; color: #6ee7b7; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
            <i class="fas fa-check-circle" style="margin-right: 6px;"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background: #7f1d1d; border: 1px solid #ef4444; color: #fca5a5; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
            <i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i> {{ session('error') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <!-- Correo Individual -->
        <div style="background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 12px; padding: 25px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <div style="background: linear-gradient(135deg, #7c3aed, #4f46e5); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-envelope" style="color: #fff; font-size: 0.9rem;"></i>
                </div>
                <h3 style="color: #fff; font-size: 1.1rem; font-weight: 700; margin: 0;">Enviar Correo Individual</h3>
            </div>
            <form action="{{ route('integracion.correo-individual') }}" method="POST">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label style="color: #94a3b8; font-size: 0.8rem; display: block; margin-bottom: 6px;">Cliente Destinatario</label>
                    <select name="cliente_id" required style="width: 100%; background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 10px 12px; border-radius: 8px; font-size: 0.9rem;">
                        <option value="">Seleccionar cliente...</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="color: #94a3b8; font-size: 0.8rem; display: block; margin-bottom: 6px;">Asunto</label>
                    <input type="text" name="asunto" required placeholder="Asunto del correo..." style="width: 100%; background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 10px 12px; border-radius: 8px; font-size: 0.9rem;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="color: #94a3b8; font-size: 0.8rem; display: block; margin-bottom: 6px;">Contenido</label>
                    <textarea name="contenido" required rows="5" placeholder="Escribe el contenido del correo..." style="width: 100%; background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 10px 12px; border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>
                <button type="submit" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; width: 100%; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-paper-plane" style="margin-right: 6px;"></i> Enviar Correo
                </button>
            </form>
        </div>

        <!-- Correo Masivo -->
        <div style="background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 12px; padding: 25px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <div style="background: linear-gradient(135deg, #f59e0b, #d97706); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-bullhorn" style="color: #fff; font-size: 0.9rem;"></i>
                </div>
                <h3 style="color: #fff; font-size: 1.1rem; font-weight: 700; margin: 0;">Correo Masivo a Todos los Clientes</h3>
            </div>
            <div style="background: #1e1b4b; border: 1px solid #4338ca; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;">
                <p style="color: #a5b4fc; font-size: 0.8rem; margin: 0;">
                    <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                    <strong>Atencion:</strong> Este correo se enviara a <strong>todos los clientes activos</strong> con email registrado ({{ count($clientes) }} clientes).
                </p>
            </div>
            <form action="{{ route('integracion.correo-masivo') }}" method="POST" onsubmit="return confirm('¿Estas seguro de enviar este correo a TODOS los clientes de integracion?');">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label style="color: #94a3b8; font-size: 0.8rem; display: block; margin-bottom: 6px;">Asunto del Comunicado</label>
                    <input type="text" name="asunto" required placeholder="Asunto del comunicado..." style="width: 100%; background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 10px 12px; border-radius: 8px; font-size: 0.9rem;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="color: #94a3b8; font-size: 0.8rem; display: block; margin-bottom: 6px;">Contenido del Comunicado</label>
                    <textarea name="contenido" required rows="5" placeholder="Escribe el comunicado para todos los clientes..." style="width: 100%; background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 10px 12px; border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>
                <button type="submit" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; width: 100%; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-bullhorn" style="margin-right: 6px;"></i> Enviar a Todos los Clientes ({{ count($clientes) }})
                </button>
            </form>
        </div>
    </div>

    <!-- Historial de Correos -->
    <div style="background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 12px; padding: 25px;">
        <h3 style="color: #fff; font-size: 1.1rem; font-weight: 700; margin: 0 0 20px;">
            <i class="fas fa-history" style="color: #7c3aed; margin-right: 8px;"></i> Historial de Correos Enviados
        </h3>

        <!-- Filtros -->
        <form method="GET" action="{{ route('integracion.correos') }}" style="display: flex; gap: 12px; align-items: end; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <label style="color: #94a3b8; font-size: 0.75rem; display: block; margin-bottom: 4px;">Cliente</label>
                <select name="cliente" style="background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem;">
                    <option value="">Todos</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ request('cliente') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="color: #94a3b8; font-size: 0.75rem; display: block; margin-bottom: 4px;">Estado

                <select name="estado" style="background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem;">
                    <option value="">Todos</option>
                    <option value="enviado" {{ request('estado') == 'enviado' ? 'selected' : '' }}>Enviado</option>
                    <option value="error" {{ request('estado') == 'error' ? 'selected' : '' }}>Error</option>
                </select>
            </div>
            <div>
                <label style="color: #94a3b8; font-size: 0.75rem; display: block; margin-bottom: 4px;">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" style="background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem;">
            </div>
            <div>
                <label style="color: #94a3b8; font-size: 0.75rem; display: block; margin-bottom: 4px;">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" style="background: #0f0f1e; border: 1px solid #2a2a4a; color: #e2e8f0; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem;">
            </div>
            <button type="submit" style="background: #7c3aed; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Filtrar</button>
            <a href="{{ route('integracion.correos') }}" style="color: #94a3b8; font-size: 0.85rem; text-decoration: none; padding: 8px 0;">Limpiar</a>
        </form>

        @if(count($historial) > 0)
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #2a2a4a;">
                    <th style="text-align: left; padding: 10px 8px; color: #94a3b8; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Fecha</th>
                    <th style="text-align: left; padding: 10px 8px; color: #94a3b8; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Destinatario</th>
                    <th style="text-align: left; padding: 10px 8px; color: #94a3b8; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Asunto</th>
                    <th style="text-align: left; padding: 10px 8px; color: #94a3b8; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($historial as $correo)
                <tr style="border-bottom: 1px solid #1a1a3a;">
                    <td style="padding: 12px 8px; color: #94a3b8; font-size: 0.85rem;">{{ $correo->created_at->format('d/m/Y H:i') }}</td>
                    <td style="padding: 12px 8px;">
                        <div style="color: #e2e8f0; font-weight: 600; font-size: 0.85rem;">{{ $correo->destinatario_nombre ?? 'N/A' }}</div>
                        <div style="color: #64748b; font-size: 0.75rem;">{{ $correo->destinatario_email }}</div>
                    </td>
                    <td style="padding: 12px 8px; color: #e2e8f0; font-size: 0.85rem;">{{ $correo->asunto }}</td>
                    <td style="padding: 12px 8px;">
                        @if($correo->estado == 'enviado')
                            <span style="background: #065f46; color: #6ee7b7; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">Enviado</span>
                        @else
                            <span style="background: #7f1d1d; color: #fca5a5; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">Error</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align: center; padding: 40px 0;">
            <i class="fas fa-inbox" style="color: #2a2a4a; font-size: 2rem; margin-bottom: 10px;"></i>
            <p style="color: #64748b; font-size: 0.9rem;">No hay correos enviados aun</p>
        </div>
        @endif
    </div>
</div>
</x-app-layout>
