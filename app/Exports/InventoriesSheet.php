<?php

namespace App\Exports;

use App\Models\Inventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InventoriesSheet implements FromCollection, WithTitle, WithHeadings, WithEvents, WithStyles, WithColumnWidths
{
    protected string $accountable;

    public function __construct(string $accountable)
    {
        $this->accountable = $accountable;
    }

    public function collection(): Collection
    {
        return Inventory::query()
            ->where('inventory_accountable', $this->accountable)
            ->get(['inventory_name', 'inventory_specification', 'inventory_brand', 'inventory_status']);
    }

    public function headings(): array
    {
        return ['Name', 'Specification', 'Brand', 'Status'];
    }

    public function title(): string
    {
        return $this->accountable;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 36,
            'C' => 22,
            'D' => 14,
        ];
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

                $sheet->getStyle('A1:D1')->applyFromArray([
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
                $sheet->setAutoFilter('A1:D1');

                $highestRow = $sheet->getHighestRow();
                if ($highestRow >= 2) {
                    $sheet->getStyle("A2:D{$highestRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFE5E7EB'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                        ],
                    ]);
                    
                    $sheet->getStyle("B2:B{$highestRow}")->getAlignment()->setWrapText(true);

                    for ($row = 2; $row <= $highestRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');
                        }
                    }

                    $sheet->getStyle("A1:D{$highestRow}")->applyFromArray([
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['argb' => 'FFD1D5DB'],
                            ],
                        ],
                    ]);
                }
            },
        ];
    }
}
