<?php

namespace Tests\Feature\Api;

use App\Models\IdempotencyKey;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    public function test_same_key_and_payload_replays_the_same_organization(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/v1/organizations', ['name' => 'Replay Org'], ['Idempotency-Key' => 'key-1'])
            ->assertCreated()->json('data');
        $second = $this->postJson('/api/v1/organizations', ['name' => 'Replay Org'], ['Idempotency-Key' => 'key-1'])
            ->assertCreated()->json('data');

        $this->assertSame($first['id'], $second['id']);
        $this->assertDatabaseCount('organizations', 1);
    }

    public function test_same_key_with_different_payload_conflicts(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/organizations', ['name' => 'First Org'], ['Idempotency-Key' => 'key-2'])
            ->assertCreated();

        $this->postJson('/api/v1/organizations', ['name' => 'Second Org'], ['Idempotency-Key' => 'key-2'])
            ->assertConflict()
            ->assertJsonPath('error.code', 'IDEMPOTENCY_CONFLICT');
    }

    public function test_different_keys_create_distinct_organizations(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/organizations', ['name' => 'Org One'], ['Idempotency-Key' => 'key-a'])->assertCreated();
        $response = $this->postJson('/api/v1/organizations', ['name' => 'Org One'], ['Idempotency-Key' => 'key-b'])->assertCreated();

        $this->assertNotEmpty($response->json('data.id'));
        $this->assertDatabaseCount('organizations', 2);
    }

    public function test_duplicate_keys_without_an_organization_are_rejected_by_the_database(): void
    {
        $user = User::factory()->create();

        $this->reserveKey($user, null, 'null-organization-key');

        $this->assertDatabaseCount('idempotency_keys', 1);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->reserveKey($user, null, 'null-organization-key');
    }

    public function test_duplicate_keys_within_an_organization_are_rejected_by_the_database(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->reserveKey($user, $organization->id, 'organization-key');

        $this->assertDatabaseCount('idempotency_keys', 1);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->reserveKey($user, $organization->id, 'organization-key');
    }

    public function test_a_key_reserved_by_an_in_flight_request_conflicts_without_executing_the_operation(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $payload = ['name' => 'In Flight Org'];
        $this->reserveKey($user, null, 'in-flight-key', 'processing', $payload);

        $this->postJson('/api/v1/organizations', $payload, ['Idempotency-Key' => 'in-flight-key'])
            ->assertConflict()
            ->assertJsonPath('error.code', 'IDEMPOTENCY_CONFLICT');

        $this->assertDatabaseCount('organizations', 0);
    }

    public function test_an_expired_reservation_is_renewed_instead_of_conflicting(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $reservation = $this->reserveKey($user, null, 'expired-key', 'processing');
        $reservation->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->postJson('/api/v1/organizations', ['name' => 'Renewed Org'], ['Idempotency-Key' => 'expired-key'])
            ->assertCreated();

        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    /**
     * Insert an idempotency key reservation directly so that the database
     * level uniqueness guarantees can be exercised on their own.
     */
    protected function reserveKey(
        User $user,
        ?string $organizationId,
        string $key,
        string $status = 'completed',
        array $payload = []
    ): IdempotencyKey {
        return IdempotencyKey::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organizationId,
            'route' => 'api/v1/organizations',
            'key' => $key,
            'request_hash' => hash('sha256', json_encode($payload)),
            'status' => $status,
            'response_code' => $status === 'completed' ? 201 : 0,
            'response_body' => [],
            'expires_at' => now()->addDay(),
        ]);
    }
}
