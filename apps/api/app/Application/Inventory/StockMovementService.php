<?php

namespace App\Application\Inventory;

use App\Application\Audit\AuditLogger;
use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Inventory\Services\StockRules;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Enums\StockMovementType;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Records stock movements and maintains the derived stock balance.
 *
 * The movement records are the authority. The balance is a read model that is
 * updated in the same transaction as the movement, and there is no path in
 * this service that writes a balance without a movement.
 */
final class StockMovementService
{
    public function record(Organization $organization, array $data, User $actor): StockMovement
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $type = StockMovementType::from($data['type']);

            if ($type->isTransfer()) {
                throw new LogicException('A transfer is recorded through the transfer operation.');
            }

            if ($type === StockMovementType::Count) {
                throw new LogicException('A count is recorded through the stock count operation.');
            }

            $product = $this->resolveProduct($organization, $data['product_id']);
            $location = $this->resolveLocation($organization, $data['location_id']);

            $quantity = StockRules::resolveQuantity($type, Quantity::parse($data['quantity']));

            $corrects = null;

            if (! empty($data['corrects_movement_id'])) {
                $corrects = $this->resolveOriginal($organization, $data['corrects_movement_id'], $product, $location);
                StockRules::assertCorrectionOpposes($corrects->quantity(), $quantity);
            }

            $movement = $this->createMovement($organization, $product, $location, $type, $quantity, $data, $corrects);

            $this->applyToBalance($movement);

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'stock.movement_recorded',
                'stock_movement',
                $movement->id,
                [
                    'organization_id' => $organization->id,
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'type' => $type->value,
                    'quantity' => $quantity->toString(),
                ]
            );

            return $movement;
        });
    }

    /**
     * Record a count. The server computes the variance against the current
     * balance, so a client never states a canonical quantity.
     */
    public function countStock(Organization $organization, array $data, User $actor): StockMovement
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $product = $this->resolveProduct($organization, $data['product_id']);
            $location = $this->resolveLocation($organization, $data['location_id']);

            $unit = $product->unitOfMeasure;
            $counted = Quantity::parseWithinPrecision($data['counted_quantity'], $unit?->precision ?? 0);

            $current = $this->currentQuantity($product->id, $location->id);
            $variance = StockRules::countVariance($counted, $current);

            $movement = $this->createMovement(
                $organization,
                $product,
                $location,
                StockMovementType::Count,
                $variance,
                $data,
                null
            );

            $this->applyToBalance($movement);

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'stock.counted',
                'stock_movement',
                $movement->id,
                [
                    'organization_id' => $organization->id,
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'counted_quantity' => $counted->toString(),
                    'variance' => $variance->toString(),
                ]
            );

            return $movement;
        });
    }

    /**
     * Move stock between two locations. The two halves are created together
     * and reference each other.
     *
     * @return array{0: StockMovement, 1: StockMovement}
     */
    public function transfer(Organization $organization, array $data, User $actor): array
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $product = $this->resolveProduct($organization, $data['product_id']);
            $from = $this->resolveLocation($organization, $data['from_location_id']);
            $to = $this->resolveLocation($organization, $data['to_location_id']);

            if ($from->id === $to->id) {
                throw new LogicException('A transfer needs two different locations.');
            }

            $quantity = StockRules::resolveQuantity(StockMovementType::TransferOut, Quantity::parse($data['quantity']));

            $outbound = $this->createMovement(
                $organization,
                $product,
                $from,
                StockMovementType::TransferOut,
                $quantity,
                $data,
                null
            );

            $inbound = $this->createMovement(
                $organization,
                $product,
                $to,
                StockMovementType::TransferIn,
                $quantity->negate(),
                $data,
                null
            );

            $outbound->forceFill(['transfer_movement_id' => $inbound->id])->save();
            $inbound->forceFill(['transfer_movement_id' => $outbound->id])->save();

            $this->applyToBalance($outbound);
            $this->applyToBalance($inbound);

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'stock.transferred',
                'stock_movement',
                $outbound->id,
                [
                    'organization_id' => $organization->id,
                    'product_id' => $product->id,
                    'from_location_id' => $from->id,
                    'to_location_id' => $to->id,
                    'quantity' => $quantity->toString(),
                ]
            );

            return [$outbound->fresh(), $inbound->fresh()];
        });
    }

    /**
     * The balance for a product at a location, or null when nothing has been
     * recorded there.
     */
    public function balance(Organization $organization, string $productId, string $locationId): ?StockBalance
    {
        return StockBalance::query()
            ->where('organization_id', $organization->id)
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();
    }

    private function createMovement(
        Organization $organization,
        Product $product,
        InventoryLocation $location,
        StockMovementType $type,
        Quantity $quantity,
        array $data,
        ?StockMovement $corrects
    ): StockMovement {
        $movement = new StockMovement([
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'corrects_movement_id' => $corrects?->id,
            'type' => $type,
            'quantity' => $quantity->scaledValue(),
            'unit_cost' => isset($data['unit_cost']) ? UnitPrice::parse($data['unit_cost'])->scaledValue() : null,
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        $movement->save();

        return $movement;
    }

    private function applyToBalance(StockMovement $movement): void
    {
        $balance = StockBalance::query()
            ->where('product_id', $movement->product_id)
            ->where('location_id', $movement->location_id)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            $balance = new StockBalance([
                'organization_id' => $movement->organization_id,
                'product_id' => $movement->product_id,
                'location_id' => $movement->location_id,
                'quantity' => 0,
            ]);
        }

        $balance->quantity = $balance->quantity + $movement->quantity;
        $balance->save();
    }

    private function currentQuantity(string $productId, string $locationId): Quantity
    {
        $balance = StockBalance::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        return $balance ? Quantity::of($balance->quantity) : Quantity::of(0);
    }

    private function resolveProduct(Organization $organization, string $id): Product
    {
        $product = Product::query()->where('organization_id', $organization->id)->find($id);

        if (! $product) {
            throw new LogicException('The product does not belong to this organization.');
        }

        if (! $product->tracksStock()) {
            throw new LogicException('Only a stocked product can have stock movements.');
        }

        if ($product->isArchived()) {
            throw new LogicException('An archived product cannot have stock movements.');
        }

        return $product;
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

    private function resolveOriginal(
        Organization $organization,
        string $id,
        Product $product,
        InventoryLocation $location
    ): StockMovement {
        $original = StockMovement::query()->where('organization_id', $organization->id)->find($id);

        if (! $original) {
            throw new LogicException('The corrected movement does not belong to this organization.');
        }

        if ($original->product_id !== $product->id || $original->location_id !== $location->id) {
            throw new LogicException('A correction must correct a movement of the same product and location.');
        }

        if ($original->correctedBy()->exists()) {
            throw new LogicException('The movement has already been corrected.');
        }

        return $original;
    }
}
