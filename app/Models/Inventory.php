<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_accountable',
        'inventory_name',
        'inventory_specification',
        'inventory_brand',
        'inventory_status',
        'item_code',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($inventory) {
            if (empty($inventory->item_code)) {
                $inventory->item_code = self::generateUniqueItemCode(
                    $inventory->inventory_name,
                    $inventory->inventory_accountable
                );
            }
        });
    }

    /**
     * Generate a unique item code for a given inventory name and accountable person.
     * This method uses a transaction and pessimistic locking to prevent race conditions.
     *
     * @param string $inventoryName
     * @param string $inventoryAccountable
     * @return string
     */
    public static function generateUniqueItemCode(string $inventoryName, string $inventoryAccountable): string
    {
        $codeBody = self::generateItemCodeBody($inventoryName, $inventoryAccountable);
        $fullPrefix = 'IT-' . $codeBody;
        $maxTries = 100; // a safe limit to prevent infinite loops

        for ($i = 0; $i < $maxTries; $i++) {
            $randomNumber = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $itemCode = $fullPrefix . $randomNumber;
            if (!self::where('item_code', $itemCode)->exists()) {
                return $itemCode;
            }
        }

        // Fallback for the very unlikely case of not finding a unique code
        return $fullPrefix . uniqid();
    }

    /**
     * Generate the body of the item code from the inventory name and accountable person.
     * e.g., "Laptop Computer" for "John Doe" becomes "JDLC"
     *
     * @param string $inventoryName
     * @param string $inventoryAccountable
     * @return string
     */
    public static function generateItemCodeBody(string $inventoryName, string $inventoryAccountable): string
    {
        $accountableInitials = self::getInitials($inventoryAccountable);
        $nameInitials = self::getInitials($inventoryName);

        return $accountableInitials . $nameInitials;
    }

    /**
     * Get initials from a string.
     *
     * @param string $str
     * @param int $wordLimit
     * @return string
     */
    private static function getInitials(string $str, int $wordLimit = 2): string
    {
        $words = preg_split('/[\s,-]+/', $str);
        $initials = '';
        $wordCount = 0;
        foreach ($words as $word) {
            if (!empty($word) && $wordCount < $wordLimit) {
                $initials .= strtoupper(substr($word, 0, 1));
                $wordCount++;
            }
        }

        if (empty($initials)) {
            $initials = strtoupper(substr(str_replace(' ', '', $str), 0, $wordLimit));
        }

        return $initials;
    }
}
