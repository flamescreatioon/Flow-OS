<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<OrganizationMembership>
 */
class OrganizationMembershipFactory extends Factory
{
    protected $model = OrganizationMembership::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'suspended_at' => null,
            'revoked_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => MembershipStatus::Active, 'joined_at' => now(), 'suspended_at' => null, 'revoked_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => MembershipStatus::Suspended, 'suspended_at' => now(), 'revoked_at' => null]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['status' => MembershipStatus::Revoked, 'revoked_at' => now()]);
    }
}
