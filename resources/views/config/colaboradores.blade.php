<x-app-layout>
<x-slot name="header">Gestión de Colaboradores</x-slot>
<div style="max-width:1200px; margin:0 auto; padding:1.5rem;">
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
                    <form method="POST" action="{{ route('config.colaboradores.toggle', $colab->id) }}" style="display:inline;">
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
                            $colores = ['finanzas' => '#10b981', 'agencia' => '#8b5cf6', 'integraciones' => '#3b82f6', 'config' => '#f59e0b'];
                            $color = $colores[$modulo] ?? '#94a3b8';
                            $label = $permisosAgrupados[ucfirst($modulo == 'config' ? 'Configuración' : $modulo)][$perm] ?? str_replace($modulo.'.', '', $perm);
                        @endphp
                        <span style="background:{{ $color }}15; color:{{ $color }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.65rem; font-weight:600;">{{ $label }}</span>
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
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:650px; width:90%; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Nuevo Colaborador</h3>
            <button onclick="document.getElementById('modalCrear').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('config.colaboradores.store') }}">
            @csrf
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Nombre *</label>
                    <input type="text" name="name" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Email *</label>
                    <input type="email" name="email" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Contraseña *</label>
                    <input type="password" name="password" required minlength="8" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Confirmar Contraseña *</label>
                    <input type="password" name="password_confirmation" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
            </div>
            <h4 style="margin:0 0 1rem; font-size:0.9rem; font-weight:700; color:#1e293b;">Permisos por Módulo</h4>

            @php
                $moduloConfig = [
                    'Finanzas' => ['color' => '#10b981', 'icon' => 'fa-chart-line', 'key' => 'finanzas'],
                    'Agencia' => ['color' => '#8b5cf6', 'icon' => 'fa-briefcase', 'key' => 'agencia'],
                    'Integraciones' => ['color' => '#3b82f6', 'icon' => 'fa-plug', 'key' => 'integraciones'],
                    'Configuración' => ['color' => '#f59e0b', 'icon' => 'fa-cog', 'key' => 'config'],
                ];
            @endphp

            @foreach($moduloConfig as $moduloNombre => $config)
                @if(isset($permisosAgrupados[$moduloNombre]) && count($permisosAgrupados[$moduloNombre]) > 0)
                <div style="margin-bottom:1rem; padding:1rem; background:{{ $config['color'] }}08; border:1px solid {{ $config['color'] }}20; border-radius:8px;">
                    <div style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;" onclick="toggleModuloExpand('crear_{{ $config['key'] }}')">
                        <input type="checkbox" id="toggleCrear_{{ $config['key'] }}" onchange="event.stopPropagation(); toggleModuloCheckboxes('crear_{{ $config['key'] }}', this.checked)" onclick="event.stopPropagation();">
                        <i class="fas {{ $config['icon'] }}" style="color:{{ $config['color'] }}; font-size:0.8rem;"></i>
                        <label style="font-weight:700; font-size:0.85rem; color:{{ $config['color'] }}; cursor:pointer; flex:1;">{{ strtoupper($moduloNombre) }}</label>
                        <i class="fas fa-chevron-down" id="arrow_crear_{{ $config['key'] }}" style="color:#94a3b8; font-size:0.7rem; transition:transform 0.3s;"></i>
                    </div>
                    <div id="subs_crear_{{ $config['key'] }}" style="display:none; margin-top:0.75rem; padding-left:1.5rem; border-top:1px solid {{ $config['color'] }}15; padding-top:0.75rem;">
                        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:0.5rem;">
                            @foreach($permisosAgrupados[$moduloNombre] as $permKey => $permLabel)
                            <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.78rem; color:#475569; cursor:pointer; padding:0.25rem 0.4rem; border-radius:6px; transition:background 0.2s;" onmouseover="this.style.background='{{ $config['color'] }}10'" onmouseout="this.style.background='transparent'">
                                <input type="checkbox" name="permisos[]" value="{{ $permKey }}" class="perm-crear_{{ $config['key'] }}" onchange="updateModuloToggle('crear_{{ $config['key'] }}')">
                                <span>{{ $permLabel }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            @endforeach

            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1rem;">
                <button type="button" onclick="document.getElementById('modalCrear').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#FFC800; color:#000; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Crear Colaborador</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Permisos -->
<div id="modalPermisos" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:650px; width:90%; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Editar Permisos</h3>
            <button onclick="document.getElementById('modalPermisos').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" id="formPermisos">
            @method("PUT")
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
// Datos de permisos agrupados para JS
var permisosData = @json($permisosAgrupados);
var moduloConfig = {
    'Finanzas': { color: '#10b981', icon: 'fa-chart-line', key: 'finanzas' },
    'Agencia': { color: '#8b5cf6', icon: 'fa-briefcase', key: 'agencia' },
    'Integraciones': { color: '#3b82f6', icon: 'fa-plug', key: 'integraciones' },
    'Configuración': { color: '#f59e0b', icon: 'fa-cog', key: 'config' }
};

function toggleModuloExpand(prefix) {
    var subs = document.getElementById('subs_' + prefix);
    var arrow = document.getElementById('arrow_' + prefix);
    if (subs.style.display === 'none') {
        subs.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    } else {
        subs.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    }
}

function toggleModuloCheckboxes(prefix, checked) {
    var subs = document.getElementById('subs_' + prefix);
    // Auto-expand when checking
    if (checked && subs.style.display === 'none') {
        subs.style.display = 'block';
        var arrow = document.getElementById('arrow_' + prefix);
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    }
    document.querySelectorAll('.perm-' + prefix).forEach(function(cb) {
        cb.checked = checked;
    });
}

function updateModuloToggle(prefix) {
    var checkboxes = document.querySelectorAll('.perm-' + prefix);
    var toggle = document.getElementById('toggle' + prefix.charAt(0).toUpperCase() + prefix.slice(1));
    // Try alternate ID format
    if (!toggle) {
        toggle = document.getElementById('toggleCrear_' + prefix.replace('crear_', ''));
        if (!toggle) toggle = document.getElementById('toggleEdit_' + prefix.replace('edit_', ''));
    }
    if (!toggle) return;
    var allChecked = true;
    var anyChecked = false;
    checkboxes.forEach(function(cb) {
        if (cb.checked) anyChecked = true;
        else allChecked = false;
    });
    toggle.checked = allChecked;
    toggle.indeterminate = anyChecked && !allChecked;
}

function editarPermisos(id) {
    document.getElementById('formPermisos').action = '/config/colaboradores/' + id + '/permisos';

    var colaboradoresPerms = {};
    @foreach($colaboradores as $c)
    colaboradoresPerms[{{ $c->id }}] = @json($c->permissions->pluck('name'));
    @endforeach

    var perms = colaboradoresPerms[id] || [];
    var html = '';

    for (var moduloNombre in moduloConfig) {
        var config = moduloConfig[moduloNombre];
        var permsModulo = permisosData[moduloNombre];
        if (!permsModulo || Object.keys(permsModulo).length === 0) continue;

        var prefix = 'edit_' + config.key;
        var allChecked = true;
        var anyChecked = false;

        // Check if all/any perms are checked
        for (var permKey in permsModulo) {
            if (perms.includes(permKey)) anyChecked = true;
            else allChecked = false;
        }

        html += '<div style="margin-bottom:1rem; padding:1rem; background:' + config.color + '08; border:1px solid ' + config.color + '20; border-radius:8px;">';
        html += '<div style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;" onclick="toggleModuloExpand(\'' + prefix + '\')">';
        html += '<input type="checkbox" id="toggle' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + '" ' + (allChecked ? 'checked' : '') + ' onchange="event.stopPropagation(); toggleModuloCheckboxes(\'' + prefix + '\', this.checked)" onclick="event.stopPropagation()"' + (anyChecked && !allChecked ? ' class="indeterminate-init"' : '') + '>';
        html += '<i class="fas ' + config.icon + '" style="color:' + config.color + '; font-size:0.8rem;"></i>';
        html += '<label style="font-weight:700; font-size:0.85rem; color:' + config.color + '; cursor:pointer; flex:1;">' + moduloNombre.toUpperCase() + '</label>';
        html += '<i class="fas fa-chevron-down" id="arrow_' + prefix + '" style="color:#94a3b8; font-size:0.7rem; transition:transform 0.3s;' + (anyChecked ? 'transform:rotate(180deg);' : '') + '"></i>';
        html += '</div>';
        html += '<div id="subs_' + prefix + '" style="display:' + (anyChecked ? 'block' : 'none') + '; margin-top:0.75rem; padding-left:1.5rem; border-top:1px solid ' + config.color + '15; padding-top:0.75rem;">';
        html += '<div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0.5rem;">';

        for (var permKey in permsModulo) {
            var permLabel = permsModulo[permKey];
            var isChecked = perms.includes(permKey);
            html += '<label style="display:flex;align-items:center;gap:0.4rem;font-size:0.78rem;color:#475569;cursor:pointer;padding:0.25rem 0.4rem;border-radius:6px;transition:background 0.2s;" onmouseover="this.style.background=\'' + config.color + '10\'" onmouseout="this.style.background=\'transparent\'">';
            html += '<input type="checkbox" name="permisos[]" value="' + permKey + '" class="perm-' + prefix + '" ' + (isChecked ? 'checked' : '') + ' onchange="updateModuloToggle(\'' + prefix + '\')">';
            html += '<span>' + permLabel + '</span></label>';
        }

        html += '</div></div></div>';
    }

    document.getElementById('permisosContent').innerHTML = html;

    // Set indeterminate state for partially checked modules
    setTimeout(function() {
        document.querySelectorAll('.indeterminate-init').forEach(function(cb) {
            cb.indeterminate = true;
            cb.classList.remove('indeterminate-init');
        });
    }, 10);

    document.getElementById('modalPermisos').style.display = 'flex';
}
</script>
</x-app-layout>
