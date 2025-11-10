<?php

namespace App\Http\Controllers;

use App\Models\ItInventory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ItInventoryController extends Controller
{
    /**
     * Allowed statuses for inventories.
     */
    protected array $validStatuses = ['stock', 'in_use', 'repair', 'retired', 'lost'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access IT inventories.');
        }

        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $perPage = (int) $request->query('perPage', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $query = ItInventory::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('asset_tag', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('supplier', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && in_array($status, $this->validStatuses, true)) {
            $query->where('status', $status);
        }

        $inventories = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (ItInventory $inv) {
                return [
                    'id' => $inv->id,
                    'asset_tag' => $inv->asset_tag,
                    'category' => $inv->category,
                    'type' => $inv->type,
                    'brand' => $inv->brand,
                    'model' => $inv->model,
                    'serial_number' => $inv->serial_number,
                    'status' => $inv->status,
                    'location' => $inv->location,
                    'assigned_to' => $inv->assignedTo?->only(['id', 'name']),
                    'created_at' => optional($inv->created_at)->toDateTimeString(),
                ];
            });

        return Inertia::render('ItInventories/Index', [
            'inventories' => $inventories,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'perPage' => $perPage,
            ],
            'statuses' => $this->validStatuses,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access IT inventories.');
        }

        $users = User::orderBy('name')->get(['id', 'name']);
        $categories = ['Laptop', 'Desktop', 'Server', 'Peripheral', 'Network', 'Software', 'Other'];

        return Inertia::render('ItInventories/Create', [
            'statuses' => $this->validStatuses,
            'categories' => $categories,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to manage IT inventories.');
        }

        $validated = $request->validate([
            'asset_tag' => ['required', 'string', 'max:100', 'unique:it_inventories,asset_tag'],
            'category' => ['required', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:150', 'unique:it_inventories,serial_number'],
            'status' => ['required', 'string', Rule::in($this->validStatuses)],
            'location' => ['nullable', 'string', 'max:150'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'warranty_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ItInventory::create($validated);

        return redirect()
            ->route('it-inventories.index')
            ->with('success', 'Inventory item created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ItInventory $itInventory): Response
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access IT inventories.');
        }

        return Inertia::render('ItInventories/Show', [
            'inventory' => [
                'id' => $itInventory->id,
                'asset_tag' => $itInventory->asset_tag,
                'category' => $itInventory->category,
                'type' => $itInventory->type,
                'brand' => $itInventory->brand,
                'model' => $itInventory->model,
                'serial_number' => $itInventory->serial_number,
                'status' => $itInventory->status,
                'location' => $itInventory->location,
                'assigned_to' => $itInventory->assignedTo?->only(['id', 'name']),
                'purchase_date' => optional($itInventory->purchase_date)?->toDateString(),
                'purchase_cost' => $itInventory->purchase_cost,
                'supplier' => $itInventory->supplier,
                'warranty_expires_at' => optional($itInventory->warranty_expires_at)?->toDateString(),
                'notes' => $itInventory->notes,
                'created_at' => optional($itInventory->created_at)->toDateTimeString(),
                'updated_at' => optional($itInventory->updated_at)->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItInventory $itInventory): Response
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to manage IT inventories.');
        }

        $users = User::orderBy('name')->get(['id', 'name']);
        $categories = ['Laptop', 'Desktop', 'Server', 'Peripheral', 'Network', 'Software', 'Other'];

        return Inertia::render('ItInventories/Edit', [
            'inventory' => [
                'id' => $itInventory->id,
                'asset_tag' => $itInventory->asset_tag,
                'category' => $itInventory->category,
                'type' => $itInventory->type,
                'brand' => $itInventory->brand,
                'model' => $itInventory->model,
                'serial_number' => $itInventory->serial_number,
                'status' => $itInventory->status,
                'location' => $itInventory->location,
                'assigned_to_user_id' => $itInventory->assigned_to_user_id,
                'purchase_date' => optional($itInventory->purchase_date)?->toDateString(),
                'purchase_cost' => $itInventory->purchase_cost,
                'supplier' => $itInventory->supplier,
                'warranty_expires_at' => optional($itInventory->warranty_expires_at)?->toDateString(),
                'notes' => $itInventory->notes,
            ],
            'statuses' => $this->validStatuses,
            'categories' => $categories,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItInventory $itInventory)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to manage IT inventories.');
        }

        $validated = $request->validate([
            'asset_tag' => ['required', 'string', 'max:100', Rule::unique('it_inventories', 'asset_tag')->ignore($itInventory->id)],
            'category' => ['required', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:150', Rule::unique('it_inventories', 'serial_number')->ignore($itInventory->id)],
            'status' => ['required', 'string', Rule::in($this->validStatuses)],
            'location' => ['nullable', 'string', 'max:150'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'warranty_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $itInventory->update($validated);

        return redirect()
            ->route('it-inventories.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItInventory $itInventory)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to manage IT inventories.');
        }

        $itInventory->delete();

        return redirect()->route('it-inventories.index')->with('success', 'Inventory item deleted successfully.');
    }
}