'use client';

import { useState } from 'react';
import {
  recordStockCount,
  type RecordStockCountInput,
} from '@/features/inventory/service';
import { validateCountedQuantity } from '@/features/inventory/stock-input-rules';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { InventoryLocation, Product, StockMovement } from '@/types';

interface RecordCountFormProps {
  organizationId: string;
  products: Product[];
  locations: InventoryLocation[];
  onRecorded: (movement: StockMovement) => void;
}

export function RecordCountForm({
  organizationId,
  products,
  locations,
  onRecorded,
}: RecordCountFormProps) {
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
    const counted = String(data.get('counted_quantity') ?? '').trim();

    // The precision bound comes from the product's unit of measure; the
    // server re-checks this against the authoritative unit.
    const productId = String(data.get('product_id') ?? '');
    const product = products.find((item) => item.id === productId);
    const unitPrecision = product?.unit_of_measure?.precision ?? 0;

    const countedError = validateCountedQuantity(counted, unitPrecision);
    if (countedError) {
      setErrors({ form: null, fields: { counted_quantity: [countedError] } });
      return;
    }

    const input: RecordStockCountInput = {
      product_id: productId,
      location_id: String(data.get('location_id') ?? ''),
      counted_quantity: counted,
      reference: String(data.get('reference') ?? '').trim() || null,
      note: String(data.get('note') ?? '').trim() || null,
    };

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      const movement = await recordStockCount(organizationId, input);
      onRecorded(movement);
      event.currentTarget.reset();
    } catch (caught) {
      setErrors(toFormErrors(caught));
    } finally {
      setPending(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Record stock count">
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <p className="text-xs text-on-surface-variant">
        State what you counted. The server computes the variance against the
        current balance.
      </p>
      <div className="space-y-1">
        <Label htmlFor="count-product">Product</Label>
        <select
          id="count-product"
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
        <Label htmlFor="count-location">Location</Label>
        <select
          id="count-location"
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
        <Label htmlFor="count-quantity">Counted quantity</Label>
        <Input
          id="count-quantity"
          name="counted_quantity"
          required
          inputMode="decimal"
          placeholder="40"
        />
        {fieldError(errors, 'counted_quantity') && (
          <p className="text-sm text-red-600">
            {fieldError(errors, 'counted_quantity')}
          </p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="count-reference">Reference (optional)</Label>
        <Input id="count-reference" name="reference" maxLength={100} />
      </div>
      <Button type="submit" disabled={pending}>
        {pending ? 'Recording…' : 'Record count'}
      </Button>
    </form>
  );
}
