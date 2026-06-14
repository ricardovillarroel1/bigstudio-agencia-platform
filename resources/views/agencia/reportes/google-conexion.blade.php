<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Conexión</span> Google Ads
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="rounded-xl px-4 py-3 text-sm" style="background:#ECFDF5; border:1px solid #A7F3D0; color:#065F46;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-xl px-4 py-3 text-sm" style="background:#FEF2F2; border:1px solid #FCA5A5; color:#991B1B;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            {{-- Estado de la integración --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header">
                    <h3 class="bs-display text-lg text-gray-800 m-0">
                        <i class="fab fa-google text-brand-500"></i> Estado de la conexión
                    </h3>
                </div>
                <div class="bs-card-body">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="p-4 rounded-xl" style="background:{{ $hasCredentials ? '#ECFDF5' : '#FEF3C7' }}; border:1px solid {{ $hasCredentials ? '#A7F3D0' : '#FCD34D' }};">
                            <p class="text-xs uppercase font-bold m-0" style="color:{{ $hasCredentials ? '#065F46' : '#92400E' }};">
                                @if($hasCredentials) <i class="fas fa-check-circle"></i> Credenciales OK @else <i class="fas fa-clock"></i> Faltan credenciales @endif
                            </p>
                            <p class="text-xs text-gray-600 m-0 mt-1">Client ID, Secret y Developer Token</p>
                        </div>
                        <div class="p-4 rounded-xl" style="background:{{ $hasOAuthToken ? '#ECFDF5' : '#FEF3C7' }}; border:1px solid {{ $hasOAuthToken ? '#A7F3D0' : '#FCD34D' }};">
                            <p class="text-xs uppercase font-bold m-0" style="color:{{ $hasOAuthToken ? '#065F46' : '#92400E' }};">
                                @if($hasOAuthToken) <i class="fas fa-check-circle"></i> OAuth autorizado @else <i class="fas fa-clock"></i> Falta autorizar OAuth @endif
                            </p>
                            <p class="text-xs text-gray-600 m-0 mt-1">Refresh token de Google</p>
                        </div>
                        <div class="p-4 rounded-xl" style="background:{{ $hasToken ? '#ECFDF5' : '#FEF3C7' }}; border:1px solid {{ $hasToken ? '#A7F3D0' : '#FCD34D' }};">
                            <p class="text-xs uppercase font-bold m-0" style="color:{{ $hasToken ? '#065F46' : '#92400E' }};">
                                @if($hasToken) <i class="fas fa-link"></i> Modo LIVE @else <i class="fas fa-flask"></i> Modo DEMO @endif
                            </p>
                            <p class="text-xs text-gray-600 m-0 mt-1">@if($hasToken) Datos reales API @else Datos simulados @endif</p>
                        </div>
                    </div>

                    @if($canStartOAuth && !$hasOAuthToken)
                    <div class="mt-4 p-4 rounded-xl" style="background:#EFF6FF; border:1px solid #BFDBFE;">
                        <p class="text-sm font-semibold text-gray-800 m-0" style="color:#1E40AF;">Siguiente paso: autoriza la conexión OAuth</p>
                        <p class="text-xs text-gray-600 m-0 mt-1 mb-3">
                            Vas a ser redirigido a Google para aprobar el acceso de lectura a tus cuentas publicitarias.
                            @if(!$hasCredentials)
                            <br><strong>Puedes autorizar ahora aunque aún no tengas el Developer Token</strong> — el sistema quedará en modo DEMO hasta que también pegues el token (cuando Google te lo apruebe).
                            @endif
                        </p>
                        <a href="{{ route('agencia.reportes.google.auth') }}" class="bs-btn-primary" style="display:inline-block;">
                            <i class="fab fa-google"></i> Autorizar con Google
                        </a>
                    </div>
                    @elseif($hasOAuthToken && !$hasCredentials)
                    <div class="mt-4 p-4 rounded-xl" style="background:#FFF7EC; border:1px solid #FED7AA;">
                        <p class="text-sm font-semibold m-0" style="color:#9A3412;">
                            <i class="fas fa-check-circle"></i> OAuth listo · Solo falta el Developer Token
                        </p>
                        <p class="text-xs text-gray-600 m-0 mt-1">
                            Cuando Google te apruebe el Developer Token (1-3 días desde el API Center de tu cuenta MCC), pégalo arriba y el sistema pasará automáticamente a modo LIVE.
                        </p>
                    </div>
                    @elseif($hasOAuthToken && $hasCredentials)
                    <div class="mt-4 p-4 rounded-xl" style="background:#ECFDF5; border:1px solid #A7F3D0;">
                        <p class="text-sm font-semibold m-0" style="color:#065F46;">
                            <i class="fas fa-check-circle"></i> Conexión completa · Modo LIVE activo
                        </p>
                        <p class="text-xs text-gray-600 m-0 mt-1">
                            Ya puedes vincular cuentas reales abajo. El sistema traerá datos directos de la Google Ads API.
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Credenciales --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header">
                    <h3 class="bs-display text-lg text-gray-800 m-0">Credenciales de Google Cloud</h3>
                </div>
                <div class="bs-card-body">
                    <form method="POST" action="{{ route('agencia.reportes.google.conexion.credenciales') }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="bs-label">Client ID</label>
                                <input type="text" name="google_client_id" class="bs-input" placeholder="xxxxx.apps.googleusercontent.com" value="{{ DB::table('settings')->where('key','google_client_id')->value('value') }}" required>
                            </div>
                            <div>
                                <label class="bs-label">Client Secret</label>
                                <input type="password" name="google_client_secret" class="bs-input" placeholder="GOCSPX-xxxxxxxxxxxxxxxxx" value="{{ DB::table('settings')->where('key','google_client_secret')->value('value') }}" required>
                            </div>
                            <div>
                                <label class="bs-label">Developer Token (Google Ads API) <span class="text-xs text-gray-400">— opcional por ahora</span></label>
                                <input type="password" name="google_developer_token" class="bs-input" placeholder="Pega cuando Google te lo apruebe" value="{{ DB::table('settings')->where('key','google_developer_token')->value('value') }}">
                                <p class="text-xs text-gray-400 mt-1">Sin este token, el modo LIVE no podrá llamar a la API (queda en DEMO). El OAuth sí funciona sin él.</p>
                            </div>
                            <div>
                                <label class="bs-label">MCC Login Customer ID (opcional)</label>
                                <input type="text" name="google_login_customer_id" class="bs-input" placeholder="123-456-7890" value="{{ DB::table('settings')->where('key','google_login_customer_id')->value('value') }}">
                                <p class="text-xs text-gray-400 mt-1">Solo si gestionas las cuentas desde una cuenta MCC manager.</p>
                            </div>
                        </div>
                        <div class="mt-4 p-3 rounded-lg" style="background:#FFF7EC;">
                            <p class="text-xs text-gray-700 m-0" style="line-height:1.6;">
                                <strong>Redirect URI a registrar en Google Cloud:</strong><br>
                                <code style="background:#fff; padding:4px 8px; border-radius:4px; font-size:0.7rem;">{{ route('agencia.reportes.google.callback') }}</code>
                            </p>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button type="submit" class="bs-btn-primary">
                                <i class="fas fa-save"></i> Guardar credenciales
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Vincular cuenta --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header">
                    <h3 class="bs-display text-lg text-gray-800 m-0">Vincular cuenta publicitaria</h3>
                </div>
                <div class="bs-card-body">
                    @if($hasToken && count($accessibleCustomers) > 0)
                    <div class="mb-4 p-3 rounded-lg" style="background:#ECFDF5;">
                        <p class="text-xs font-bold text-gray-700 m-0 uppercase">Cuentas accesibles desde tu Google</p>
                        <ul class="text-xs text-gray-600 m-0 mt-1" style="list-style:disc; padding-left:20px;">
                            @foreach($accessibleCustomers as $c)
                            <li>{{ $c }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <form method="POST" action="{{ route('agencia.reportes.google.conexion.cuenta') }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="bs-label">Cliente vinculado</label>
                                <select name="agencia_cliente_id" class="bs-input">
                                    <option value="">— Sin cliente (cuenta independiente) —</option>
                                    @foreach($clientes as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="bs-label">Nombre de la cuenta (visible en reportes)</label>
                                <input type="text" name="nombre_cuenta" class="bs-input" placeholder="ej: Botas Militares — Google Ads" required>
                            </div>
                            <div>
                                <label class="bs-label">Customer ID Google Ads</label>
                                <input type="text" name="customer_id" class="bs-input" placeholder="1234567890 o 123-456-7890" required>
                            </div>
                            <div>
                                <label class="bs-label">Login Customer ID (MCC, opcional)</label>
                                <input type="text" name="login_customer_id" class="bs-input" placeholder="MCC manager si aplica">
                            </div>
                            <div>
                                <label class="bs-label">Moneda</label>
                                <input type="text" name="moneda" class="bs-input" placeholder="CLP" value="CLP" maxlength="10">
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button type="submit" class="bs-btn-primary"><i class="fas fa-plus"></i> Vincular cuenta</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Cuentas vinculadas --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header">
                    <h3 class="bs-display text-lg text-gray-800 m-0">Cuentas vinculadas ({{ $cuentas->count() }})</h3>
                </div>
                <div class="bs-card-body">
                    @if($cuentas->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-6 m-0">Aún no has vinculado ninguna cuenta de Google Ads.</p>
                    @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100">
                                    <th class="py-2 pr-3">Cuenta</th>
                                    <th class="py-2 px-3">Cliente</th>
                                    <th class="py-2 px-3">Customer ID</th>
                                    <th class="py-2 px-3">Última sync</th>
                                    <th class="py-2 px-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($cuentas as $c)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 pr-3 font-medium text-gray-800">{{ $c->nombre_cuenta }}</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $c->cliente->nombre ?? '—' }}</td>
                                    <td class="py-3 px-3 text-gray-600 text-xs"><code>{{ $c->customer_id }}</code></td>
                                    <td class="py-3 px-3 text-gray-500 text-xs">{{ $c->ultima_sync_at ? $c->ultima_sync_at->format('d/m/Y H:i') : '—' }}</td>
                                    <td class="py-3 px-3 text-right">
                                        <a href="{{ route('agencia.reportes.google', ['cuenta_id' => $c->id]) }}" class="text-xs bs-link"><i class="fas fa-eye"></i> Ver reporte</a>
                                        <form method="POST" action="{{ route('agencia.reportes.google.conexion.cuenta.eliminar', $c) }}" class="inline" onsubmit="return confirm('¿Desvincular esta cuenta?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs" style="color:#DC2626; background:none; border:none; cursor:pointer; margin-left:8px;"><i class="fas fa-unlink"></i> Desvincular</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
