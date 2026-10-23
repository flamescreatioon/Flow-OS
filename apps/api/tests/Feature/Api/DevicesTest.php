<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DevicesTest extends TestCase
{
    public function test_user_can_register_list_and_revoke_a_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/v1/devices', ['platform' => 'android', 'device_name' => 'Pixel'])
            ->assertCreated()->json('data');

        $this->getJson('/api/v1/devices')->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $this->postJson("/api/v1/devices/{$created['id']}/revoke")->assertOk()
            ->assertJsonPath('data.id', $created['id']);

        $this->assertNotNull(Device::findOrFail($created['id'])->revoked_at);
    }

    public function test_users_cannot_revoke_each_others_devices(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $device = Device::factory()->for($owner)->create();

        Sanctum::actingAs($other);
        $this->postJson("/api/v1/devices/{$device->id}/revoke")->assertNotFound();
    }
}
