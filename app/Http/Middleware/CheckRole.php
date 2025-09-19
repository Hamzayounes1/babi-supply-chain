<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('role:administrator') or 'role:administrator,manager'
     */
    public function handle(Request $request, Closure $next, $roles)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $roleList = explode(',', $roles);

        if (!in_array($user->role, $roleList)) {
            return response()->json(['message' => 'Forbidden: insufficient role'], 403);
        }

        return $next($request);
    }
}
