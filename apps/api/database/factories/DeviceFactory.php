<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'user_id' => User::factory(),
            'platform' => fake()->randomElement(['ios', 'android', 'web', 'desktop']),
            'device_name' => fake()->word(),
            'last_seen_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
