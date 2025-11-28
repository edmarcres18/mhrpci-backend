<?php

namespace App\Exports;

use App\Models\CompanyPhone;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CompanyPhonesExport implements FromCollection, WithHeadings, WithProperties, ShouldAutoSize
{
    public function collection(): Collection
    {
        return CompanyPhone::query()
            ->orderBy('department')
            ->orderBy('phone_number')
            ->get(['department', 'phone_number', 'person_in_charge', 'position', 'extension']);
    }

    public function headings(): array
    {
        return ['Department', 'Phone Number', 'Person In Charge', 'Position', 'Extension'];
    }

    public function properties(): array
    {
        $app = config('app.name');
        return [
            'creator' => $app,
            'lastModifiedBy' => $app,
            'title' => 'Company Phones Directory',
            'description' => 'Export of company phone records',
            'subject' => 'Company Phones',
            'company' => $app,
            'category' => 'Reports',
        ];
    }
}

