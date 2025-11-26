<?php

namespace App\Exports;

use App\Models\ConsumableUsage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ConsumableUsagesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function collection(): Collection
    {
        return ConsumableUsage::query()
            ->with('consumable')
            ->orderByDesc('date_used')
            ->get()
            ->map(function ($u) {
                return [
                    optional($u->consumable)->consumable_name,
                    $u->quantity_used,
                    $u->purpose,
                    $u->used_by,
                    optional($u->date_used)->format('Y-m-d'),
                    $u->notes,
                ];
            });
    }

    public function headings(): array
    {
        return ['Consumable', 'Quantity Used', 'Purpose', 'Used By', 'Date Used', 'Notes'];
    }

    public function title(): string
    {
        return 'Usage History';
    }
}

