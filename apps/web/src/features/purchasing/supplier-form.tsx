'use client';

import { useState } from 'react';
import {
  createSupplier,
  updateSupplier,
  type CreateSupplierInput,
  type UpdateSupplierInput,
} from '@/features/purchasing/service';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Supplier } from '@/types';

interface SupplierFormProps {
  organizationId: string;
  /** When set, the form edits this supplier instead of creating one. */
  supplier?: Supplier;
  onCreated: (supplier: Supplier) => void;
  onUpdated: (supplier: Supplier) => void;
  onCancelEdit?: () => void;
}

export function SupplierForm({
  organizationId,
  supplier,
  onCreated,
  onUpdated,
  onCancelEdit,
}: SupplierFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);

    const input: CreateSupplierInput = {
      name: String(data.get('name') ?? '').trim(),
      email: String(data.get('email') ?? '').trim() || null,
      phone: String(data.get('phone') ?? '').trim() || null,
      address: String(data.get('address') ?? '').trim() || null,
    };

    if (!input.name) {
      setErrors({ form: null, fields: { name: ['A supplier name is required.'] } });
      return;
    }

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      if (supplier) {
        const changes: UpdateSupplierInput = input;
        const updated = await updateSupplier(organizationId, supplier.id, changes);
        onUpdated(updated);
      } else {
        const created = await createSupplier(organizationId, input);
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
      key={supplier?.id ?? 'create'}
      onSubmit={onSubmit}
      className="space-y-4"
      aria-label={supplier ? 'Edit supplier' : 'Create supplier'}
    >
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="supplier-name">Name</Label>
        <Input
          id="supplier-name"
          name="name"
          required
          maxLength={200}
          defaultValue={supplier?.name ?? ''}
        />
        {fieldError(errors, 'name') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'name')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="supplier-email">Email (optional)</Label>
        <Input
          id="supplier-email"
          name="email"
          type="email"
          maxLength={255}
          defaultValue={supplier?.email ?? ''}
        />
        {fieldError(errors, 'email') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'email')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="supplier-phone">Phone (optional)</Label>
        <Input
          id="supplier-phone"
          name="phone"
          maxLength={50}
          defaultValue={supplier?.phone ?? ''}
        />
      </div>
      <div className="space-y-1">
        <Label htmlFor="supplier-address">Address (optional)</Label>
        <Input
          id="supplier-address"
          name="address"
          maxLength={255}
          defaultValue={supplier?.address ?? ''}
        />
      </div>
      <div className="flex items-center gap-3">
        <Button type="submit" disabled={pending}>
          {pending ? 'Saving…' : supplier ? 'Save changes' : 'Create supplier'}
        </Button>
        {supplier && onCancelEdit && (
          <Button type="button" variant="ghost" onClick={onCancelEdit} disabled={pending}>
            Cancel
          </Button>
        )}
      </div>
    </form>
  );
}
