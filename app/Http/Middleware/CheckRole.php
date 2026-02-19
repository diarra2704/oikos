<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Vérifie que l'utilisateur a au moins le rôle requis.
     * Usage dans les routes : ->middleware('role:superviseur')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Si l'un des rôles spécifiés correspond
        foreach ($roles as $role) {
            $requiredRole = Role::from($role);
            if ($user->role === $requiredRole || $user->hasRoleAtLeast($requiredRole)) {
                return $next($request);
            }
        }

        abort(403, 'Accès non autorisé.');
    }
}
