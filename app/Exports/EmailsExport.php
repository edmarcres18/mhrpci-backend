<?php

namespace App\Exports;

use App\Models\Email;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EmailsExport implements FromCollection, WithHeadings, WithProperties, ShouldAutoSize, WithStyles, WithEvents
{
    public function collection(): Collection
    {
        return Email::query()
            ->orderBy('department')
            ->orderBy('email')
            ->get(['department', 'email', 'password', 'person_in_charge', 'position'])
            ->map(function (Email $e) {
                $pwd = null;
                try {
                    $pwd = $e->password ? Crypt::decryptString($e->password) : null;
                } catch (\Throwable $ex) {
                    $pwd = $e->password; // legacy/plain if not encrypted
                }
                return [
                    'department' => $e->department,
                    'email' => $e->email,
                    'password' => $pwd,
                    'person_in_charge' => $e->person_in_charge,
                    'position' => $e->position,
                ];
            });
    }

    public function headings(): array
    {
        return ['Department', 'Email', 'Password', 'Person In Charge', 'Position'];
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

    public function properties(): array
    {
        $app = config('app.name');
        return [
            'creator' => $app,
            'lastModifiedBy' => $app,
            'title' => 'Emails Directory',
            'description' => 'Export of managed email records',
            'subject' => 'Emails',
            'company' => $app,
            'category' => 'Reports',
        ];
    }
}
