'use client';

import { useState } from 'react';
import {
  createInvoice,
  type CreateInvoiceInput,
  type InvoiceLineInput,
} from '@/features/invoicing/service';
import { isCanonicalDecimal } from '@/utils/decimal';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Customer, Invoice, Product } from '@/types';

interface InvoiceLineDraft {
  key: number;
  product_id: string;
  description: string;
  quantity: string;
  unit_price: string;
  discount: string;
}

let lineKey = 0;

function newLine(): InvoiceLineDraft {
  lineKey += 1;
  return { key: lineKey, product_id: '', description: '', quantity: '', unit_price: '', discount: '' };
}

interface InvoiceFormProps {
  organizationId: string;
  products: Product[];
  customers: Customer[];
  onCreated: (invoice: Invoice) => void;
}

/**
 * A draft invoice states lines only; the server computes every total and
 * assigns the document number at issue (ADR 0036).
 */
export function InvoiceForm({ organizationId, products, customers, onCreated }: InvoiceFormProps) {
  const [pending, setPending] = useState(false);
  const [lines, setLines] = useState<InvoiceLineDraft[]>(() => [newLine()]);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  function updateLine(key: number, patch: Partial<InvoiceLineDraft>): void {
    setLines((current) => current.map((line) => (line.key === key ? { ...line, ...patch } : line)));
  }

  function validateLines(): Record<string, string[]> | null {
    const fields: Record<string, string[]> = {};
    lines.forEach((line, index) => {
      const label = `Line ${index + 1}`;
      if (!line.description.trim()) {
        fields[`line_${line.key}_description`] = [`${label}: a description is required.`];
      }
      if (!isCanonicalDecimal(line.quantity, 3) || line.quantity.startsWith('-') || !/[1-9]/.test(line.quantity.replace('.', ''))) {
        fields[`line_${line.key}_quantity`] = [
          `${label}: enter a positive quantity with at most 3 decimal places.`,
        ];
      }
      if (!isCanonicalDecimal(line.unit_price, 4) || line.unit_price.startsWith('-')) {
        fields[`line_${line.key}_unit_price`] = [
          `${label}: enter a unit price with at most 4 decimal places.`,
        ];
      }
      if (line.discount && !isCanonicalDecimal(line.discount, 4)) {
        fields[`line_${line.key}_discount`] = [
          `${label}: enter a discount with at most 4 decimal places.`,
        ];
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
    const customerId = String(data.get('customer_id') ?? '');
    const dueOn = String(data.get('due_on') ?? '').trim();

    const payloadLines: InvoiceLineInput[] = lines.map((line) => ({
      product_id: line.product_id || null,
      description: line.description.trim(),
      quantity: line.quantity.trim(),
      unit_price: line.unit_price.trim(),
      discount: line.discount.trim() || null,
    }));

    const input: CreateInvoiceInput = {
      customer_id: customerId || null,
      due_on: dueOn || undefined,
      note: String(data.get('note') ?? '').trim() || null,
      lines: payloadLines,
    };

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      const invoice = await createInvoice(organizationId, input);
      setLines([newLine()]);
      onCreated(invoice);
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
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Create invoice">
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="invoice-customer">Customer (optional)</Label>
        <select id="invoice-customer" name="customer_id" className={selectClassName} defaultValue="">
          <option value="">No customer (walk-in)</option>
          {customers.map((customer) => (
            <option key={customer.id} value={customer.id}>
              {customer.name}
            </option>
          ))}
        </select>
      </div>
      <div className="space-y-1">
        <Label htmlFor="invoice-due-on">Due on (optional)</Label>
        <Input id="invoice-due-on" name="due_on" type="date" />
      </div>
      <fieldset className="space-y-3 rounded-xl border border-gray-200 p-3" aria-label="Invoice lines">
        <legend className="px-1 text-sm font-medium text-on-surface">Lines</legend>
        {lines.map((line, index) => (
          <div key={line.key} className="space-y-2 rounded-lg bg-secondary-container/40 p-3">
            <p className="text-xs font-medium text-on-surface-variant">Line {index + 1}</p>
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1">
                <Label htmlFor={`line-${line.key}-product`}>Product (optional)</Label>
                <select
                  id={`line-${line.key}-product`}
                  className={selectClassName}
                  value={line.product_id}
                  onChange={(event) => updateLine(line.key, { product_id: event.target.value })}
                >
                  <option value="">Free-text line</option>
                  {products.map((product) => (
                    <option key={product.id} value={product.id}>
                      {product.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-1">
                <Label htmlFor={`line-${line.key}-description`}>{`Description (line ${index + 1})`}</Label>
                <Input
                  id={`line-${line.key}-description`}
                  required
                  maxLength={300}
                  value={line.description}
                  onChange={(event) => updateLine(line.key, { description: event.target.value })}
                />
                {fieldError(errors, `line_${line.key}_description`) && (
                  <p className="text-sm text-red-600">
                    {fieldError(errors, `line_${line.key}_description`)}
                  </p>
                )}
              </div>
            </div>
            <div className="grid grid-cols-3 gap-3">
              <div className="space-y-1">
                <Label htmlFor={`line-${line.key}-quantity`}>{`Quantity (line ${index + 1})`}</Label>
                <Input
                  id={`line-${line.key}-quantity`}
                  required
                  inputMode="decimal"
                  placeholder="2.5"
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
                <Label htmlFor={`line-${line.key}-unit-price`}>{`Unit price (line ${index + 1})`}</Label>
                <Input
                  id={`line-${line.key}-unit-price`}
                  required
                  inputMode="decimal"
                  placeholder="1500.00"
                  value={line.unit_price}
                  onChange={(event) => updateLine(line.key, { unit_price: event.target.value })}
                />
                {fieldError(errors, `line_${line.key}_unit_price`) && (
                  <p className="text-sm text-red-600">
                    {fieldError(errors, `line_${line.key}_unit_price`)}
                  </p>
                )}
              </div>
              <div className="space-y-1">
                <Label htmlFor={`line-${line.key}-discount`}>{`Discount (line ${index + 1})`}</Label>
                <Input
                  id={`line-${line.key}-discount`}
                  inputMode="decimal"
                  placeholder="0.00"
                  value={line.discount}
                  onChange={(event) => updateLine(line.key, { discount: event.target.value })}
                />
                {fieldError(errors, `line_${line.key}_discount`) && (
                  <p className="text-sm text-red-600">
                    {fieldError(errors, `line_${line.key}_discount`)}
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
        <Label htmlFor="invoice-note">Note (optional)</Label>
        <Input id="invoice-note" name="note" maxLength={500} />
      </div>
      <Button type="submit" disabled={pending}>
        {pending ? 'Saving…' : 'Create draft invoice'}
      </Button>
    </form>
  );
}
