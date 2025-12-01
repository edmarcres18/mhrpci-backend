<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_accountable',
        'inventory_name',
        'inventory_specification',
        'inventory_brand',
        'inventory_status',
    ];
}
