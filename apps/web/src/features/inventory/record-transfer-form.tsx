'use client';

import { useState } from 'react';
import {
  recordStockTransfer,
  type RecordStockTransferInput,
} from '@/features/inventory/service';
import { validateTransferQuantity } from '@/features/inventory/stock-input-rules';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { InventoryLocation, Product, StockTransfer } from '@/types';

interface RecordTransferFormProps {
  organizationId: string;
  products: Product[];
  locations: InventoryLocation[];
  onRecorded: (transfer: StockTransfer) => void;
}

export function RecordTransferForm({
  organizationId,
  products,
  locations,
  onRecorded,
}: RecordTransferFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  if (products.length === 0 || locations.length < 2) {
    return (
      <p className="text-sm text-on-surface-variant">
        A transfer needs one stocked product and two locations.
      </p>
    );
  }

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const quantity = String(data.get('quantity') ?? '').trim();
    const fromLocationId = String(data.get('from_location_id') ?? '');
    const toLocationId = String(data.get('to_location_id') ?? '');

    const quantityError = validateTransferQuantity(quantity);
    if (quantityError) {
      setErrors({ form: null, fields: { quantity: [quantityError] } });
      return;
    }

    if (fromLocationId && fromLocationId === toLocationId) {
      setErrors({ form: 'A transfer needs two different locations.', fields: {} });
      return;
    }

    const input: RecordStockTransferInput = {
      product_id: String(data.get('product_id') ?? ''),
      from_location_id: fromLocationId,
      to_location_id: toLocationId,
      quantity,
      reference: String(data.get('reference') ?? '').trim() || null,
      note: String(data.get('note') ?? '').trim() || null,
    };

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      const transfer = await recordStockTransfer(organizationId, input);
      onRecorded(transfer);
      event.currentTarget.reset();
    } catch (caught) {
      setErrors(toFormErrors(caught));
    } finally {
      setPending(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Record stock transfer">
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="transfer-product">Product</Label>
        <select
          id="transfer-product"
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
        <Label htmlFor="transfer-from">From location</Label>
        <select
          id="transfer-from"
          name="from_location_id"
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
        <Label htmlFor="transfer-to">To location</Label>
        <select
          id="transfer-to"
          name="to_location_id"
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
        <Label htmlFor="transfer-quantity">Quantity</Label>
        <Input
          id="transfer-quantity"
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
        <Label htmlFor="transfer-reference">Reference (optional)</Label>
        <Input id="transfer-reference" name="reference" maxLength={100} />
      </div>
      <Button type="submit" disabled={pending}>
        {pending ? 'Transferring…' : 'Record transfer'}
      </Button>
    </form>
  );
}
