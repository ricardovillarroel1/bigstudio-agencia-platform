<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    @if (session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <div class="mb-4 flex justify-between items-center">
                        <a href="{{ route('usuarios.create') }}" style="display: inline-flex; align-items: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #000; text-transform: uppercase; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            <i class="fas fa-plus"></i> &nbsp;Crear Nuevo Usuario
                        </a>
                    </div>

                    <!-- Buscador y Filtros -->
                    <form method="GET" action="{{ route('usuarios.index') }}" class="mb-6">
                        <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 0.75rem;">
                            <div style="position: relative;">
                                <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #6b7280;"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o email..." style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                            </div>
                            <select name="role" style="padding: 0.75rem 1rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s;" onfocus="this.style.borderColor='#FFC800'; this.style.boxShadow='0 0 0 3px rgba(255, 193, 7, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                                <option value="">Todos los roles</option>
                                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administradores</option>
                                <option value="cliente" {{ request('role') === 'cliente' ? 'selected' : '' }}>Clientes</option>
                            </select>
                            <div style="display: flex; gap: 0.75rem;">
                                <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                @if(request('search') || request('role'))
                                    <a href="{{ route('usuarios.index') }}" style="padding: 0.75rem 1.5rem; background: #f3f4f6; color: #374151; font-weight: 600; border: none; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; transition: all 0.2s; white-space: nowrap;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="overflow-x-auto mb-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nombre
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rol
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $user->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($user->roles->isNotEmpty())
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->hasRole('admin') ? 'bg-brand-100 text-brand-800' : 'bg-blue-100 text-blue-800' }}">
                                                    <i class="fas fa-{{ $user->hasRole('admin') ? 'user-shield' : 'user' }}"></i> &nbsp;{{ ucfirst($user->roles->first()->name) }}
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Sin rol
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('usuarios.edit', $user->id) }}" class="text-brand-600 hover:text-brand-900 mr-3"><i class="fas fa-edit"></i> Editar</a>
                                            <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i> Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                            @if(request('search') || request('role'))
                                                <i class="fas fa-search" style="font-size: 2rem; color: #d1d5db; margin-bottom: 0.5rem;"></i>
                                                <p>No se encontraron resultados</p>
                                            @else
                                                <i class="fas fa-users" style="font-size: 2rem; color: #d1d5db; margin-bottom: 0.5rem;"></i>
                                                <p>No hay usuarios registrados</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
