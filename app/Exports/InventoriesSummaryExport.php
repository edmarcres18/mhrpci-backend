<?php

namespace App\Exports;

use App\Models\Inventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoriesSummaryExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected int $rowNumber = 0;

    public function collection(): Collection
    {
        return Inventory::query()
            ->orderBy('inventory_accountable')
            ->orderBy('inventory_name')
            ->get([
                'inventory_accountable',
                'location',
                'item_code',
                'inventory_name',
                'inventory_specification',
                'inventory_brand',
                'inventory_status',
            ]);
    }

    public function map($inventory): array
    {
        $location = $inventory->location;
        if ($location instanceof \BackedEnum) {
            $location = $location->value;
        }

        return [
            ++$this->rowNumber,
            (string) $inventory->inventory_accountable,
            (string) ($location ?? ''),
            (string) $inventory->item_code,
            (string) $inventory->inventory_name,
            (string) ($inventory->inventory_specification ?? ''),
            (string) ($inventory->inventory_brand ?? ''),
            (string) $inventory->inventory_status,
        ];
    }

    public function headings(): array
    {
        return ['NO.', 'END USER', 'LOCATION', 'ITEM CODE', 'ITEM NAME', 'ITEM SPECIFICATION', 'ITEM BRAND', 'STATUS'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()
            ->setName('Calibri')
            ->setSize(11);

        $sheet->setShowGridlines(false);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FF111827'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF3F4F6'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFE5E7EB'],
                        ],
                    ],
                ]);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:H1');

                $highestRow = $sheet->getHighestRow();
                if ($highestRow >= 2) {
                    $sheet->getStyle("A2:H{$highestRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFE5E7EB'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'wrapText' => true,
                        ],
                    ]);

                    for ($row = 2; $row <= $highestRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:H{$row}")
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setARGB('FFF9FAFB');
                        }
                    }

                    $sheet->getStyle("A1:H{$highestRow}")->applyFromArray([
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['argb' => 'FFD1D5DB'],
                            ],
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFE5E7EB'],
                            ],
                        ],
                    ]);
                }
            },
        ];
    }
}
