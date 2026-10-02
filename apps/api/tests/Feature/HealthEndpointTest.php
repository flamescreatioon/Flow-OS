<?php

namespace Tests\Feature;

use Ramsey\Uuid\Uuid;
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

        $response->assertHeader('X-Request-ID');
        $this->assertTrue(Uuid::isValid($response->headers->get('X-Request-ID')));
    }

    public function test_valid_client_request_id_is_preserved(): void
    {
        $requestId = '018f7c52-0d31-7f54-9f18-a1fe57c6f83d';

        $this->withHeader('X-Request-ID', $requestId)
            ->get('/api/v1/health')
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId);
    }

    public function test_invalid_client_request_id_is_replaced(): void
    {
        $response = $this->withHeader('X-Request-ID', "invalid\nrequest-id")
            ->get('/api/v1/health')
            ->assertOk();

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertTrue(Uuid::isValid($requestId));
        $this->assertNotSame("invalid\nrequest-id", $requestId);
    }

    public function test_unknown_api_route_uses_standard_error_envelope(): void
    {
        $this->getJson('/api/v1/unknown')
            ->assertNotFound()
            ->assertHeader('X-Request-ID')
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
