<?php

namespace App\Http\Controllers;

use App\Models\SiteInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends Controller
{
    /**
     * Display the site settings configuration page.
     * Only System Admin can access this page.
     */
    public function index(): Response
    {
        $siteInfo = SiteInformation::first();

        return Inertia::render('SiteSettings/Index', [
            'siteSettings' => $siteInfo ? [
                'id' => $siteInfo->id,
                'site_name' => $siteInfo->site_name ?? 'Laravel Starter Kit',
                'site_logo' => $siteInfo->site_logo ? Storage::url($siteInfo->site_logo) : null,
                'created_at' => optional($siteInfo->created_at)->toDateTimeString(),
                'updated_at' => optional($siteInfo->updated_at)->toDateTimeString(),
            ] : [
                'site_name' => 'Laravel Starter Kit',
                'site_logo' => null,
            ],
        ]);
    }

    /**
     * Update the site name and/or logo.
     * Handles file uploads for logo images.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $siteInfo = SiteInformation::first();
        
        // Prepare data for update/create
        $data = [
            'site_name' => $request->site_name,
        ];

        // Handle logo upload
        if ($request->hasFile('site_logo')) {
            // Delete old logo if exists
            if ($siteInfo && $siteInfo->site_logo) {
                Storage::delete($siteInfo->site_logo);
            }

            // Store new logo
            $logoPath = $request->file('site_logo')->store('site-assets', 'public');
            $data['site_logo'] = $logoPath;
        }

        if ($siteInfo) {
            // Update existing record
            $siteInfo->update($data);
            $message = 'Site settings updated successfully.';
        } else {
            // Create new record with default contact info
            $data = array_merge($data, [
                'email_address' => 'info@example.com',
                'tel_no' => '000-0000',
                'phone_no' => '0000-000-000',
                'facebook' => 'example',
            ]);
            SiteInformation::create($data);
            $message = 'Site settings created successfully.';
        }

        return redirect()
            ->route('site-settings.index')
            ->with('success', $message);
    }

    /**
     * Remove the site logo.
     */
    public function removeLogo()
    {
        $siteInfo = SiteInformation::first();

        if ($siteInfo && $siteInfo->site_logo) {
            // Delete the logo file
            Storage::delete($siteInfo->site_logo);
            
            // Update the record
            $siteInfo->update(['site_logo' => null]);

            return redirect()
                ->route('site-settings.index')
                ->with('success', 'Site logo removed successfully.');
        }

        return redirect()
            ->route('site-settings.index')
            ->with('error', 'No site logo to remove.');
    }

    /**
     * Reset site settings to default values.
     */
    public function reset()
    {
        $siteInfo = SiteInformation::first();

        if ($siteInfo) {
            // Delete logo if exists
            if ($siteInfo->site_logo) {
                Storage::delete($siteInfo->site_logo);
            }

            // Reset to default values
            $siteInfo->update([
                'site_name' => 'Laravel Starter Kit',
                'site_logo' => null,
            ]);

            return redirect()
                ->route('site-settings.index')
                ->with('success', 'Site settings reset to default values.');
        }

        return redirect()
            ->route('site-settings.index')
            ->with('error', 'No site settings to reset.');
    }

    // ============================================
    // API ENDPOINT FOR PUBLIC ACCESS
    // ============================================

    /**
     * Fetch site name and logo for public use.
     * Used by auth pages and sidebar.
     * Cached for 1 hour for performance.
     *
     * @return JsonResponse
     */
    public function fetchSiteSettings(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_settings';
            $cacheDuration = 3600; // 1 hour

            $settings = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();
                
                return [
                    'site_name' => $siteInfo?->site_name ?? 'Laravel Starter Kit',
                    'site_logo' => $siteInfo?->site_logo 
                        ? Storage::url($siteInfo->site_logo) 
                        : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $settings,
                'meta' => [
                    'cached' => true,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Site Settings Fetch Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching site settings',
                'data' => [
                    'site_name' => 'Laravel Starter Kit',
                    'site_logo' => null,
                ],
            ], 500);
        }
    }
}
