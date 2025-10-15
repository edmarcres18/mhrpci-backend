<?php

namespace App\Http\Middleware;

use App\Models\SiteInformation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        
        // Cache site settings for 1 hour
        $siteSettings = Cache::remember('site_info_settings', 3600, function () {
            $siteInfo = SiteInformation::first();
            return [
                'site_name' => $siteInfo?->site_name ?? 'Laravel Starter Kit',
                'site_logo' => $siteInfo?->site_logo 
                    ? Storage::url($siteInfo->site_logo) 
                    : null,
            ];
        });
        
        // Regenerate CSRF token periodically to prevent expiration
        // Only regenerate if the session is active and the token is older than 1 hour
        if ($request->session()->has('_token')) {
            $lastTokenRefresh = $request->session()->get('_token_refreshed_at', 0);
            $currentTime = time();
            
            // Refresh token every hour to keep it valid
            if ($currentTime - $lastTokenRefresh > 3600) {
                $request->session()->regenerateToken();
                $request->session()->put('_token_refreshed_at', $currentTime);
            }
        }
        
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'siteSettings' => $siteSettings,
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'canDeleteProducts' => $user ? $user->hasAdminPrivileges() : false,
                'canDeleteBlogs' => $user ? $user->hasAdminPrivileges() : false,
                'canAccessUsers' => $user ? $user->hasAdminPrivileges() : false,
                'isSystemAdmin' => $user ? $user->isSystemAdmin() : false,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'csrf_token' => csrf_token(), // Share current CSRF token with frontend
        ];
    }
}
