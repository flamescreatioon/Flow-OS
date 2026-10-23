<?php

namespace App\Support\Mfa;

use RuntimeException;

/**
 * Raised when a multi-factor operation cannot be completed. The reason is a
 * stable machine-readable code so that the HTTP layer does not have to
 * interpret an error message.
 */
class MfaException extends RuntimeException
{
    public const CODE_INVALID = 'MFA_CODE_INVALID';

    public const CODE_CHALLENGE_INVALID = 'MFA_CHALLENGE_INVALID';

    public const CODE_LOCKED = 'MFA_LOCKED';

    public const CODE_NOT_ENROLLED = 'MFA_NOT_ENROLLED';

    public const CODE_NO_PENDING_ENROLLMENT = 'MFA_NO_PENDING_ENROLLMENT';

    public const CODE_ALREADY_ENROLLED = 'MFA_ALREADY_ENROLLED';

    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
