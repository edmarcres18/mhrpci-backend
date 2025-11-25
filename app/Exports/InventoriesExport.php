<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class InventoriesExport implements WithMultipleSheets, WithProperties
{
    protected ?string $accountable;

    public function __construct(?string $accountable = null)
    {
        $this->accountable = $accountable;
    }

    public function sheets(): array
    {
        if ($this->accountable) {
            return [new InventoriesSheet($this->accountable)];
        }

        $accountables = Inventory::query()
            ->select('inventory_accountable')
            ->distinct()
            ->pluck('inventory_accountable')
            ->all();

        return array_map(fn ($acc) => new InventoriesSheet($acc), $accountables);
    }

    public function properties(): array
    {
        $appName = config('app.name');
        return [
            'creator' => $appName,
            'lastModifiedBy' => $appName,
            'title' => $this->accountable ? "IT Inventories — {$this->accountable}" : 'IT Inventories — All',
            'description' => 'IT Inventory export generated from the application',
            'subject' => 'IT Inventories',
            'company' => $appName,
            'manager' => $appName,
            'category' => 'Reports',
        ];
    }
}
