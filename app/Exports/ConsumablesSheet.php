<?php

namespace App\Exports;

use App\Models\Consumable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ConsumablesSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function collection(): Collection
    {
        return Consumable::query()->orderBy('consumable_name')->get([
            'consumable_name',
            'consumable_description',
            'consumable_brand',
            'current_quantity',
            'threshold_limit',
            'unit',
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Description', 'Brand', 'Quantity', 'Threshold', 'Unit'];
    }

    public function title(): string
    {
        return 'Consumables';
    }
}
