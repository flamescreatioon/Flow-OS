<?php

namespace Tests\Unit\Domain;

use App\Domain\Shared\ValueObjects\ScaledDecimal;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScaledDecimalTest extends TestCase
{
    public static function canonicalInputs(): array
    {
        return [
            'whole number' => ['12', 1200, 2],
            'two places' => ['12.34', 1234, 2],
            'one place' => ['12.3', 123, 1],
            'negative' => ['-12.34', -1234, 2],
            'negative fraction' => ['-0.50', -50, 2],
            'zero' => ['0', 0, 2],
            'negative zero' => ['-0.00', 0, 2],
            'leading zeros' => ['007.50', 750, 2],
        ];
    }

    #[DataProvider('canonicalInputs')]
    public function test_it_parses_a_canonical_decimal(string $input, int $expected, int $scale): void
    {
        $this->assertSame($expected, ScaledDecimal::parse($input, $scale)->value);
    }

    public static function rejectedInputs(): array
    {
        return [
            'empty' => [''],
            'only a sign' => ['-'],
            'leading plus' => ['+12.50'],
            'shorthand fraction' => ['.50'],
            'trailing point' => ['12.'],
            'multiple points' => ['12.5.5'],
            'thousands separator' => ['1,200.50'],
            'not a number' => ['abc'],
            'letters in the fraction' => ['12.5a'],
            'over precise' => ['12.999'],
        ];
    }

    #[DataProvider('rejectedInputs')]
    public function test_it_rejects_a_non_canonical_decimal(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        ScaledDecimal::parse($input, 2);
    }

    public function test_it_renders_the_canonical_string(): void
    {
        $this->assertSame('12.34', ScaledDecimal::parse('12.34', 2)->toString());
        $this->assertSame('12.3400', ScaledDecimal::parse('12.34', 4)->toString());
        $this->assertSame('-12.340', ScaledDecimal::parse('-12.34', 3)->toString());
        $this->assertSame('0.00', ScaledDecimal::of(0, 2)->toString());
        $this->assertSame('12', ScaledDecimal::parse('12', 0)->toString());
    }

    public function test_it_adds_and_subtracts_exactly(): void
    {
        $a = ScaledDecimal::parse('3.125', 3);
        $b = ScaledDecimal::parse('82.375', 3);

        $this->assertSame('85.500', $a->add($b)->toString());
        $this->assertSame('-79.250', $a->subtract($b)->toString());
    }

    public function test_it_multiplies_without_losing_intermediate_precision(): void
    {
        $quantity = ScaledDecimal::parse('3.125', 3);
        $price = ScaledDecimal::parse('82.375', 3);

        // Seven decimal places of intermediate precision are preserved.
        $this->assertSame('257.421875', $quantity->multiply($price)->toString());
    }

    public function test_it_rejects_arithmetic_between_different_scales(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ScaledDecimal::parse('1.00', 2)->add(ScaledDecimal::parse('1.000', 3));
    }

    public function test_it_compares_values(): void
    {
        $a = ScaledDecimal::parse('10.00', 2);
        $b = ScaledDecimal::parse('10.01', 2);

        $this->assertTrue($a->isNegative() === false);
        $this->assertTrue($a->compare($b) < 0);
        $this->assertTrue($b->compare($a) > 0);
        $this->assertTrue($a->equals(ScaledDecimal::parse('10.00', 2)));
        $this->assertTrue(ScaledDecimal::of(0, 2)->isZero());
    }

    public function test_it_negates(): void
    {
        $this->assertSame('-3.125', ScaledDecimal::parse('3.125', 3)->negate()->toString());
        $this->assertSame('3.125', ScaledDecimal::parse('-3.125', 3)->negate()->toString());
    }
}
