<?php

namespace App\Enums;

enum InventoryLocation: string
{
    case FIRST_FLOOR = '1st Floor';
    case SECOND_FLOOR = '2nd Floor';
    case THIRD_FLOOR = '3rd Floor';
    case FOURTH_FLOOR = '4th Floor';
    case WAREHOUSE = 'Warehouse';
    case VIRTUAL_ROOM = 'Virtual Room';

    /**
     * @return array<InventoryLocation>
     */
    public static function all(): array
    {
        return [
            self::FIRST_FLOOR,
            self::SECOND_FLOOR,
            self::THIRD_FLOOR,
            self::FOURTH_FLOOR,
            self::WAREHOUSE,
            self::VIRTUAL_ROOM,
        ];
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $loc) => $loc->value, self::all());
    }
}
