<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItInventory extends Model
{
    protected $table = 'it_inventories';

    protected $fillable = [
        'asset_tag',
        'category',
        'type',
        'brand',
        'model',
        'serial_number',
        'status',
        'location',
        'assigned_to',
        'purchase_date',
        'purchase_cost',
        'supplier',
        'warranty_expires_at',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expires_at' => 'date',
        'purchase_cost' => 'decimal:2',
    ];

    // Relationship removed: assigned_to is stored as a free-form string
}
