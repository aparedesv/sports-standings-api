<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $user = Auth::user();

        if (!$user || !$user->can($permission)) {
            return response()->json([
                'message' => "You don't have permission to perform this action",
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
