<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBiometric extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'device_id',
        'biometric_key',
    ];

    /**
     * Get the user that owns the biometric.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
