<?php

namespace App\Domain\Invoicing\Services;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\Services\DecimalMath;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Domain\Shared\ValueObjects\ScaledDecimal;
use App\Enums\TaxMode;
use InvalidArgumentException;

/**
 * The declared, exact invoice calculation (ADR 0036).
 *
 * Canonical order per line: quantity x price -> gross -> discount -> tax ->
 * line total, all in scaled integers with half-up rounding applied once at
 * the currency scale. Document totals are sums of the rounded line values,
 * so no second rounding can drift between lines and document.
 *
 * Exclusive mode adds the tax on top of the line totals. Inclusive mode
 * treats the stated price as containing the tax and extracts it.
 */
final class InvoiceTotalsCalculator
{
    private const RATE_DENOMINATOR = 10 ** 6;

    /**
     * @param  array<int, array{quantity: Quantity, unit_price: UnitPrice, discount: UnitPrice}>  $lines
     * @param  UnitPrice|null  $ratePercent  The tax rate as a percentage at the unit price scale, or null when tax is disabled.
     * @return array{
     *     lines: array<int, array{line_total: ScaledDecimal, tax_amount: ScaledDecimal}>,
     *     subtotal: ScaledDecimal,
     *     discount_total: ScaledDecimal,
     *     tax_total: ScaledDecimal,
     *     grand_total: ScaledDecimal
     * }
     */
    public static function calculate(array $lines, ?UnitPrice $ratePercent, TaxMode $mode): array
    {
        if ($ratePercent !== null && $ratePercent->isNegative()) {
            throw new InvalidArgumentException('The tax rate must not be negative.');
        }

        $scale = UnitPrice::SCALE;

        $computedLines = [];
        $subtotal = ScaledDecimal::of(0, $scale);
        $discountTotal = ScaledDecimal::of(0, $scale);
        $taxTotal = ScaledDecimal::of(0, $scale);
        $grandTotal = ScaledDecimal::of(0, $scale);

        foreach ($lines as $line) {
            $discount = ScaledDecimal::of($line['discount']->scaledValue(), $scale);

            $gross = ScaledDecimal::of(
                DecimalMath::multiplyRoundHalfUp(
                    $line['quantity']->scaledValue(),
                    $line['unit_price']->scaledValue(),
                    Quantity::SCALE,
                    UnitPrice::SCALE,
                    $scale
                ),
                $scale
            );

            $lineTotal = $gross->subtract($discount);

            if ($lineTotal->isNegative()) {
                throw new InvalidArgumentException('The discount must not exceed the line amount.');
            }

            $taxAmount = self::taxFor($lineTotal, $ratePercent, $mode);

            $computedLines[] = [
                'line_total' => $lineTotal,
                'tax_amount' => $taxAmount,
            ];

            $discountTotal = $discountTotal->add($discount);
            $taxTotal = $taxTotal->add($taxAmount);

            if ($mode === TaxMode::Inclusive) {
                // The stated price contains the tax: the customer owes the
                // line total, and the tax comes out of it.
                $grandTotal = $grandTotal->add($lineTotal);
            } else {
                $subtotal = $subtotal->add($lineTotal);
                $grandTotal = $grandTotal->add($lineTotal)->add($taxAmount);
            }
        }

        if ($mode === TaxMode::Inclusive) {
            $subtotal = $grandTotal->subtract($taxTotal);
        }

        return [
            'lines' => $computedLines,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ];
    }

    private static function taxFor(ScaledDecimal $lineTotal, ?UnitPrice $ratePercent, TaxMode $mode): ScaledDecimal
    {
        if ($ratePercent === null) {
            return ScaledDecimal::of(0, UnitPrice::SCALE);
        }

        if ($mode === TaxMode::Inclusive) {
            // tax = amount x R / (100 + R), computed on the scaled integers:
            // (A x R) / (10^6 + R).
            $denominator = self::RATE_DENOMINATOR + $ratePercent->scaledValue();
        } else {
            // tax = amount x R / 100, computed on the scaled integers:
            // (A x R) / 10^6.
            $denominator = self::RATE_DENOMINATOR;
        }

        $tax = DecimalMath::divideRoundHalfUp(
            DecimalMath::multiplyExact($lineTotal->value, $ratePercent->scaledValue()),
            $denominator
        );

        return ScaledDecimal::of($tax, UnitPrice::SCALE);
    }
}
