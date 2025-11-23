<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si el usuario debe cambiar su contraseña y no está en la ruta de cambio de contraseña
        if ($user && $user->must_change_password && !$request->is('settings/password*')) {
            return redirect()->route('password.edit')
                ->with('warning', 'Debes cambiar tu contraseña temporal antes de continuar.');
        }

        return $next($request);
    }
}
