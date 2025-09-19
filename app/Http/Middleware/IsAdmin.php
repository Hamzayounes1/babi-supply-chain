<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /** Handle an incoming request. */
    public function handle(Request $request, Closure $next)
    {
        // Must be authenticated and have role 'administrator'
        if (!Auth::check() || Auth::user()->role !== 'administrator') {
            abort(403, 'Access denied');
        }
        return $next($request);
    }
}
