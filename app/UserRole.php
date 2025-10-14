<?php

namespace App;

enum UserRole: string
{
    case SYSTEM_ADMIN = 'system_admin';
    case ADMIN = 'admin';
    case STAFF = 'staff';

    /**
     * Get the display name for the role.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::SYSTEM_ADMIN => 'System Admin',
            self::ADMIN => 'Admin',
            self::STAFF => 'Staff',
        };
    }

    /**
     * Get all available roles.
     *
     * @return array<UserRole>
     */
    public static function all(): array
    {
        return [
            self::SYSTEM_ADMIN,
            self::ADMIN,
            self::STAFF,
        ];
    }

    /**
     * Get all role values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::all());
    }
}
