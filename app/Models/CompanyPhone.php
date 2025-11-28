<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'department',
        'phone_number',
        'person_in_charge',
        'position',
        'extension',
    ];
}

