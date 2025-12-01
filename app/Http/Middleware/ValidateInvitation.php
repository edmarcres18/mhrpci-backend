<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInvitation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token') ?? $request->input('token');

        if (! $token) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Valid invitation token is required to register.']);
        }

        $invitation = Invitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Invalid or expired invitation link.']);
        }

        // Store invitation in request for later use
        $request->merge(['invitation' => $invitation]);

        return $next($request);
    }
}
