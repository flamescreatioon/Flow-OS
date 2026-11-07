'use client';

import { useState } from 'react';
import {
  recordGoodsReceipt,
  type RecordGoodsReceiptInput,
  type RecordGoodsReceiptLineInput,
} from '@/features/purchasing/service';
import { validateMovementQuantity } from '@/features/inventory/stock-input-rules';
import { isCanonicalDecimal } from '@/utils/decimal';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { GoodsReceipt, InventoryLocation, Product, Supplier } from '@/types';

interface ReceiptLineDraft {
  key: number;
  product_id: string;
  quantity: string;
  unit_cost: string;
}

let lineKey = 0;

function newLine(): ReceiptLineDraft {
  lineKey += 1;
  return { key: lineKey, product_id: '', quantity: '', unit_cost: '' };
}

interface RecordReceiptFormProps {
  organizationId: string;
  products: Product[];
  locations: InventoryLocation[];
  suppliers: Supplier[];
  onRecorded: (receipt: GoodsReceipt) => void;
}

/**
 * A goods receipt is finalized on creation, so the client only states lines;
 * the server assigns the document number and computes every total.
 */
export function RecordReceiptForm({
  organizationId,
  products,
  locations,
  suppliers,
  onRecorded,
}: RecordReceiptFormProps) {
  const [pending, setPending] = useState(false);
  const [lines, setLines] = useState<ReceiptLineDraft[]>(() => [newLine()]);
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

  function updateLine(key: number, patch: Partial<ReceiptLineDraft>): void {
    setLines((current) => current.map((line) => (line.key === key ? { ...line, ...patch } : line)));
  }

  function validateLines(): Record<string, string[]> | null {
    const fields: Record<string, string[]> = {};
    lines.forEach((line, index) => {
      const label = `Line ${index + 1}`;
      if (!line.product_id) {
        fields[`line_${line.key}_product`] = [`${label}: choose a product.`];
      }
      const quantityError = validateMovementQuantity('receipt', line.quantity);
      if (quantityError) {
        fields[`line_${line.key}_quantity`] = [`${label}: ${quantityError}`];
      }
      if (!isCanonicalDecimal(line.unit_cost, 4)) {
        fields[`line_${line.key}_unit_cost`] = [
          `${label}: enter a unit cost with at most 4 decimal places.`,
        ];
      } else if (line.unit_cost.startsWith('-')) {
        fields[`line_${line.key}_unit_cost`] = [`${label}: the unit cost must not be negative.`];
      }
    });
    return Object.keys(fields).length > 0 ? fields : null;
  }

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();

    const lineErrors = validateLines();
    if (lineErrors) {
      setErrors({ form: null, fields: lineErrors });
      return;
    }

    const data = new FormData(event.currentTarget);
    const supplierId = String(data.get('supplier_id') ?? '');
    const receivedOn = String(data.get('received_on') ?? '').trim();

    const payloadLines: RecordGoodsReceiptLineInput[] = lines.map((line) => ({
      product_id: line.product_id,
      quantity: line.quantity.trim(),
      unit_cost: line.unit_cost.trim(),
    }));

    const input: RecordGoodsReceiptInput = {
      supplier_id: supplierId || null,
      location_id: String(data.get('location_id') ?? ''),
      received_on: receivedOn || undefined,
      note: String(data.get('note') ?? '').trim() || null,
      lines: payloadLines,
    };

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      const receipt = await recordGoodsReceipt(organizationId, input);
      setLines([newLine()]);
      onRecorded(receipt);
      event.currentTarget.reset();
    } catch (caught) {
      setErrors(toFormErrors(caught));
    } finally {
      setPending(false);
    }
  }

  const selectClassName =
    'h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface';

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Record goods receipt">
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="receipt-supplier">Supplier (optional)</Label>
        <select id="receipt-supplier" name="supplier_id" className={selectClassName} defaultValue="">
          <option value="">No supplier (one-off purchase)</option>
          {suppliers.map((supplier) => (
            <option key={supplier.id} value={supplier.id}>
              {supplier.name}
            </option>
          ))}
        </select>
      </div>
      <div className="space-y-1">
        <Label htmlFor="receipt-location">Location</Label>
        <select id="receipt-location" name="location_id" required className={selectClassName}>
          {locations.map((location) => (
            <option key={location.id} value={location.id}>
              {location.name} ({location.code})
            </option>
          ))}
        </select>
      </div>
      <div className="space-y-1">
        <Label htmlFor="receipt-received-on">Received on (optional)</Label>
        <Input id="receipt-received-on" name="received_on" type="date" />
        <p className="text-xs text-on-surface-variant">Defaults to today.</p>
      </div>
      <fieldset className="space-y-3 rounded-xl border border-gray-200 p-3" aria-label="Receipt lines">
        <legend className="px-1 text-sm font-medium text-on-surface">Lines</legend>
        {lines.map((line, index) => (
          <div key={line.key} className="space-y-2 rounded-lg bg-secondary-container/40 p-3">
            <p className="text-xs font-medium text-on-surface-variant">Line {index + 1}</p>
            <div className="space-y-1">
              <Label htmlFor={`line-${line.key}-product`}>{`Product (line ${index + 1})`}</Label>
              <select
                id={`line-${line.key}-product`}
                required
                className={selectClassName}
                value={line.product_id}
                onChange={(event) => updateLine(line.key, { product_id: event.target.value })}
              >
                <option value="">Choose a product</option>
                {products.map((product) => (
                  <option key={product.id} value={product.id}>
                    {product.name}
                    {product.sku ? ` (${product.sku})` : ''}
                  </option>
                ))}
              </select>
              {fieldError(errors, `line_${line.key}_product`) && (
                <p className="text-sm text-red-600">
                  {fieldError(errors, `line_${line.key}_product`)}
                </p>
              )}
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1">
                <Label htmlFor={`line-${line.key}-quantity`}>{`Quantity (line ${index + 1})`}</Label>
                <Input
                  id={`line-${line.key}-quantity`}
                  required
                  inputMode="decimal"
                  placeholder="12.5"
                  value={line.quantity}
                  onChange={(event) => updateLine(line.key, { quantity: event.target.value })}
                />
                {fieldError(errors, `line_${line.key}_quantity`) && (
                  <p className="text-sm text-red-600">
                    {fieldError(errors, `line_${line.key}_quantity`)}
                  </p>
                )}
              </div>
              <div className="space-y-1">
                <Label htmlFor={`line-${line.key}-unit-cost`}>{`Unit cost (line ${index + 1})`}</Label>
                <Input
                  id={`line-${line.key}-unit-cost`}
                  required
                  inputMode="decimal"
                  placeholder="1500.0000"
                  value={line.unit_cost}
                  onChange={(event) => updateLine(line.key, { unit_cost: event.target.value })}
                />
                {fieldError(errors, `line_${line.key}_unit_cost`) && (
                  <p className="text-sm text-red-600">
                    {fieldError(errors, `line_${line.key}_unit_cost`)}
                  </p>
                )}
              </div>
            </div>
            {lines.length > 1 && (
              <Button
                type="button"
                variant="ghost"
                onClick={() => setLines((current) => current.filter((row) => row.key !== line.key))}
                disabled={pending}
              >
                Remove line
              </Button>
            )}
          </div>
        ))}
        <Button
          type="button"
          variant="secondary"
          onClick={() => setLines((current) => [...current, newLine()])}
          disabled={pending}
        >
          Add line
        </Button>
      </fieldset>
      <div className="space-y-1">
        <Label htmlFor="receipt-note">Note (optional)</Label>
        <Input id="receipt-note" name="note" maxLength={500} />
      </div>
      <Button type="submit" disabled={pending}>
        {pending ? 'Recording…' : 'Record receipt'}
      </Button>
    </form>
  );
}
