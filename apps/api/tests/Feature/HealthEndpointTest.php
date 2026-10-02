<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'flowos-api',
            ]);
    }

    public function test_unknown_api_route_uses_standard_error_envelope(): void
    {
        $this->getJson('/api/v1/unknown')
            ->assertNotFound()
            ->assertJson([
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Resource not found.',
                ],
            ]);
    }

    public function test_configured_first_party_origin_can_make_credentialed_api_requests(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:3000',
        ])->get('/api/v1/health')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }
}
