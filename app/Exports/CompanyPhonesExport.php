<?php

namespace App\Exports;

use App\Models\CompanyPhone;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CompanyPhonesExport implements FromCollection, WithHeadings, WithProperties, ShouldAutoSize, WithStyles, WithEvents, WithTitle
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '111827'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $rangeAll = "A1:{$highestColumn}{$highestRow}";
                $rangeHeader = 'A1:E1';

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$highestColumn}1");

                $sheet->getStyle($rangeAll)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('D1D5DB'));

                $sheet->getStyle($rangeHeader)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    public function title(): string
    {
        return 'Company Phone ' . now()->format('Y') . ' exported';
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
