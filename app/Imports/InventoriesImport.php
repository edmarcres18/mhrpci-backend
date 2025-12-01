<?php

namespace App\Imports;

use App\Models\Inventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InventoriesImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            '*' => new class implements ToCollection
            {
                public function collection(Collection $rows)
                {
                    $accountable = request()->input('accountable') ?? (request()->attributes->get('sheetName'));

                    foreach ($rows->skip(1) as $row) {
                        $name = data_get($row, 0);
                        $spec = data_get($row, 1);
                        $brand = data_get($row, 2);
                        $status = data_get($row, 3) ?: 'active';

                        if (! $name) {
                            continue;
                        }

                        Inventory::create([
                            'inventory_accountable' => (string) $accountable,
                            'inventory_name' => (string) $name,
                            'inventory_specification' => $spec ? (string) $spec : null,
                            'inventory_brand' => $brand ? (string) $brand : null,
                            'inventory_status' => (string) $status,
                        ]);
                    }
                }
            },
        ];
    }
}
