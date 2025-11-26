<?php

namespace App\Http\Controllers;

use App\Exports\ConsumablesExport;
use App\Http\Requests\ConsumableRequest;
use App\Http\Requests\ConsumableUsageRequest;
use App\Http\Resources\ConsumableResource;
use App\Http\Resources\ConsumableUsageResource;
use App\Http\Resources\ConsumableLogResource;
use App\Http\Resources\DeletedConsumableResource;
use App\Models\Consumable;
use App\Models\ConsumableUsage;
use App\Models\ConsumableLog;
use App\Models\DeletedConsumable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ConsumableController extends Controller
{
    public function page()
    {
        return Inertia::render('Consumables/Index');
    }

    public function createPage()
    {
        return Inertia::render('Consumables/Create');
    }

    public function editPage(Consumable $consumable)
    {
        return Inertia::render('Consumables/Edit', [
            'id' => $consumable->id,
        ]);
    }

    public function usageHistoryPage()
    {
        return Inertia::render('Consumables/UsageHistory');
    }

    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->get('search', '');
        $brand = (string) $request->get('brand', '');
        $stock = (string) $request->get('stock', '');
        $perPage = (int) $request->get('perPage', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) { $perPage = 10; }

        $q = Consumable::query();
        if ($search !== '') {
            $q->where(function ($x) use ($search) {
                $x->where('consumable_name', 'like', "%{$search}%")
                  ->orWhere('consumable_description', 'like', "%{$search}%")
                  ->orWhere('consumable_brand', 'like', "%{$search}%");
            });
        }
        if ($brand !== '') {
            $q->where('consumable_brand', $brand);
        }
        if ($stock === 'low') {
            $q->whereColumn('current_quantity', '<=', 'threshold_limit')->where('current_quantity', '>', 0);
        } elseif ($stock === 'critical') {
            $q->where('current_quantity', 0);
        }

        $items = $q->orderBy('consumable_name')->paginate($perPage)->onEachSide(1);

        return response()->json([
            'success' => true,
            'data' => ConsumableResource::collection($items->items()),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(ConsumableRequest $request): JsonResponse
    {
        $c = Consumable::create($request->validated());
        ConsumableLog::create([
            'consumable_id' => $c->id,
            'user_id' => optional($request->user())->id,
            'action' => 'created',
            'changes' => ['after' => $c->only(['consumable_name','consumable_description','consumable_brand','current_quantity','threshold_limit','unit'])],
        ]);
        return response()->json(['success' => true, 'data' => new ConsumableResource($c)], 201);
    }

    public function show(Consumable $consumable): JsonResponse
    {
        return response()->json(['success' => true, 'data' => new ConsumableResource($consumable)]);
    }

    public function update(ConsumableRequest $request, Consumable $consumable): JsonResponse
    {
        $before = $consumable->only(['consumable_name','consumable_description','consumable_brand','current_quantity','threshold_limit','unit']);
        $consumable->update($request->validated());
        $after = $consumable->only(['consumable_name','consumable_description','consumable_brand','current_quantity','threshold_limit','unit']);
        ConsumableLog::create([
            'consumable_id' => $consumable->id,
            'user_id' => optional($request->user())->id,
            'action' => 'updated',
            'changes' => ['before' => $before, 'after' => $after],
        ]);
        return response()->json(['success' => true, 'data' => new ConsumableResource($consumable)]);
    }

    public function destroy(Request $request, Consumable $consumable): JsonResponse
    {
        return \DB::transaction(function () use ($request, $consumable) {
            $force = (bool) $request->boolean('force');
            $snapshot = $consumable->only(['consumable_name','consumable_description','consumable_brand','current_quantity','threshold_limit','unit']);
            if ($force) {
                $consumable->forceDelete();
                ConsumableLog::create([
                    'consumable_id' => $consumable->id,
                    'user_id' => optional($request->user())->id,
                    'action' => 'force_deleted',
                    'changes' => ['before' => $snapshot],
                ]);
            } else {
                $consumable->delete();
                DeletedConsumable::create(array_merge($snapshot, [
                    'consumable_id' => $consumable->id,
                    'deleted_at' => now(),
                    'deleted_by' => optional($request->user())->id,
                    'restore_status' => false,
                ]));
                ConsumableLog::create([
                    'consumable_id' => $consumable->id,
                    'user_id' => optional($request->user())->id,
                    'action' => 'deleted',
                    'changes' => ['before' => $snapshot],
                ]);
            }
            return response()->json(['success' => true]);
        });
    }

    public function usage(ConsumableUsageRequest $request, Consumable $consumable): JsonResponse
    {
        $qty = (int) $request->input('quantity_used');
        if ($qty <= 0) {
            return response()->json(['success' => false, 'errors' => ['quantity_used must be positive']], 422);
        }
        if ((int) $consumable->current_quantity < $qty) {
            return response()->json(['success' => false, 'errors' => ['Insufficient stock']], 422);
        }

        $beforeQty = (int) $consumable->current_quantity;
        $consumable->update(['current_quantity' => $beforeQty - $qty]);
        $usage = ConsumableUsage::create(array_merge($request->validated(), ['consumable_id' => $consumable->id]));

        ConsumableLog::create([
            'consumable_id' => $consumable->id,
            'user_id' => optional($request->user())->id,
            'action' => 'usage',
            'changes' => [
                'before' => ['current_quantity' => $beforeQty],
                'after' => ['current_quantity' => (int) $consumable->current_quantity],
                'usage' => [
                    'quantity_used' => $qty,
                    'purpose' => (string) $usage->purpose,
                    'used_by' => (string) $usage->used_by,
                    'date_used' => (string) $usage->date_used,
                    'notes' => $usage->notes,
                ],
            ],
        ]);

        $warn = (int) $consumable->current_quantity <= (int) $consumable->threshold_limit;
        return response()->json([
            'success' => true,
            'data' => new ConsumableUsageResource($usage),
            'low_stock' => $warn,
        ]);
    }

    public function usages(Request $request): JsonResponse
    {
        $consumableId = (int) $request->get('consumable_id', 0);
        $perPage = (int) $request->get('perPage', 10);
        $q = ConsumableUsage::query()->with('consumable')->orderByDesc('date_used');
        if ($consumableId) { $q->where('consumable_id', $consumableId); }
        if ($from = $request->get('date_from')) { $q->whereDate('date_used', '>=', $from); }
        if ($to = $request->get('date_to')) { $q->whereDate('date_used', '<=', $to); }
        if ($user = $request->get('used_by')) { $q->where('used_by', 'like', "%{$user}%"); }
        if ($purpose = $request->get('purpose')) { $q->where('purpose', 'like', "%{$purpose}%"); }
        $items = $q->paginate($perPage)->onEachSide(1);

        return response()->json([
            'success' => true,
            'data' => ConsumableUsageResource::collection($items->items()),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function trashed(Request $request): JsonResponse
    {
        $search = (string) $request->get('search', '');
        $perPage = (int) $request->get('perPage', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) { $perPage = 10; }

        $q = DeletedConsumable::query()->with('user');
        if ($search !== '') {
            $q->where(function ($x) use ($search) {
                $x->where('consumable_name', 'like', "%{$search}%")
                  ->orWhere('consumable_description', 'like', "%{$search}%")
                  ->orWhere('consumable_brand', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('deleted_at')->paginate($perPage)->onEachSide(1);

        return response()->json([
            'success' => true,
            'data' => DeletedConsumableResource::collection($items->items()),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        return \DB::transaction(function () use ($request, $id) {
            $c = Consumable::withTrashed()->findOrFail($id);
            $deleted = DeletedConsumable::where('consumable_id', $id)->first();
            if ($deleted) {
                $c->forceFill([
                    'consumable_name' => $deleted->consumable_name,
                    'consumable_description' => $deleted->consumable_description,
                    'consumable_brand' => $deleted->consumable_brand,
                    'current_quantity' => $deleted->current_quantity,
                    'threshold_limit' => $deleted->threshold_limit,
                    'unit' => $deleted->unit,
                ])->save();
                $deleted->delete();
            }
            $c->restore();
            ConsumableLog::create([
                'consumable_id' => $c->id,
                'user_id' => optional($request->user())->id,
                'action' => 'restored',
                'changes' => ['after' => $c->only(['consumable_name','consumable_description','consumable_brand','current_quantity','threshold_limit','unit'])],
            ]);
            return response()->json(['success' => true, 'data' => new ConsumableResource($c)]);
        });
    }

    public function forceDestroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSystemAdmin()) {
            abort(403, 'Only System Admin can perform permanent deletion.');
        }
        return \DB::transaction(function () use ($request, $id) {
            $c = Consumable::withTrashed()->findOrFail($id);
            $snapshot = $c->only(['consumable_name','consumable_description','consumable_brand','current_quantity','threshold_limit','unit']);
            ConsumableLog::create([
                'consumable_id' => $id,
                'user_id' => optional($request->user())->id,
                'action' => 'force_deleted',
                'changes' => ['before' => $snapshot],
            ]);
            $c->forceDelete();
            return response()->json(['success' => true]);
        });
    }
    public function exportExcel()
    {
        $fileName = 'IT_CONSUMABLES_' . now()->format('Y_m_d_His') . '.xlsx';
        return Excel::download(new ConsumablesExport(), $fileName);
    }

    public function exportPdf()
    {
        $items = Consumable::orderBy('consumable_name')->get();
        $pdf = Pdf::loadView('consumables.pdf', ['items' => $items]);
        return $pdf->download('it-consumables_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportUsagePdf()
    {
        $items = ConsumableUsage::with('consumable')->orderByDesc('date_used')->get();
        $pdf = Pdf::loadView('consumables.usage_pdf', ['items' => $items]);
        return $pdf->download('it-consumables-usage_' . now()->format('Ymd_His') . '.pdf');
    }

    public function importExcel(Request $request): JsonResponse
    {
        $data = $request->all();
        $validator = validator($data, [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }
        $rows = \Maatwebsite\Excel\Facades\Excel::toArray(null, $request->file('file'))[0] ?? [];
        foreach (array_slice($rows, 1) as $row) {
            $name = $row[0] ?? null;
            if (!$name) { continue; }
            $desc = $row[1] ?? null;
            $brand = $row[2] ?? null;
            $qty = (int) ($row[3] ?? 0);
            $threshold = (int) ($row[4] ?? 0);
            $unit = $row[5] ?? 'pcs';
            $c = Consumable::create([
                'consumable_name' => (string) $name,
                'consumable_description' => $desc ? (string) $desc : null,
                'consumable_brand' => $brand ? (string) $brand : null,
                'current_quantity' => max(0, $qty),
                'threshold_limit' => max(0, $threshold),
                'unit' => (string) $unit,
            ]);
            ConsumableLog::create([
                'consumable_id' => $c->id,
                'user_id' => optional($request->user())->id,
                'action' => 'created',
                'changes' => ['after' => $c->only(['consumable_name','consumable_description','consumable_brand','current_quantity','threshold_limit','unit'])],
            ]);
        }
        return response()->json(['success' => true]);
    }

    public function logs(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('perPage', 10);
        $q = ConsumableLog::query()->with(['consumable','user'])->orderByDesc('created_at');
        if ($consumableId = (int) $request->get('consumable_id', 0)) { $q->where('consumable_id', $consumableId); }
        if ($userId = (int) $request->get('user_id', 0)) { $q->where('user_id', $userId); }
        if ($action = $request->get('action')) { $q->where('action', $action); }
        if ($from = $request->get('date_from')) { $q->whereDate('created_at', '>=', $from); }
        if ($to = $request->get('date_to')) { $q->whereDate('created_at', '<=', $to); }
        $items = $q->paginate($perPage)->onEachSide(1);

        return response()->json([
            'success' => true,
            'data' => ConsumableLogResource::collection($items->items()),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}

