<?php

namespace App\Exports;

use App\Models\Email;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EmailsExport implements FromCollection, WithHeadings, WithProperties, ShouldAutoSize
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
