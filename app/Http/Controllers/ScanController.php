<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\ScanLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScanController extends Controller
{
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
