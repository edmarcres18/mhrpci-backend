<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedConsumable extends Model
{
    use HasFactory;

    protected $table = 'deleted_consumables';

    protected $fillable = [
        'consumable_id',
        'consumable_name',
        'consumable_description',
        'consumable_brand',
        'current_quantity',
        'threshold_limit',
        'unit',
        'deleted_at',
        'deleted_by',
        'restore_status',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'restore_status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function consumable()
    {
        return $this->belongsTo(Consumable::class, 'consumable_id');
    }
}

