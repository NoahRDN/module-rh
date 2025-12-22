<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour vérifier qu'un utilisateur est manager d'un département.
 */
class EnsureIsManager
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié',
            ], 401);
        }

        // Les admins et RH ont accès automatiquement
        if ($user->isAdmin() || $user->isRH()) {
            return $next($request);
        }

        // Vérifie que l'utilisateur est bien manager d'un département
        if (!$user->isManagerOfDepartement()) {
            return response()->json([
                'message' => 'Accès réservé aux managers',
            ], 403);
        }

        return $next($request);
    }
}
