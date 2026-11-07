<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TaxSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<TaxSetting>
 */
class TaxSettingFactory extends Factory
{
    protected $model = TaxSetting::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'tax_enabled' => false,
            'tax_rate_percent' => null,
            'tax_mode' => 'exclusive',
        ];
    }

    public function vatExclusive(): static
    {
        return $this->state(fn (): array => [
            'tax_enabled' => true,
            'tax_rate_percent' => 75000,
            'tax_mode' => 'exclusive',
        ]);
    }

    public function vatInclusive(): static
    {
        return $this->state(fn (): array => [
            'tax_enabled' => true,
            'tax_rate_percent' => 75000,
            'tax_mode' => 'inclusive',
        ]);
    }
}
