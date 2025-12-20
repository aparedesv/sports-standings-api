<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserStatusMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Verificar si l'usuari està actiu (si existeix el camp)
        if (isset($user->is_active) && $user->is_active === false) {
            // Eliminar tots els tokens de l'usuari
            $user->tokens->each(function ($token) {
                $token->delete();
            });

            return response()->json([
                'message' => "This user account is not active",
            ], 403);
        }

        return $next($request);
    }
}
