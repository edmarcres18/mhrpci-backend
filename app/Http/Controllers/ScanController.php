<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\ScanLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    /**
     * Display the Mobile App IT Scanner landing page.
     */
    public function mobileAppPage(): Response
    {
        return Inertia::render('MobileScanner', [
            'apk' => $this->getApkMeta(),
        ]);
    }

    /**
     * Return a QR image that points to the public APK download.
     */
    public function apkQr(): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $apkUrl = $this->getApkDownloadUrl();
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($apkUrl)
            ->encoding(new Encoding('UTF-8'))
            ->size(500)
            ->margin(10)
            ->build();

        return response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }

    /**
     * Show System Admin page to manage Android APK versions.
     */
    public function manageApk(): Response
    {
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to manage the mobile app.');
        }

        return Inertia::render('SiteSettings/MobileApp', [
            'apk' => $this->getApkMeta(),
        ]);
    }

    /**
     * Upload a new Android APK (System Admin only) and set it as the latest version.
     */
    public function uploadApk(Request $request)
    {
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to upload mobile apps.');
        }

        $validated = $request->validate([
            'version' => ['required', 'string', 'max:50'],
            'apk_file' => ['required', 'file', 'mimes:apk,zip', 'max:1048576'], // 1GB
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $dir = $this->getApkDirectory();
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $versionSlug = preg_replace('/[^A-Za-z0-9._-]/', '_', $validated['version']);
        $timestamp = now()->format('Ymd_His');
        $fileName = "ITScanner_v{$versionSlug}_{$timestamp}.apk";
        $uploadedFile = $request->file('apk_file');
        $uploadedFile->move($dir, $fileName);

        // Set latest alias
        $aliasPath = $dir.DIRECTORY_SEPARATOR.$this->getApkAlias();
        File::copy($dir.DIRECTORY_SEPARATOR.$fileName, $aliasPath);

        $sizeBytes = File::size($aliasPath);
        $meta = [
            'version' => $validated['version'],
            'file' => $fileName,
            'alias' => $this->getApkAlias(),
            'download_url' => $this->getApkDownloadUrl(),
            'file_url' => url('/mobile_app/'.$fileName),
            'size_bytes' => $sizeBytes,
            'size_human' => $this->formatBytes($sizeBytes),
            'uploaded_at' => now()->toIso8601String(),
            'uploaded_by' => $currentUser ? [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
            ] : null,
            'notes' => $validated['notes'] ?? null,
        ];

        File::put($this->getApkMetaPath(), json_encode($meta, JSON_PRETTY_PRINT));

        return redirect()
            ->route('mobile-app.manage')
            ->with('success', 'Android APK uploaded and set as latest.');
    }

    /**
     * API endpoint to fetch latest Android APK metadata.
     */
    public function latestApkMeta(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getApkMeta(),
        ]);
    }

    private function getApkDirectory(): string
    {
        return public_path('mobile_app');
    }

    private function getApkAlias(): string
    {
        return 'ITScanner.apk';
    }

    private function getApkMetaPath(): string
    {
        return $this->getApkDirectory().DIRECTORY_SEPARATOR.'apk_meta.json';
    }

    private function getApkDownloadUrl(): string
    {
        return url('/mobile_app/'.$this->getApkAlias());
    }

    private function getApkMeta(): array
    {
        $metaPath = $this->getApkMetaPath();
        $defaults = [
            'version' => null,
            'download_url' => $this->getApkDownloadUrl(),
            'size_human' => null,
            'uploaded_at' => null,
            'uploaded_by' => null,
            'notes' => null,
        ];

        if (File::exists($metaPath)) {
            $json = json_decode(File::get($metaPath), true);
            if (is_array($json)) {
                return array_merge($defaults, $json);
            }
        }

        return $defaults;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Record a scan event for an inventory item.
     */
    public function recordScan(Request $request): JsonResponse
    {
        $tz = 'Asia/Manila';

        $validator = Validator::make($request->all(), [
            'item_code' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $itemCode = $validator->validated('item_code');

        $inventory = Inventory::where('item_code', $itemCode)->first();

        if (! $inventory) {
            return response()->json(['success' => false, 'message' => 'Inventory item not found.'], 404);
        }

        $userId = optional($request->user())->id;

        ScanLog::create([
            'user_id' => $userId,
            'inventory_id' => $inventory->id,
            'scanned_at' => now($tz),
        ]);

        return response()->json(['success' => true, 'message' => 'Scan recorded successfully.']);
    }

    /**
     * Get daily scan counts, optionally for a specific date.
     */
    public function getDailyScanCounts(Request $request): JsonResponse
    {
        $tz = 'Asia/Manila';

        $validator = Validator::make($request->all(), [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'), $tz)->startOfDay()
            : Carbon::now($tz)->startOfDay();
        $start = $date->copy();
        $end = $date->copy()->endOfDay();
        $userId = optional($request->user())->id;

        $scanLogs = ScanLog::whereBetween('scanned_at', [$start, $end])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->with('inventory:id,item_code,inventory_name,inventory_status') // Eager load inventory with specific columns
            ->get();

        $totalScans = $scanLogs->count();
        $scansByItem = $scanLogs->groupBy('inventory.item_code')->map(function ($group) {
            return [
                'item_code' => $group->first()->inventory->item_code,
                'inventory_name' => $group->first()->inventory->inventory_name,
                'count' => $group->count(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->toDateString(),
                'total_scans_today' => $totalScans,
                'scans_by_item' => $scansByItem,
            ],
        ]);
    }

    /**
     * Get a list of recent scan events.
     */
    public function getRecentScans(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $limit = (int) $request->get('limit', 20); // Default to 20 recent scans
        $userId = optional($request->user())->id;

        $recentScans = ScanLog::orderBy('scanned_at', 'desc')
            ->limit($limit)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->with('inventory:id,item_code,inventory_name,inventory_status') // Eager load inventory details
            ->get();

        return response()->json(['success' => true, 'data' => $recentScans]);
    }
}
