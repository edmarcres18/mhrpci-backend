<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumableUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumable_id',
        'quantity_used',
        'purpose',
        'used_by',
        'date_used',
        'notes',
    ];

    protected $casts = [
        'date_used' => 'date',
    ];

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }
}
