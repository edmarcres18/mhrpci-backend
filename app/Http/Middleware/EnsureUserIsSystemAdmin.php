<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSystemAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Check if user exists and is System Admin
        if (! $user || ! $user->isSystemAdmin()) {
            abort(403, 'Access denied. Only System Administrators can access this resource.');
        }

        return $next($request);
    }
}
