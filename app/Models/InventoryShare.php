<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_accountable',
        'token',
        'access_type',
        'allowed_emails',
        'expires_at',
        'created_by',
        'revoked_at',
        'scope',
        'accountable_list',
    ];

    protected $casts = [
        'allowed_emails' => 'array',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'accountable_list' => 'array',
    ];

    public function accesses()
    {
        return $this->hasMany(InventoryShareAccess::class);
    }
}
