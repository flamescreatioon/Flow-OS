<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationEnforcementTest extends TestCase
{
    public function test_unverified_user_can_read_but_cannot_write(): void
    {
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/organizations')->assertOk();
        $this->getJson('/api/v1/auth/me')->assertOk();

        $this->postJson('/api/v1/organizations', ['name' => 'Blocked Org'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_verified_user_can_write(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/organizations', ['name' => 'Allowed Org'])->assertCreated();
    }
}
