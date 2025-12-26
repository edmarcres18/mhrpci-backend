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
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoriesSummaryExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
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

        $normalizedLocation = strtoupper(trim((string) $location));
        $mhrpciLocations = [
            '1ST FLOOR',
            '2ND FLOOR',
            '3RD FLOOR',
            '4TH FLOOR',
            'WAREHOUSE',
            'VIRTUAL ROOM',
            'GORORDO',
            'MAKATI',
            'CDO',
        ];
        $company = match ($normalizedLocation) {
            'BGPDI' => 'BGPDI',
            'VHI' => 'VHI',
            default => in_array($normalizedLocation, $mhrpciLocations, true) ? 'MHRPCI' : '',
        };

        return [
            ++$this->rowNumber,
            $company,
            (string) $inventory->inventory_accountable,
            (string) $inventory->inventory_name,
            (string) ($inventory->inventory_specification ?? ''),
            (string) ($inventory->inventory_brand ?? ''),
            (string) $inventory->item_code,
            (string) ($location ?? ''),
            (string) $inventory->inventory_status,
            1,
        ];
    }

    public function headings(): array
    {
        return ['NO.', 'COMPANY', 'END USER', 'ITEM NAME', 'ITEM SPECIFICATION', 'ITEM BRAND', 'ITEM CODE', 'LOCATION', 'STATUS', 'QTY'];
    }

    public function title(): string
    {
        return 'IT INVENTORIES';
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

                $sheet->getStyle('A1:J1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF0F4C81'],
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
                $sheet->setAutoFilter('A1:J1');

                $highestRow = $sheet->getHighestRow();
                if ($highestRow >= 2) {
                    $sheet->getStyle("A2:J{$highestRow}")->applyFromArray([
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
                            $sheet->getStyle("A{$row}:J{$row}")
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setARGB('FFF9FAFB');
                        }
                    }

                    $sheet->getStyle("A1:J{$highestRow}")->applyFromArray([
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

                $protection = $sheet->getProtection();
                $protection->setPassword('mhrpci-admin@2025');
                $protection->setSheet(true);
                $protection->setSort(true);
                $protection->setAutoFilter(true);

                $workbookSecurity = $sheet->getParent()->getSecurity();
                $workbookSecurity->setLockStructure(true);
                $workbookSecurity->setWorkbookPassword('mhrpci-admin@2025');
            },
        ];
    }
}
