<?php

namespace Database\Factories;

use App\Models\Organization;
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
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => 'viewer',
            'is_active' => true,
        ];
    }
}
