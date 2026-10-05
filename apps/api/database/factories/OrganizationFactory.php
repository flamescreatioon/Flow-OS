<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'timezone' => 'Africa/Lagos',
            'currency' => 'NGN',
            'settings' => [],
            'status' => OrganizationStatus::Active,
        ];
    }
}
