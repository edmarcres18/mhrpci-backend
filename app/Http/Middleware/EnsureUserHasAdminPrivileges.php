<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAdminPrivileges
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Check if user exists and has admin privileges (System Admin or Admin)
        if (!$user || !$user->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access this resource.');
        }
        
        return $next($request);
    }
}
