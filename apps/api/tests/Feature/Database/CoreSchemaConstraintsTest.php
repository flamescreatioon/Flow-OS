<?php

namespace Tests\Feature\Database;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoreSchemaConstraintsTest extends TestCase
{
    public function test_user_email_and_organization_slug_are_globally_unique(): void
    {
        User::factory()->create(['email' => 'unique@flowos.test']);
        Organization::factory()->create(['slug' => 'unique-org']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'unique@flowos.test']);
    }

    public function test_organization_slug_duplicate_is_rejected(): void
    {
        Organization::factory()->create(['slug' => 'unique-org']);

        $this->expectException(QueryException::class);
        Organization::factory()->create(['slug' => 'unique-org']);
    }

    public function test_membership_and_branch_foreign_keys_restrict_parent_deletion(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Foreign-key delete semantics are verified by the PostgreSQL suite.');
        }

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->create();
        Branch::factory()->for($organization)->create();

        try {
            DB::transaction(fn () => $user->delete());
            $this->fail('Membership foreign key should restrict deleting its user.');
        } catch (QueryException) {
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }

        try {
            DB::transaction(fn () => $organization->delete());
            $this->fail('Branch and membership foreign keys should restrict deleting their organization.');
        } catch (QueryException) {
            $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
        }
    }

    public function test_uuid_primary_keys_are_persisted_as_identifiers(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $organization->id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $user->id);
    }
}
