'use client';

import { useState } from 'react';
import {
  recordStockMovement,
  type RecordStockMovementInput,
} from '@/features/inventory/service';
import { validateMovementQuantity } from '@/features/inventory/stock-input-rules';
import { isCanonicalDecimal } from '@/utils/decimal';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { InventoryLocation, Product, StockMovement, StockMovementType } from '@/types';

const RECORDABLE_TYPES: Array<{ value: StockMovementType; label: string; hint: string }> = [
  { value: 'receipt', label: 'Receipt', hint: 'Stock received into the location.' },
  { value: 'sale', label: 'Sale', hint: 'Stock sold out of the location.' },
  { value: 'return', label: 'Return', hint: 'Stock returned into the location.' },
  { value: 'opening', label: 'Opening', hint: 'Initial stock when tracking starts.' },
  {
    value: 'adjustment',
    label: 'Adjustment',
    hint: 'A signed correction: positive adds, negative removes.',
  },
];

interface RecordMovementFormProps {
  organizationId: string;
  products: Product[];
  locations: InventoryLocation[];
  onRecorded: (movement: StockMovement) => void;
}

export function RecordMovementForm({
  organizationId,
  products,
  locations,
  onRecorded,
}: RecordMovementFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  if (products.length === 0 || locations.length === 0) {
    return (
      <p className="text-sm text-on-surface-variant">
        Record at least one stocked product and one location first.
      </p>
    );
  }

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const type = String(data.get('type') ?? '') as StockMovementType;
    const quantity = String(data.get('quantity') ?? '').trim();
    const unitCost = String(data.get('unit_cost') ?? '').trim();
    const correctsId = String(data.get('corrects_movement_id') ?? '').trim();

    const quantityError = validateMovementQuantity(type, quantity);
    if (quantityError) {
      setErrors({ form: null, fields: { quantity: [quantityError] } });
      return;
    }

    if (unitCost && !isCanonicalDecimal(unitCost, 4)) {
      setErrors({
        form: null,
        fields: { unit_cost: ['Enter a unit cost with at most 4 decimal places.'] },
      });
      return;
    }

    const input: RecordStockMovementInput = {
      product_id: String(data.get('product_id') ?? ''),
      location_id: String(data.get('location_id') ?? ''),
      type,
      quantity,
      unit_cost: unitCost || null,
      reference: String(data.get('reference') ?? '').trim() || null,
      note: String(data.get('note') ?? '').trim() || null,
      corrects_movement_id: correctsId || null,
    };

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      const movement = await recordStockMovement(organizationId, input);
      onRecorded(movement);
      event.currentTarget.reset();
    } catch (caught) {
      setErrors(toFormErrors(caught));
    } finally {
      setPending(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Record stock movement">
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="movement-type">Type</Label>
        <select
          id="movement-type"
          name="type"
          required
          defaultValue="receipt"
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
        >
          {RECORDABLE_TYPES.map((type) => (
            <option key={type.value} value={type.value}>
              {type.label}
            </option>
          ))}
        </select>
        <p className="text-xs text-on-surface-variant">
          For receipts and sales state the amount; the server applies the direction.
        </p>
      </div>
      <div className="space-y-1">
        <Label htmlFor="movement-product">Product</Label>
        <select
          id="movement-product"
          name="product_id"
          required
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
        >
          {products.map((product) => (
            <option key={product.id} value={product.id}>
              {product.name}
              {product.sku ? ` (${product.sku})` : ''}
            </option>
          ))}
        </select>
      </div>
      <div className="space-y-1">
        <Label htmlFor="movement-location">Location</Label>
        <select
          id="movement-location"
          name="location_id"
          required
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
        >
          {locations.map((location) => (
            <option key={location.id} value={location.id}>
              {location.name} ({location.code})
            </option>
          ))}
        </select>
      </div>
      <div className="space-y-1">
        <Label htmlFor="movement-quantity">Quantity</Label>
        <Input
          id="movement-quantity"
          name="quantity"
          required
          inputMode="decimal"
          placeholder="12.5"
        />
        {fieldError(errors, 'quantity') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'quantity')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="movement-unit-cost">Unit cost (optional)</Label>
        <Input
          id="movement-unit-cost"
          name="unit_cost"
          inputMode="decimal"
          placeholder="1500.0000"
        />
        {fieldError(errors, 'unit_cost') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'unit_cost')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="movement-reference">Reference (optional)</Label>
        <Input id="movement-reference" name="reference" maxLength={100} />
      </div>
      <div className="space-y-1">
        <Label htmlFor="movement-corrects">Corrects movement ID (optional)</Label>
        <Input id="movement-corrects" name="corrects_movement_id" placeholder="UUID" />
        <p className="text-xs text-on-surface-variant">
          Repairs an earlier movement; the correction must move stock the opposite way.
        </p>
        {fieldError(errors, 'corrects_movement_id') && (
          <p className="text-sm text-red-600">
            {fieldError(errors, 'corrects_movement_id')}
          </p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="movement-note">Note (optional)</Label>
        <Input id="movement-note" name="note" maxLength={500} />
      </div>
      <Button type="submit" disabled={pending}>
        {pending ? 'Recording…' : 'Record movement'}
      </Button>
    </form>
  );
}
