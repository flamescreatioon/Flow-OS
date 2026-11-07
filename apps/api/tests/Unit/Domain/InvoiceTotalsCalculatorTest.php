<?php

namespace Tests\Unit\Domain;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Invoicing\Services\InvoiceTotalsCalculator;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Enums\TaxMode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvoiceTotalsCalculatorTest extends TestCase
{
    /**
     * @return array<int, array{quantity: Quantity, unit_price: UnitPrice, discount: UnitPrice}>
     */
    private function lines(array $rows): array
    {
        return array_map(fn (array $row) => [
            'quantity' => Quantity::parse($row[0]),
            'unit_price' => UnitPrice::parse($row[1]),
            'discount' => isset($row[2]) ? UnitPrice::parse($row[2]) : UnitPrice::of(0),
        ], $rows);
    }

    public function test_an_exclusive_rate_adds_tax_on_top(): void
    {
        // 2 x 1500.0000 = 3000.0000; tax 7.5% = 225.0000; grand = 3225.0000.
        $result = InvoiceTotalsCalculator::calculate(
            $this->lines([['2.000', '1500.0000']]),
            UnitPrice::parse('7.5'),
            TaxMode::Exclusive
        );

        $this->assertSame('3000.0000', $result['lines'][0]['line_total']->toString());
        $this->assertSame('225.0000', $result['lines'][0]['tax_amount']->toString());
        $this->assertSame('3000.0000', $result['subtotal']->toString());
        $this->assertSame('225.0000', $result['tax_total']->toString());
        $this->assertSame('3225.0000', $result['grand_total']->toString());
    }

    public function test_an_inclusive_rate_extracts_tax_from_the_stated_price(): void
    {
        // 1075.0000 stated with 7.5% inclusive: tax = 1075 x 7.5/107.5 = 75.0000.
        $result = InvoiceTotalsCalculator::calculate(
            $this->lines([['1.000', '1075.0000']]),
            UnitPrice::parse('7.5'),
            TaxMode::Inclusive
        );

        $this->assertSame('1075.0000', $result['lines'][0]['line_total']->toString());
        $this->assertSame('75.0000', $result['lines'][0]['tax_amount']->toString());
        // The net subtotal sits under the stated price.
        $this->assertSame('1000.0000', $result['subtotal']->toString());
        $this->assertSame('75.0000', $result['tax_total']->toString());
        $this->assertSame('1075.0000', $result['grand_total']->toString());
    }

    public function test_line_totals_round_half_up_before_tax(): void
    {
        // 3.333 x 1500.25 = 5000.33325 -> 5000.3333 at scale 4; tax 10% of
        // the rounded line = 500.03333 -> 500.0333 half-up.
        $result = InvoiceTotalsCalculator::calculate(
            $this->lines([['3.333', '1500.2500']]),
            UnitPrice::parse('10'),
            TaxMode::Exclusive
        );

        $this->assertSame('5000.3333', $result['lines'][0]['line_total']->toString());
        $this->assertSame('500.0333', $result['lines'][0]['tax_amount']->toString());
    }

    public function test_a_discount_reduces_the_taxable_amount(): void
    {
        $result = InvoiceTotalsCalculator::calculate(
            $this->lines([['1.000', '2000.0000', '500.0000']]),
            UnitPrice::parse('7.5'),
            TaxMode::Exclusive
        );

        $this->assertSame('1500.0000', $result['lines'][0]['line_total']->toString());
        $this->assertSame('112.5000', $result['lines'][0]['tax_amount']->toString());
        $this->assertSame('500.0000', $result['discount_total']->toString());
        $this->assertSame('1612.5000', $result['grand_total']->toString());
    }

    public function test_a_discount_must_not_exceed_the_line_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InvoiceTotalsCalculator::calculate(
            $this->lines([['1.000', '500.0000', '600.0000']]),
            null,
            TaxMode::Exclusive
        );
    }

    public function test_a_null_rate_means_no_tax(): void
    {
        $result = InvoiceTotalsCalculator::calculate(
            $this->lines([['2.000', '1000.0000']]),
            null,
            TaxMode::Exclusive
        );

        $this->assertSame('0.0000', $result['tax_total']->toString());
        $this->assertSame('2000.0000', $result['grand_total']->toString());
    }

    public function test_multiple_lines_sum_at_the_document_level(): void
    {
        $result = InvoiceTotalsCalculator::calculate(
            $this->lines([
                ['1.000', '1000.0000'],
                ['2.500', '400.0000', '100.0000'],
            ]),
            UnitPrice::parse('7.5'),
            TaxMode::Exclusive
        );

        $this->assertSame('900.0000', $result['lines'][1]['line_total']->toString());
        $this->assertSame('100.0000', $result['discount_total']->toString());
        $this->assertSame('142.5000', $result['tax_total']->toString());
        $this->assertSame('2042.5000', $result['grand_total']->toString());
    }

    public function test_a_zero_tax_rate_yields_zero_tax(): void
    {
        $result = InvoiceTotalsCalculator::calculate(
            $this->lines([['1.000', '1000.0000']]),
            UnitPrice::of(0),
            TaxMode::Exclusive
        );

        $this->assertSame('0.0000', $result['lines'][0]['tax_amount']->toString());
        $this->assertSame('1000.0000', $result['grand_total']->toString());
    }

    public function test_a_negative_rate_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InvoiceTotalsCalculator::calculate(
            $this->lines([['1.000', '1000.0000']]),
            UnitPrice::parse('-1'),
            TaxMode::Exclusive
        );
    }

    public function test_scaled_totals_match_the_string_repositories(): void
    {
        // The scaled integers stored on the invoice and the decimal strings
        // rendered on the wire describe the same amounts.
        $result = InvoiceTotalsCalculator::calculate(
            $this->lines([['12.500', '2500.5000']]),
            UnitPrice::parse('7.5'),
            TaxMode::Exclusive
        );

        $this->assertSame(312562500, $result['lines'][0]['line_total']->value);
        $this->assertSame(23442188, $result['lines'][0]['tax_amount']->value);
        $this->assertSame('2344.2188', $result['lines'][0]['tax_amount']->toString());
    }
}
