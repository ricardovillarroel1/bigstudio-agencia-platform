<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModulePermission
{
    /**
     * Acepta uno o varios permisos (OR): el colaborador pasa si tiene CUALQUIERA.
     * Uso: ->middleware('module.permission:finanzas.dashboard')
     *      ->middleware('module.permission:agencia.tareas.mias,agencia.tareas')
     */
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admin has access to everything
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Collaborator must have at least one of the specified permissions
        if ($user->hasRole('colaborador')) {
            foreach ($permissions as $permission) {
                if ($user->hasPermissionTo($permission)) {
                    return $next($request);
                }
            }
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        // Clients cannot access admin/agencia/finanzas modules
        abort(403, 'Acceso no autorizado.');
    }
}
