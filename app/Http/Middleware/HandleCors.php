<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow requests from trusted origins (local + production)
        $allowedOrigins = [
            // Local development
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost',
            'http://127.0.0.1',
            // Production domains
            'https://adminpci.mhrpci.site',
            'https://mhrpci.site',
        ];

        // Add APP_URL from environment if set
        $appUrl = config('app.url');
        if ($appUrl && ! in_array($appUrl, $allowedOrigins)) {
            $allowedOrigins[] = $appUrl;
        }

        $origin = $request->header('Origin');

        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $origin && in_array($origin, $allowedOrigins) ? $origin : '')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, X-XSRF-TOKEN')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Add CORS headers for allowed origins
        if ($origin && (in_array($origin, $allowedOrigins) || app()->environment('local'))) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, X-XSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Expose-Headers', 'X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }

        return $response;
    }
}
