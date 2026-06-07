<x-app-layout>
<x-slot name="header">Gestión de Colaboradores</x-slot>

<div style="padding: 1.5rem;">
    @if(session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    <!-- Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
            <h2 style="margin:0; font-size:1.25rem; font-weight:700; color:#1e293b;">Colaboradores</h2>
            <p style="margin:0.25rem 0 0; font-size:0.85rem; color:#94a3b8;">Administra el acceso de tu equipo a los módulos del sistema</p>
        </div>
        <button onclick="document.getElementById('modalCrear').style.display='flex'" style="padding:0.6rem 1.5rem; background:#FFC800; color:#000; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:0.85rem;">
            <i class="fas fa-user-plus"></i> Nuevo Colaborador
        </button>
    </div>

    <!-- Lista de colaboradores -->
    <div style="display:grid; gap:1rem;">
        @forelse($colaboradores as $colab)
        <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <div style="width:48px; height:48px; background:#FFC80030; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#FFC800; font-size:1.1rem;">
                        {{ strtoupper(substr($colab->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:#1e293b;">{{ $colab->name }}</div>
                        <div style="font-size:0.8rem; color:#94a3b8;">{{ $colab->email }}</div>
                        <div style="font-size:0.7rem; color:#64748b; margin-top:0.15rem;">
                            Creado: {{ $colab->created_at->format('d/m/Y') }}
                            @if($colab->last_login_at) · Último acceso: {{ \Carbon\Carbon::parse($colab->last_login_at)->format('d/m/Y H:i') }} @endif
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <button onclick="editarPermisos({{ $colab->id }})" style="padding:0.4rem 1rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-size:0.75rem; font-weight:600; cursor:pointer;">
                        <i class="fas fa-key"></i> Permisos
                    </button>
                    <form method="POST" action="{{ route('admin.colaboradores.toggle', $colab->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" style="padding:0.4rem 1rem; background:{{ $colab->activo !== false ? '#f59e0b' : '#10b981' }}; color:#fff; border:none; border-radius:8px; font-size:0.75rem; font-weight:600; cursor:pointer;">
                            <i class="fas fa-{{ $colab->activo !== false ? 'ban' : 'check' }}"></i> {{ $colab->activo !== false ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Permisos actuales -->
            <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #f1f5f9;">
                <div style="font-size:0.75rem; font-weight:600; color:#64748b; margin-bottom:0.5rem;">PERMISOS ASIGNADOS:</div>
                <div style="display:flex; flex-wrap:wrap; gap:0.35rem;">
                    @php $permisos = $colab->permissions->pluck('name')->toArray(); @endphp
                    @if(count($permisos) > 0)
                        @foreach($permisos as $perm)
                        @php
                            $modulo = explode('.', $perm)[0] ?? '';
                            $colores = ['finanzas' => '#10b981', 'agencia' => '#8b5cf6', 'integraciones' => '#3b82f6'];
                            $color = $colores[$modulo] ?? '#94a3b8';
                        @endphp
                        <span style="background:{{ $color }}15; color:{{ $color }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.65rem; font-weight:600;">{{ $perm }}</span>
                        @endforeach
                    @else
                        <span style="font-size:0.75rem; color:#94a3b8;">Sin permisos asignados</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div style="background:#fff; border-radius:12px; padding:3rem; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <i class="fas fa-users" style="font-size:2rem; color:#e2e8f0; margin-bottom:0.75rem;"></i>
            <p style="color:#94a3b8; font-size:0.9rem;">No hay colaboradores registrados</p>
            <p style="color:#cbd5e1; font-size:0.8rem;">Crea un colaborador para darle acceso a secciones específicas del sistema</p>
        </div>
        @endforelse
    </div>
</div>

<!-- Modal Crear Colaborador -->
<div id="modalCrear" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:600px; width:90%; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Nuevo Colaborador</h3>
            <button onclick="document.getElementById('modalCrear').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.colaboradores.store') }}">
            @csrf
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Nombre *</label>
                    <input type="text" name="name" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Email *</label>
                    <input type="email" name="email" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Contraseña *</label>
                    <input type="password" name="password" required minlength="8" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Confirmar Contraseña *</label>
                    <input type="password" name="password_confirmation" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
            </div>

            <h4 style="margin:0 0 1rem; font-size:0.9rem; font-weight:700; color:#1e293b;">Permisos por Módulo</h4>

            <!-- Módulo Finanzas -->
            <div style="margin-bottom:1rem; padding:1rem; background:#10b98108; border:1px solid #10b98120; border-radius:8px;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                    <input type="checkbox" id="toggleFinanzas" onchange="toggleModulo('finanzas', this.checked)">
                    <label for="toggleFinanzas" style="font-weight:700; font-size:0.85rem; color:#10b981; cursor:pointer;">FINANZAS</label>
                </div>
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:0.35rem; padding-left:1.5rem;">
                    @foreach($permisosAgrupados['finanzas'] ?? [] as $perm)
                    <label style="display:flex; align-items:center; gap:0.35rem; font-size:0.75rem; color:#475569; cursor:pointer;">
                        <input type="checkbox" name="permisos[]" value="{{ $perm->name }}" class="perm-finanzas"> {{ str_replace('finanzas.', '', $perm->name) }}
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Módulo Agencia -->
            <div style="margin-bottom:1rem; padding:1rem; background:#8b5cf608; border:1px solid #8b5cf620; border-radius:8px;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                    <input type="checkbox" id="toggleAgencia" onchange="toggleModulo('agencia', this.checked)">
                    <label for="toggleAgencia" style="font-weight:700; font-size:0.85rem; color:#8b5cf6; cursor:pointer;">AGENCIA</label>
                </div>
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:0.35rem; padding-left:1.5rem;">
                    @foreach($permisosAgrupados['agencia'] ?? [] as $perm)
                    <label style="display:flex; align-items:center; gap:0.35rem; font-size:0.75rem; color:#475569; cursor:pointer;">
                        <input type="checkbox" name="permisos[]" value="{{ $perm->name }}" class="perm-agencia"> {{ str_replace('agencia.', '', $perm->name) }}
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Módulo Integraciones -->
            <div style="margin-bottom:1.5rem; padding:1rem; background:#3b82f608; border:1px solid #3b82f620; border-radius:8px;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                    <input type="checkbox" id="toggleIntegraciones" onchange="toggleModulo('integraciones', this.checked)">
                    <label for="toggleIntegraciones" style="font-weight:700; font-size:0.85rem; color:#3b82f6; cursor:pointer;">INTEGRACIONES</label>
                </div>
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:0.35rem; padding-left:1.5rem;">
                    @foreach($permisosAgrupados['integraciones'] ?? [] as $perm)
                    <label style="display:flex; align-items:center; gap:0.35rem; font-size:0.75rem; color:#475569; cursor:pointer;">
                        <input type="checkbox" name="permisos[]" value="{{ $perm->name }}" class="perm-integraciones"> {{ str_replace('integraciones.', '', $perm->name) }}
                    </label>
                    @endforeach
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:1rem;">
                <button type="button" onclick="document.getElementById('modalCrear').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#FFC800; color:#000; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Crear Colaborador</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Permisos -->
<div id="modalPermisos" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:600px; width:90%; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Editar Permisos</h3>
            <button onclick="document.getElementById('modalPermisos').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" id="formPermisos">
            @csrf
            <div id="permisosContent"></div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalPermisos').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Guardar Permisos</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModulo(modulo, checked) {
    document.querySelectorAll('.perm-' + modulo).forEach(function(cb) { cb.checked = checked; });
}
function editarPermisos(id) {
    document.getElementById('formPermisos').action = '/admin/colaboradores/' + id + '/permisos';
    // Load current permissions via page data
    @foreach($colaboradores as $c)
    if(id === {{ $c->id }}) {
        var perms = @json($c->permissions->pluck('name'));
        var html = '';
        @foreach(['finanzas' => '#10b981', 'agencia' => '#8b5cf6', 'integraciones' => '#3b82f6'] as $mod => $color)
        html += '<div style="margin-bottom:1rem; padding:1rem; background:{{ $color }}08; border:1px solid {{ $color }}20; border-radius:8px;">';
        html += '<div style="font-weight:700; font-size:0.85rem; color:{{ $color }}; margin-bottom:0.75rem;">{{ strtoupper($mod) }}</div>';
        html += '<div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0.35rem;">';
        @foreach($permisosAgrupados[$mod] ?? [] as $perm)
        html += '<label style="display:flex;align-items:center;gap:0.35rem;font-size:0.75rem;color:#475569;cursor:pointer;"><input type="checkbox" name="permisos[]" value="{{ $perm->name }}" ' + (perms.includes('{{ $perm->name }}') ? 'checked' : '') + '> {{ str_replace($mod.'.', '', $perm->name) }}</label>';
        @endforeach
        html += '</div></div>';
        @endforeach
        document.getElementById('permisosContent').innerHTML = html;
    }
    @endforeach
    document.getElementById('modalPermisos').style.display = 'flex';
}
</script>
</x-app-layout>
