<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModulePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admin has access to everything
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Collaborator must have specific permission
        if ($user->hasRole('colaborador')) {
            if ($user->hasPermissionTo($permission)) {
                return $next($request);
            }
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        // Clients cannot access admin/agencia/finanzas modules
        abort(403, 'Acceso no autorizado.');
    }
}
