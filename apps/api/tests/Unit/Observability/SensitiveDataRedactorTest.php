<?php

namespace Tests\Unit\Observability;

use App\Support\Observability\SensitiveDataRedactor;
use PHPUnit\Framework\TestCase;

class SensitiveDataRedactorTest extends TestCase
{
    public function test_sensitive_values_are_redacted_recursively(): void
    {
        $redacted = SensitiveDataRedactor::redact([
            'Authorization' => 'Bearer secret',
            'metadata' => [
                'password' => 'not-for-logs',
                'safe_id' => 'user-123',
            ],
        ]);

        $this->assertSame('[REDACTED]', $redacted['Authorization']);
        $this->assertSame('[REDACTED]', $redacted['metadata']['password']);
        $this->assertSame('user-123', $redacted['metadata']['safe_id']);
    }
}
