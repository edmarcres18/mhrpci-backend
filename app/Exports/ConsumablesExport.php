<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class ConsumablesExport implements WithMultipleSheets, WithProperties
{
    public function sheets(): array
    {
        return [
            new ConsumablesSheet(),
            new ConsumableUsagesSheet(),
        ];
    }

    public function properties(): array
    {
        $app = config('app.name');
        return [
            'creator' => $app,
            'lastModifiedBy' => $app,
            'title' => 'IT Consumables',
            'description' => 'Consumables and usage export',
            'subject' => 'IT Consumables',
            'company' => $app,
            'category' => 'Reports',
        ];
    }
}

