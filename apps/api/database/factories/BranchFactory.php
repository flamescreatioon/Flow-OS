<?php

namespace Database\Factories;

use App\Enums\BranchStatus;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country_code' => 'NG',
            'is_primary' => false,
            'status' => BranchStatus::Active,
        ];
    }
}
