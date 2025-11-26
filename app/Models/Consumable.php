<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consumable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'consumable_name',
        'consumable_description',
        'consumable_brand',
        'current_quantity',
        'threshold_limit',
        'unit',
    ];

    public function usages()
    {
        return $this->hasMany(ConsumableUsage::class);
    }
}

