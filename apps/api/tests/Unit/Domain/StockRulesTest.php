<?php

namespace Tests\Unit\Domain;

use App\Domain\Inventory\Services\StockRules;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Enums\StockMovementType;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class StockRulesTest extends TestCase
{
    public function test_a_receipt_increases_stock(): void
    {
        $resolved = StockRules::resolveQuantity(StockMovementType::Receipt, Quantity::parse('5.000'));

        $this->assertSame('5.000', $resolved->toString());
    }

    public function test_a_sale_decreases_stock(): void
    {
        $resolved = StockRules::resolveQuantity(StockMovementType::Sale, Quantity::parse('5.000'));

        $this->assertSame('-5.000', $resolved->toString());
    }

    public function test_a_transfer_out_decreases_and_a_transfer_in_increases(): void
    {
        $this->assertSame(
            '-2.500',
            StockRules::resolveQuantity(StockMovementType::TransferOut, Quantity::parse('2.500'))->toString()
        );

        $this->assertSame(
            '2.500',
            StockRules::resolveQuantity(StockMovementType::TransferIn, Quantity::parse('2.500'))->toString()
        );
    }

    public function test_a_movement_with_a_fixed_direction_rejects_a_signed_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StockRules::resolveQuantity(StockMovementType::Receipt, Quantity::parse('-5.000'));
    }

    public function test_a_movement_with_a_fixed_direction_rejects_a_zero_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StockRules::resolveQuantity(StockMovementType::Receipt, Quantity::parse('0.000'));
    }

    public function test_an_adjustment_keeps_the_signed_quantity(): void
    {
        $resolved = StockRules::resolveQuantity(StockMovementType::Adjustment, Quantity::parse('-1.250'));

        $this->assertSame('-1.250', $resolved->toString());
    }

    public function test_an_adjustment_rejects_a_zero_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StockRules::resolveQuantity(StockMovementType::Adjustment, Quantity::parse('0.000'));
    }

    public function test_a_count_variance_is_the_difference_from_the_current_balance(): void
    {
        $variance = StockRules::countVariance(Quantity::parse('10.000'), Quantity::parse('7.500'));

        $this->assertSame('2.500', $variance->toString());
    }

    public function test_a_count_variance_may_be_negative(): void
    {
        $variance = StockRules::countVariance(Quantity::parse('6.000'), Quantity::parse('7.500'));

        $this->assertSame('-1.500', $variance->toString());
    }

    public function test_a_count_variance_rejects_a_negative_counted_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StockRules::countVariance(Quantity::parse('-1.000'), Quantity::parse('7.500'));
    }

    public function test_a_correction_must_move_stock_in_the_opposite_direction(): void
    {
        StockRules::assertCorrectionOpposes(Quantity::parse('5.000'), Quantity::parse('-5.000'));
        StockRules::assertCorrectionOpposes(Quantity::parse('-5.000'), Quantity::parse('2.000'));

        $this->expectException(LogicException::class);

        StockRules::assertCorrectionOpposes(Quantity::parse('5.000'), Quantity::parse('2.000'));
    }

    public function test_a_correction_must_not_be_zero(): void
    {
        $this->expectException(LogicException::class);

        StockRules::assertCorrectionOpposes(Quantity::parse('5.000'), Quantity::parse('0.000'));
    }
}
