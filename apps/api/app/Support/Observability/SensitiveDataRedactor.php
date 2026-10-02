<?php

namespace App\Support\Observability;

class SensitiveDataRedactor
{
    private const REDACTED = '[REDACTED]';

    /** @var array<int, string> */
    private const SENSITIVE_KEYS = [
        'authorization',
        'cookie',
        'set-cookie',
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'api_secret',
        'app_key',
        'db_password',
        'otp',
    ];

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $context[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }

        return $context;
    }
}
