'use client';

import { useState } from 'react';
import {
  createProduct,
  updateProduct,
  type CreateProductInput,
  type UpdateProductInput,
} from '@/features/catalogue/service';
import { isCanonicalDecimal } from '@/utils/decimal';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Category, Product, ProductType, UnitOfMeasure } from '@/types';

const PRODUCT_TYPES: Array<{ value: ProductType; label: string }> = [
  { value: 'stocked', label: 'Stocked (tracked in inventory)' },
  { value: 'non_stocked', label: 'Non-stocked (resold, not tracked)' },
  { value: 'service', label: 'Service (no stock)' },
];

interface ProductFormProps {
  organizationId: string;
  units: UnitOfMeasure[];
  categories: Category[];
  /** When set, the form edits this product instead of creating one. */
  product?: Product;
  onCreated: (product: Product) => void;
  onUpdated: (product: Product) => void;
  onCancelEdit?: () => void;
}

export function ProductForm({
  organizationId,
  units,
  categories,
  product,
  onCreated,
  onUpdated,
  onCancelEdit,
}: ProductFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  if (units.length === 0) {
    return (
      <p className="text-sm text-on-surface-variant">
        Create a unit of measure first — every product needs one.
      </p>
    );
  }

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const price = String(data.get('price') ?? '').trim();

    if (!isCanonicalDecimal(price, 4)) {
      setErrors({
        form: null,
        fields: { price: ['Enter a price with at most 4 decimal places, e.g. 1500.50.'] },
      });
      return;
    }

    const categoryId = String(data.get('category_id') ?? '');
    const input: CreateProductInput = {
      name: String(data.get('name') ?? '').trim(),
      sku: String(data.get('sku') ?? '').trim() || null,
      type: String(data.get('type') ?? '') as ProductType,
      price,
      unit_of_measure_id: String(data.get('unit_of_measure_id') ?? ''),
      category_id: categoryId || null,
    };

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      if (product) {
        // Fields the form does not offer (e.g. tax_code) are left untouched.
        const changes: UpdateProductInput = input;
        const updated = await updateProduct(organizationId, product.id, changes);
        onUpdated(updated);
      } else {
        const created = await createProduct(organizationId, input);
        onCreated(created);
        event.currentTarget.reset();
      }
    } catch (caught) {
      setErrors(toFormErrors(caught));
    } finally {
      setPending(false);
    }
  }

  return (
    <form
      key={product?.id ?? 'create'}
      onSubmit={onSubmit}
      className="space-y-4"
      aria-label={product ? 'Edit product' : 'Create product'}
    >
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="product-name">Name</Label>
        <Input
          id="product-name"
          name="name"
          required
          maxLength={200}
          defaultValue={product?.name ?? ''}
        />
        {fieldError(errors, 'name') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'name')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="product-sku">SKU (optional)</Label>
        <Input
          id="product-sku"
          name="sku"
          maxLength={100}
          defaultValue={product?.sku ?? ''}
        />
      </div>
      <div className="space-y-1">
        <Label htmlFor="product-type">Type</Label>
        <select
          id="product-type"
          name="type"
          required
          defaultValue={product?.type ?? 'stocked'}
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
        >
          {PRODUCT_TYPES.map((type) => (
            <option key={type.value} value={type.value}>
              {type.label}
            </option>
          ))}
        </select>
      </div>
      <div className="space-y-1">
        <Label htmlFor="product-price">Price</Label>
        <Input
          id="product-price"
          name="price"
          required
          inputMode="decimal"
          placeholder="1500.50"
          defaultValue={product?.price ?? ''}
        />
        <p className="text-xs text-on-surface-variant">
          Exact decimal, at most 4 places, in the organization currency.
        </p>
        {fieldError(errors, 'price') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'price')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="product-unit">Unit of measure</Label>
        <select
          id="product-unit"
          name="unit_of_measure_id"
          required
          defaultValue={product?.unit_of_measure?.id ?? ''}
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
        >
          {units.map((unit) => (
            <option key={unit.id} value={unit.id}>
              {unit.name} ({unit.code})
            </option>
          ))}
        </select>
      </div>
      <div className="space-y-1">
        <Label htmlFor="product-category">Category (optional)</Label>
        <select
          id="product-category"
          name="category_id"
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
          defaultValue={product?.category?.id ?? ''}
        >
          <option value="">None</option>
          {categories.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
      </div>
      <div className="flex items-center gap-3">
        <Button type="submit" disabled={pending}>
          {pending ? 'Saving…' : product ? 'Save changes' : 'Create product'}
        </Button>
        {product && onCancelEdit && (
          <Button type="button" variant="ghost" onClick={onCancelEdit} disabled={pending}>
            Cancel
          </Button>
        )}
      </div>
    </form>
  );
}
