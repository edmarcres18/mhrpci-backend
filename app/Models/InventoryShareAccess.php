<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryShareAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_share_id',
        'email',
        'ip',
        'user_agent',
        'success',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    public function share()
    {
        return $this->belongsTo(InventoryShare::class, 'inventory_share_id');
    }
}

