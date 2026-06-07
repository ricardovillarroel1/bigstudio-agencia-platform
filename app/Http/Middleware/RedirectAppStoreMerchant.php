<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Solicitud;

/**
 * Si el usuario autenticado es un merchant instalado desde Shopify App Store,
 * lo redirige a su dashboard simplificado (/app/dashboard) para evitar que vea
 * las vistas legacy de BigStudio que contienen referencias a planes/billing externo.
 *
 * Esto es CRÍTICO para cumplir con la política 1.2.1 de Shopify (no off-platform billing).
 */
class RedirectAppStoreMerchant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $isAppStoreMerchant = Solicitud::where('cliente_id', $user->id)
            ->where('estado', 'activa_appstore')
            ->exists();

        if (!$isAppStoreMerchant) {
            return $next($request);
        }

        // Whitelist de rutas que SÍ puede visitar el merchant App Store
        $allowedRouteNames = [
            'appstore.dashboard',
            'appstore.onboarding',
            'appstore.onboarding.store',
            'profile.edit',
            'profile.update',
            'profile.photo.update',
            'profile.photo.delete',
            'logout',
            'legal.privacy',
            'legal.terms',
        ];
        $allowedPathPrefixes = [
            'app/',
            'onboarding',
            'profile',
            'privacy',
            'terms',
            'logout',
            'webhooks/',
            'shopify/',
            'install',
        ];

        $routeName = $request->route() ? $request->route()->getName() : null;
        if ($routeName && in_array($routeName, $allowedRouteNames, true)) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        foreach ($allowedPathPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        // Cualquier otra ruta → redirige al dashboard del App Store merchant
        return redirect()->route('appstore.dashboard');
    }
}
