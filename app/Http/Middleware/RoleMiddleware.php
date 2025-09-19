<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage in routes: ->middleware(['auth:sanctum','role:administrator,buyer'])
     */
    public function handle(Request $request, Closure $next, $roles = null): Response
    {
        $user = $request->user();
        if (!$user || !$user->active) {
            return response()->json(['message'=>'Unauthorized'], 401);
        }

        if ($roles) {
            $allowed = explode(',', $roles);
            // trim spaces
            $allowed = array_map('trim', $allowed);
            if (!in_array($user->role, $allowed)) {
                return response()->json(['message'=>'Forbidden'], 403);
            }
        }

        return $next($request);
    }
}
