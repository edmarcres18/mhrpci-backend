<?php

namespace Database\Factories;

use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Use faker if available (dev), otherwise provide safe defaults (production)
        $faker = $this->faker ?? null;

        return [
            'name' => $faker ? $faker->name() : 'User '.Str::random(6),
            'email' => $faker ? $faker->unique()->safeEmail() : 'user'.Str::random(10).'@example.com',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::STAFF,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with system admin role.
     */
    public function systemAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SYSTEM_ADMIN,
        ]);
    }

    /**
     * Create a user with admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ADMIN,
        ]);
    }

    /**
     * Create a user with staff role.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::STAFF,
        ]);
    }
}
