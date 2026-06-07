<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoAuthenticated
{
    /**
     * Handle an incoming request.
     * Verifica que el usuario haya ingresado la clave de demo.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('demo_authenticated')) {
            return redirect()->route('demo.login');
        }

        return $next($request);
    }
}
