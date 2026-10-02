<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    public function test_credentialed_cors_uses_explicit_origins(): void
    {
        $this->assertTrue(config('cors.supports_credentials'));
        $this->assertNotContains('*', config('cors.allowed_origins'));
        $this->assertContains('http://localhost:3000', config('cors.allowed_origins'));
    }
}
