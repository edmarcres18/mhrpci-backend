<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItInventoryRequest;
use App\Models\ItInventory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ItInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access inventory management.');
        }

        $search = (string) $request->query('search', '');
        $perPage = (int) $request->query('perPage', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $query = ItInventory::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('inventory_name', 'like', "%{$search}%")
                  ->orWhere('accountable_by_name', 'like', "%{$search}%")
                  ->orWhere('descriptions', 'like', "%{$search}%");
            });
        }

        $inventories = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($inv) {
                return [
                    'id' => $inv->id,
                    'inventory_name' => $inv->inventory_name,
                    'accountable_by_name' => $inv->accountable_by_name,
                    'created_at' => optional($inv->created_at)->toDateTimeString(),
                ];
            });

        return Inertia::render('ItInventories/Index', [
            'inventories' => $inventories,
            'filters' => [
                'search' => $search,
                'perPage' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access inventory management.');
        }

        return Inertia::render('ItInventories/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ItInventoryRequest $request): RedirectResponse
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access inventory management.');
        }

        $validated = $request->validated();

        ItInventory::create($validated);

        return redirect()
            ->route('it-inventories.index')
            ->with('success', 'Inventory created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ItInventory $itInventory): Response
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access inventory management.');
        }

        return Inertia::render('ItInventories/Show', [
            'inventory' => [
                'id' => $itInventory->id,
                'inventory_name' => $itInventory->inventory_name,
                'descriptions' => $itInventory->descriptions,
                'accountable_by_name' => $itInventory->accountable_by_name,
                'remarks' => $itInventory->remarks,
                'created_at' => optional($itInventory->created_at)->toDateTimeString(),
                'updated_at' => optional($itInventory->updated_at)->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItInventory $itInventory): Response
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access inventory management.');
        }

        return Inertia::render('ItInventories/Edit', [
            'inventory' => [
                'id' => $itInventory->id,
                'inventory_name' => $itInventory->inventory_name,
                'descriptions' => $itInventory->descriptions,
                'accountable_by_name' => $itInventory->accountable_by_name,
                'remarks' => $itInventory->remarks,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ItInventoryRequest $request, ItInventory $itInventory): RedirectResponse
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access inventory management.');
        }

        $validated = $request->validated();

        $itInventory->update($validated);

        return redirect()
            ->route('it-inventories.index')
            ->with('success', 'Inventory updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItInventory $itInventory): RedirectResponse
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access inventory management.');
        }

        $itInventory->delete();

        return redirect()
            ->route('it-inventories.index')
            ->with('success', 'Inventory deleted successfully.');
    }
}