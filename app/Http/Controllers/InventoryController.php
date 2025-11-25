<?php

namespace App\Http\Controllers;

use App\Exports\InventoriesExport;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function page()
    {
        return Inertia::render('Inventories/Index');
    }

    public function showPage(string $accountable)
    {
        return Inertia::render('Inventories/Show', [
            'accountable' => $accountable,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->get('search', '');
        $status = (string) $request->get('status', '');
        $perPage = (int) $request->get('perPage', 10);
        $page = (int) $request->get('page', 1);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $base = Inventory::query();
        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('inventory_accountable', 'like', "%{$search}%")
                    ->orWhere('inventory_name', 'like', "%{$search}%")
                    ->orWhere('inventory_brand', 'like', "%{$search}%");
            });
        }
        if ($status !== '') {
            $base->where('inventory_status', $status);
        }

        $accountables = $base->clone()
            ->select('inventory_accountable')
            ->distinct()
            ->orderBy('inventory_accountable')
            ->paginate($perPage, ['*'], 'page', $page);

        $groups = [];
        foreach ($accountables->items() as $row) {
            $acc = is_array($row) ? ($row['inventory_accountable'] ?? '') : ($row->inventory_accountable ?? '');
            if ($acc === '') { continue; }

            $itemsQ = Inventory::query()->where('inventory_accountable', $acc);
            if ($search !== '') {
                $itemsQ->where(function ($q) use ($search) {
                    $q->where('inventory_name', 'like', "%{$search}%")
                        ->orWhere('inventory_brand', 'like', "%{$search}%");
                });
            }
            if ($status !== '') {
                $itemsQ->where('inventory_status', $status);
            }

            $total = (clone $itemsQ)->count();
            $preview = $itemsQ->orderBy('inventory_name')->limit(3)->get(['inventory_name']);

            $groups[] = [
                'inventory_accountable' => (string) $acc,
                'items' => $preview->values(),
                'total' => (int) $total,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $groups,
            'pagination' => [
                'current_page' => $accountables->currentPage(),
                'last_page' => $accountables->lastPage(),
                'per_page' => $accountables->perPage(),
                'total' => $accountables->total(),
            ],
        ]);
    }

    public function byAccountable(string $accountable, Request $request): JsonResponse
    {
        $query = Inventory::query()->where('inventory_accountable', $accountable);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('inventory_name', 'like', "%{$search}%")
                    ->orWhere('inventory_brand', 'like', "%{$search}%")
                    ->orWhere('inventory_specification', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('inventory_status', $status);
        }

        $items = $query->orderBy('inventory_name')->get();
        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'inventory_accountable' => ['required', 'string', 'max:255'],
            'inventory_name' => ['required', 'string', 'max:255'],
            'inventory_specification' => ['nullable', 'string', 'max:255'],
            'inventory_brand' => ['nullable', 'string', 'max:255'],
            'inventory_status' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $item = Inventory::create($validator->validated());

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function batchStore(Request $request): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'inventory_accountable' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_name' => ['required', 'string', 'max:255'],
            'items.*.inventory_specification' => ['nullable', 'string', 'max:255'],
            'items.*.inventory_brand' => ['nullable', 'string', 'max:255'],
            'items.*.inventory_status' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $validated = $validator->validated();

        $created = [];
        foreach ($validated['items'] as $row) {
            $created[] = Inventory::create([
                'inventory_accountable' => $validated['inventory_accountable'],
                'inventory_name' => $row['inventory_name'],
                'inventory_specification' => $row['inventory_specification'] ?? null,
                'inventory_brand' => $row['inventory_brand'] ?? null,
                'inventory_status' => $row['inventory_status'],
            ]);
        }

        return response()->json(['success' => true, 'data' => $created], 201);
    }

    public function update(Request $request, Inventory $inventory): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'inventory_accountable' => ['required', 'string', 'max:255'],
            'inventory_name' => ['required', 'string', 'max:255'],
            'inventory_specification' => ['nullable', 'string', 'max:255'],
            'inventory_brand' => ['nullable', 'string', 'max:255'],
            'inventory_status' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $inventory->update($validator->validated());

        return response()->json(['success' => true, 'data' => $inventory]);
    }

    public function destroy(Inventory $inventory): JsonResponse
    {
        try {
            $events = Cache::get('inventories_deleted_events', []);
            $events[] = [
                'type' => 'single',
                'accountable' => $inventory->inventory_accountable,
                'count' => 1,
                'item' => [
                    'id' => $inventory->id,
                    'inventory_name' => $inventory->inventory_name,
                ],
                'timestamp' => now()->toDateTimeString(),
            ];
            $events = array_values(array_filter($events, function ($e) {
                try {
                    return Carbon::parse($e['timestamp'])->gte(now()->subDays(7));
                } catch (\Throwable $ex) {
                    return false;
                }
            }));
            Cache::forever('inventories_deleted_events', $events);
        } catch (\Throwable $e) {
            // swallow cache errors
        }

        $inventory->delete();
        return response()->json(['success' => true]);
    }

    public function destroyAccountable(string $accountable): JsonResponse
    {
        $count = Inventory::where('inventory_accountable', $accountable)->count();
        Inventory::where('inventory_accountable', $accountable)->delete();

        try {
            $events = Cache::get('inventories_deleted_events', []);
            $events[] = [
                'type' => 'group',
                'accountable' => $accountable,
                'count' => (int) $count,
                'timestamp' => now()->toDateTimeString(),
            ];
            $events = array_values(array_filter($events, function ($e) {
                try {
                    return Carbon::parse($e['timestamp'])->gte(now()->subDays(7));
                } catch (\Throwable $ex) {
                    return false;
                }
            }));
            Cache::forever('inventories_deleted_events', $events);
        } catch (\Throwable $e) {
            // swallow cache errors
        }
        return response()->json(['success' => true]);
    }

    public function exportExcel(Request $request)
    {
        $accountable = $request->get('accountable');
        $year = now()->format('Y');
        $createdAt = now()->format('Y-m-d_His');
        $fileName = "IT INVENTORIES_{$year}_{$createdAt}.xlsx";
        return Excel::download(new InventoriesExport($accountable), $fileName);
    }

    public function importExcel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'mode' => ['nullable', 'in:single,multi'],
            'accountable' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $file = $request->file('file');
        $mode = $request->get('mode', 'multi');

        if ($mode === 'single') {
            $acc = $request->get('accountable');
            if (!$acc) {
                return response()->json(['success' => false, 'errors' => ['accountable is required for single sheet import']], 422);
            }

            $rows = Excel::toArray(null, $file)[0] ?? [];
            foreach (array_slice($rows, 1) as $row) {
                $name = $row[0] ?? null;
                $spec = $row[1] ?? null;
                $brand = $row[2] ?? null;
                $status = $row[3] ?? 'active';
                if (!$name) { continue; }
                Inventory::create([
                    'inventory_accountable' => (string) $acc,
                    'inventory_name' => (string) $name,
                    'inventory_specification' => $spec ? (string) $spec : null,
                    'inventory_brand' => $brand ? (string) $brand : null,
                    'inventory_status' => (string) $status,
                ]);
            }
        } else {
            $arr = Excel::toArray(null, $file);
            $tmpPath = $file->store('temp');
            $fullPath = Storage::path($tmpPath);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $sheetNames = [];
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheetNames[] = $sheet->getTitle();
            }
            Storage::delete($tmpPath);

            foreach ($arr as $index => $rows) {
                $acc = $sheetNames[$index] ?? null;
                if (!$acc) { continue; }
                foreach (array_slice($rows, 1) as $row) {
                    $name = $row[0] ?? null;
                    $spec = $row[1] ?? null;
                    $brand = $row[2] ?? null;
                    $status = $row[3] ?? 'active';
                    if (!$name) { continue; }
                    Inventory::create([
                        'inventory_accountable' => (string) $acc,
                        'inventory_name' => (string) $name,
                        'inventory_specification' => $spec ? (string) $spec : null,
                        'inventory_brand' => $brand ? (string) $brand : null,
                        'inventory_status' => (string) $status,
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function exportPdf(string $accountable)
    {
        $items = Inventory::where('inventory_accountable', $accountable)->orderBy('inventory_name')->get();
        $pdf = Pdf::loadView('inventories.pdf', [
            'accountable' => $accountable,
            'items' => $items,
        ]);
        $ts = now()->format('Ymd_His');
        $safeAcc = preg_replace('/[^A-Za-z0-9_\-]/', '_', $accountable);
        return $pdf->download("it-inventories_{$safeAcc}_{$ts}.pdf");
    }

    public function exportPdfAll()
    {
        $items = Inventory::orderBy('inventory_accountable')->orderBy('inventory_name')->get();
        $groups = $items->groupBy('inventory_accountable');
        $pdf = Pdf::loadView('inventories.pdf', [
            'groups' => $groups,
        ]);
        $ts = now()->format('Ymd_His');
        return $pdf->download("it-inventories_all_{$ts}.pdf");
    }
}
