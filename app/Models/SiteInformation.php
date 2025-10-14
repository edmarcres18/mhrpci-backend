<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteInformation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'site_informations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_name',
        'site_logo',
        'email_address',
        'tel_no',
        'phone_no',
        'telegram',
        'facebook',
        'viber',
        'whatsapp',
        'address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear API cache when site information is created
        static::created(function () {
            self::clearApiCache();
        });

        // Clear API cache when site information is updated
        static::updated(function () {
            self::clearApiCache();
        });

        // Clear API cache when site information is deleted
        static::deleted(function () {
            self::clearApiCache();
        });
    }

    /**
     * Clear all API-related caches for site information.
     */
    protected static function clearApiCache(): void
    {
        Cache::forget('site_info_settings');
        Cache::forget('site_info_email');
        Cache::forget('site_info_tel_no');
        Cache::forget('site_info_phone_no');
        Cache::forget('site_info_telegram');
        Cache::forget('site_info_facebook');
        Cache::forget('site_info_viber');
        Cache::forget('site_info_whatsapp');
        Cache::forget('site_info_address');
        Cache::forget('site_info_all_contacts');

        \Log::info('Site Information API cache cleared');
    }
}
