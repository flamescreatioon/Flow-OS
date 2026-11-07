<?php

namespace App\Application\Purchasing;

use App\Application\Audit\AuditLogger;
use App\Application\Documents\DocumentNumberService;
use App\Application\Inventory\StockMovementService;
use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\Services\DecimalMath;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Domain\Shared\ValueObjects\ScaledDecimal;
use App\Models\Branch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Records goods receipts (ADR 0035).
 *
 * A receipt is finalized on creation: the receipt, its snapshot lines, and
 * one receipt-category movement per line are written in a single transaction.
 * The stock effects go through StockMovementService — this service never
 * writes a stock balance, and the receipt has no update or delete path.
 */
final class GoodsReceiptService
{
    public function record(Organization $organization, array $data, User $actor): GoodsReceipt
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $supplier = null;

            if (! empty($data['supplier_id'])) {
                $supplier = $this->resolveSupplier($organization, $data['supplier_id']);
            }

            $branch = null;

            if (! empty($data['branch_id'])) {
                $branch = $this->resolveBranch($organization, $data['branch_id']);
            }

            $location = $this->resolveLocation($organization, $data['location_id']);

            // Validate and price every line before anything is written, so a
            // bad line costs one query, not a rollback of half a receipt.
            $lines = $this->prepareLines($organization, $data['lines']);

            $total = ScaledDecimal::of(0, UnitPrice::SCALE);

            foreach ($lines as $line) {
                $total = $total->add($line['line_total']);
            }

            $number = app(DocumentNumberService::class)->allocateNext($organization, DocumentNumberService::GOODS_RECEIPT);

            $receipt = GoodsReceipt::create([
                'organization_id' => $organization->id,
                'branch_id' => $branch?->id,
                'supplier_id' => $supplier?->id,
                'location_id' => $location->id,
                'supplier_name' => $supplier?->name,
                'receipt_number' => $number,
                'document_number' => app(DocumentNumberService::class)->render(DocumentNumberService::GOODS_RECEIPT, $number),
                'status' => 'recorded',
                'received_on' => $data['received_on'] ?? now()->format('Y-m-d'),
                'note' => $data['note'] ?? null,
                'total_cost' => $total->value,
            ]);

            foreach ($lines as $line) {
                GoodsReceiptLine::create([
                    'organization_id' => $organization->id,
                    'goods_receipt_id' => $receipt->id,
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'quantity' => $line['quantity']->scaledValue(),
                    'unit_cost' => $line['unit_cost']->scaledValue(),
                    'line_total' => $line['line_total']->value,
                ]);

                // The receipt's stock effect is a movement recorded by
                // Inventory. The movement carries the line's cost and points
                // back at this receipt through its reference.
                app(StockMovementService::class)->record($organization, [
                    'product_id' => $line['product']->id,
                    'location_id' => $location->id,
                    'type' => 'receipt',
                    'quantity' => $line['quantity_string'],
                    'unit_cost' => $line['unit_cost_string'],
                    'reference' => $receipt->id,
                    'note' => $data['note'] ?? null,
                ], $actor);
            }

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'purchasing.goods_receipt_recorded',
                'goods_receipt',
                $receipt->id,
                [
                    'organization_id' => $organization->id,
                    'document_number' => $receipt->document_number,
                    'supplier_id' => $supplier?->id,
                    'location_id' => $location->id,
                    'line_count' => count($lines),
                    'total_cost' => $total->toString(),
                ]
            );

            return $receipt->load(['lines', 'supplier', 'branch', 'location']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $input
     * @return array<int, array{product: Product, quantity: Quantity, unit_cost: UnitPrice, line_total: ScaledDecimal, quantity_string: string, unit_cost_string: string}>
     */
    private function prepareLines(Organization $organization, array $input): array
    {
        $lines = [];

        foreach ($input as $line) {
            $product = $this->resolveProduct($organization, $line['product_id']);
            $unitCost = UnitPrice::parse($line['unit_cost']);

            if ($unitCost->isNegative()) {
                throw new LogicException('The unit cost must not be negative.');
            }

            $quantity = Quantity::parse($line['quantity']);

            if ($quantity->isNegative() || $quantity->isZero()) {
                // StockRules re-states this rule for the movement; failing
                // here keeps the error message about the receipt line.
                throw new LogicException('A receipt line quantity must be a positive magnitude.');
            }

            $lines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => ScaledDecimal::of(
                    DecimalMath::multiplyRoundHalfUp(
                        $quantity->scaledValue(),
                        $unitCost->scaledValue(),
                        Quantity::SCALE,
                        UnitPrice::SCALE,
                        UnitPrice::SCALE
                    ),
                    UnitPrice::SCALE
                ),
                'quantity_string' => $line['quantity'],
                'unit_cost_string' => $line['unit_cost'],
            ];
        }

        return $lines;
    }

    private function resolveSupplier(Organization $organization, string $id): Supplier
    {
        $supplier = Supplier::query()->where('organization_id', $organization->id)->find($id);

        if (! $supplier) {
            throw new LogicException('The supplier does not belong to this organization.');
        }

        if ($supplier->isArchived()) {
            throw new LogicException('An archived supplier cannot receive goods.');
        }

        return $supplier;
    }

    private function resolveBranch(Organization $organization, string $id): Branch
    {
        $branch = Branch::query()->where('organization_id', $organization->id)->find($id);

        if (! $branch) {
            throw new LogicException('The branch does not belong to this organization.');
        }

        return $branch;
    }

    private function resolveLocation(Organization $organization, string $id): InventoryLocation
    {
        $location = InventoryLocation::query()->where('organization_id', $organization->id)->find($id);

        if (! $location) {
            throw new LogicException('The location does not belong to this organization.');
        }

        if ($location->isArchived()) {
            throw new LogicException('An archived location cannot hold stock movements.');
        }

        return $location;
    }

    private function resolveProduct(Organization $organization, string $id): Product
    {
        $product = Product::query()->where('organization_id', $organization->id)->find($id);

        if (! $product) {
            throw new LogicException('The product does not belong to this organization.');
        }

        if (! $product->tracksStock()) {
            throw new LogicException('Only a stocked product can be received.');
        }

        if ($product->isArchived()) {
            throw new LogicException('An archived product cannot be received.');
        }

        return $product;
    }
}
