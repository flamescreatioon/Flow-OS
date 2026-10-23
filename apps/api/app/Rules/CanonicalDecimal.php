<?php

namespace App\Rules;

use App\Domain\Shared\ValueObjects\ScaledDecimal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * Accepts only a canonical decimal string with at most the given number of
 * decimal places.
 *
 * The value is transported as a string so that no client ever has to parse it
 * as a binary floating point number. The rule matches the canonicalisation
 * rules of the shared TypeScript money parser.
 */
class CanonicalDecimal implements ValidationRule
{
    public function __construct(private readonly int $maxScale) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail("The {$attribute} must be a decimal string.");

            return;
        }

        try {
            ScaledDecimal::parse($value, $this->maxScale);
        } catch (InvalidArgumentException $exception) {
            $fail("The {$attribute} must be a canonical decimal string with at most {$this->maxScale} decimal places.");
        }
    }
}
