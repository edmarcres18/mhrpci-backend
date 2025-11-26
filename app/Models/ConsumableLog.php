<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumableLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumable_id',
        'user_id',
        'action',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

