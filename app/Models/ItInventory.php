<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItInventory extends Model
{
    protected $fillable = [
        'inventory_name',
        'descriptions',
        'accountable_by_name',
        'remarks',
    ];
}
