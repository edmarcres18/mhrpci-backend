<?php

namespace App\Http\Controllers;

use App\Http\Requests\SiteInformationRequest;
use App\Models\SiteInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SiteInformationController extends Controller
{
    /**
     * Display the site information setup page.
     * Only one record is allowed.
     * Access restricted to System Admin and Admin via middleware.
     */
    public function index(): Response
    {
        $siteInfo = SiteInformation::first();

        return Inertia::render('SiteInformation/Setup', [
            'siteInformation' => $siteInfo ? [
                'id' => $siteInfo->id,
                'email_address' => $siteInfo->email_address,
                'tel_no' => $siteInfo->tel_no,
                'phone_no' => $siteInfo->phone_no,
                'address' => $siteInfo->address,
                'telegram' => $siteInfo->telegram,
                'facebook' => $siteInfo->facebook,
                'viber' => $siteInfo->viber,
                'whatsapp' => $siteInfo->whatsapp,
                'created_at' => optional($siteInfo->created_at)->toDateTimeString(),
                'updated_at' => optional($siteInfo->updated_at)->toDateTimeString(),
            ] : null,
        ]);
    }

    /**
     * Store or update the site information.
     * Only one record is allowed - if exists, update it; otherwise, create it.
     */
    public function store(SiteInformationRequest $request)
    {
        $validated = $request->validated();

        // Check if record exists
        $siteInfo = SiteInformation::first();

        if ($siteInfo) {
            // Update existing record
            $siteInfo->update($validated);
            $message = 'Site information updated successfully.';
        } else {
            // Create new record
            $siteInfo = SiteInformation::create($validated);
            $message = 'Site information created successfully.';
        }

        return redirect()
            ->route('site-information.index')
            ->with('success', $message);
    }

    /**
     * Reset/delete the site information.
     * For admin purposes only.
     */
    public function destroy()
    {
        $siteInfo = SiteInformation::first();

        if ($siteInfo) {
            $siteInfo->delete();
        }

        return redirect()
            ->route('site-information.index')
            ->with('success', 'Site information has been reset successfully.');
    }

    // ============================================
    // API ENDPOINTS FOR PUBLIC ACCESS
    // ============================================

    /**
     * Fetch physical address from site information.
     * Cached for 1 hour for performance.
     */
    public function fetchAddress(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_address';
            $cacheDuration = 3600; // 1 hour

            $address = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                return $siteInfo?->address;
            });

            if (! $address) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'address' => null,
                        'type' => 'address',
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'address' => $address,
                    'type' => 'address',
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Address Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching address',
            ], 500);
        }
    }

    /**
     * Fetch email address from site information.
     * Cached for 1 hour for performance.
     */
    public function fetchEmail(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_email';
            $cacheDuration = 3600; // 1 hour

            $email = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                return $siteInfo?->email_address;
            });

            if (! $email) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'email_address' => null,
                        'type' => 'email',
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'email_address' => $email,
                    'type' => 'email',
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Email Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching email address',
            ], 500);
        }
    }

    /**
     * Fetch telephone number from site information.
     * Cached for 1 hour for performance.
     */
    public function fetchTelNo(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_tel_no';
            $cacheDuration = 3600; // 1 hour

            $telNo = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                return $siteInfo?->tel_no;
            });

            if (! $telNo) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tel_no' => null,
                        'type' => 'telephone',
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tel_no' => $telNo,
                    'type' => 'telephone',
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Tel No Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching telephone number',
            ], 500);
        }
    }

    /**
     * Fetch phone number from site information.
     * Cached for 1 hour for performance.
     */
    public function fetchPhoneNo(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_phone_no';
            $cacheDuration = 3600; // 1 hour

            $phoneNo = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                return $siteInfo?->phone_no;
            });

            if (! $phoneNo) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'phone_no' => null,
                        'type' => 'phone',
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'phone_no' => $phoneNo,
                    'type' => 'phone',
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Phone No Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching phone number',
            ], 500);
        }
    }

    /**
     * Fetch Telegram handle from site information.
     * Cached for 1 hour for performance.
     */
    public function fetchTelegram(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_telegram';
            $cacheDuration = 3600; // 1 hour

            $telegram = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                return $siteInfo?->telegram;
            });

            if (! $telegram) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'telegram' => null,
                        'type' => 'telegram',
                        'url' => null,
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'telegram' => $telegram,
                    'type' => 'telegram',
                    'url' => 'https://t.me/'.ltrim($telegram, '@'),
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Telegram Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching Telegram',
            ], 500);
        }
    }

    /**
     * Fetch Facebook handle/URL from site information.
     * Cached for 1 hour for performance.
     */
    public function fetchFacebook(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_facebook';
            $cacheDuration = 3600; // 1 hour

            $facebook = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                return $siteInfo?->facebook;
            });

            if (! $facebook) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'facebook' => null,
                        'type' => 'facebook',
                        'url' => null,
                    ],
                ], 200);
            }

            // Check if it's already a full URL
            $url = filter_var($facebook, FILTER_VALIDATE_URL)
                ? $facebook
                : 'https://facebook.com/'.ltrim($facebook, '@/');

            return response()->json([
                'success' => true,
                'data' => [
                    'facebook' => $facebook,
                    'type' => 'facebook',
                    'url' => $url,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Facebook Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching Facebook',
            ], 500);
        }
    }

    /**
     * Fetch Viber handle from site information.
     * Cached for 1 hour for performance.
     */
    public function fetchViber(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_viber';
            $cacheDuration = 3600; // 1 hour

            $viber = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                return $siteInfo?->viber;
            });

            if (! $viber) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'viber' => null,
                        'type' => 'viber',
                        'url' => null,
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'viber' => $viber,
                    'type' => 'viber',
                    'url' => 'viber://chat?number='.preg_replace('/[^0-9+]/', '', $viber),
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Viber Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching Viber',
            ], 500);
        }
    }

    /**
     * Fetch WhatsApp number from site information.
     * Cached for 1 hour for performance.
     */
    public function fetchWhatsapp(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_whatsapp';
            $cacheDuration = 3600; // 1 hour

            $whatsapp = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                return $siteInfo?->whatsapp;
            });

            if (! $whatsapp) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'whatsapp' => null,
                        'type' => 'whatsapp',
                        'url' => null,
                    ],
                ], 200);
            }

            // Clean phone number for WhatsApp URL
            $cleanNumber = preg_replace('/[^0-9]/', '', $whatsapp);

            return response()->json([
                'success' => true,
                'data' => [
                    'whatsapp' => $whatsapp,
                    'type' => 'whatsapp',
                    'url' => 'https://wa.me/'.$cleanNumber,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API WhatsApp Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching WhatsApp',
            ], 500);
        }
    }

    /**
     * Fetch all contact information at once.
     * Cached for 1 hour for performance.
     */
    public function fetchAllContacts(): JsonResponse
    {
        try {
            $cacheKey = 'site_info_all_contacts';
            $cacheDuration = 3600; // 1 hour

            $contacts = Cache::remember($cacheKey, $cacheDuration, function () {
                $siteInfo = SiteInformation::first();

                if (! $siteInfo) {
                    return null;
                }

                $data = [];

                // Email
                if ($siteInfo->email_address) {
                    $data['email'] = [
                        'value' => $siteInfo->email_address,
                        'type' => 'email',
                    ];
                }

                // Telephone
                if ($siteInfo->tel_no) {
                    $data['telephone'] = [
                        'value' => $siteInfo->tel_no,
                        'type' => 'telephone',
                    ];
                }

                // Phone
                if ($siteInfo->phone_no) {
                    $data['phone'] = [
                        'value' => $siteInfo->phone_no,
                        'type' => 'phone',
                    ];
                }

                // Address
                if ($siteInfo->address) {
                    $data['address'] = [
                        'value' => $siteInfo->address,
                        'type' => 'address',
                    ];
                }

                // Telegram
                if ($siteInfo->telegram) {
                    $data['telegram'] = [
                        'value' => $siteInfo->telegram,
                        'type' => 'telegram',
                        'url' => 'https://t.me/'.ltrim($siteInfo->telegram, '@'),
                    ];
                }

                // Facebook
                if ($siteInfo->facebook) {
                    $url = filter_var($siteInfo->facebook, FILTER_VALIDATE_URL)
                        ? $siteInfo->facebook
                        : 'https://facebook.com/'.ltrim($siteInfo->facebook, '@/');

                    $data['facebook'] = [
                        'value' => $siteInfo->facebook,
                        'type' => 'facebook',
                        'url' => $url,
                    ];
                }

                // Viber
                if ($siteInfo->viber) {
                    $data['viber'] = [
                        'value' => $siteInfo->viber,
                        'type' => 'viber',
                        'url' => 'viber://chat?number='.preg_replace('/[^0-9+]/', '', $siteInfo->viber),
                    ];
                }

                // WhatsApp
                if ($siteInfo->whatsapp) {
                    $cleanNumber = preg_replace('/[^0-9]/', '', $siteInfo->whatsapp);
                    $data['whatsapp'] = [
                        'value' => $siteInfo->whatsapp,
                        'type' => 'whatsapp',
                        'url' => 'https://wa.me/'.$cleanNumber,
                    ];
                }

                return $data;
            });

            if (! $contacts || empty($contacts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No contact information configured',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $contacts,
                'meta' => [
                    'count' => count($contacts),
                    'cached' => true,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API All Contacts Fetch Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching contact information',
            ], 500);
        }
    }
}
