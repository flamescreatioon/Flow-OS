<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }
}
