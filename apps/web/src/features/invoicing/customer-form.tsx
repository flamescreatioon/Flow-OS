'use client';

import { useState } from 'react';
import {
  createCustomer,
  updateCustomer,
  type CreateCustomerInput,
  type UpdateCustomerInput,
} from '@/features/invoicing/service';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Customer } from '@/types';

interface CustomerFormProps {
  organizationId: string;
  /** When set, the form edits this customer instead of creating one. */
  customer?: Customer;
  onCreated: (customer: Customer) => void;
  onUpdated: (customer: Customer) => void;
  onCancelEdit?: () => void;
}

export function CustomerForm({
  organizationId,
  customer,
  onCreated,
  onUpdated,
  onCancelEdit,
}: CustomerFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);

    const input: CreateCustomerInput = {
      name: String(data.get('name') ?? '').trim(),
      email: String(data.get('email') ?? '').trim() || null,
      phone: String(data.get('phone') ?? '').trim() || null,
      address: String(data.get('address') ?? '').trim() || null,
    };

    if (!input.name) {
      setErrors({ form: null, fields: { name: ['A customer name is required.'] } });
      return;
    }

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      if (customer) {
        const changes: UpdateCustomerInput = input;
        const updated = await updateCustomer(organizationId, customer.id, changes);
        onUpdated(updated);
      } else {
        const created = await createCustomer(organizationId, input);
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
      key={customer?.id ?? 'create'}
      onSubmit={onSubmit}
      className="space-y-4"
      aria-label={customer ? 'Edit customer' : 'Create customer'}
    >
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="customer-name">Name</Label>
        <Input
          id="customer-name"
          name="name"
          required
          maxLength={200}
          defaultValue={customer?.name ?? ''}
        />
        {fieldError(errors, 'name') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'name')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="customer-email">Email (optional)</Label>
        <Input
          id="customer-email"
          name="email"
          type="email"
          maxLength={255}
          defaultValue={customer?.email ?? ''}
        />
        <p className="text-xs text-on-surface-variant">
          Used as the default invoice email recipient.
        </p>
        {fieldError(errors, 'email') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'email')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="customer-phone">Phone (optional)</Label>
        <Input
          id="customer-phone"
          name="phone"
          maxLength={50}
          defaultValue={customer?.phone ?? ''}
        />
        <p className="text-xs text-on-surface-variant">
          Used as the default SMS and WhatsApp recipient.
        </p>
      </div>
      <div className="space-y-1">
        <Label htmlFor="customer-address">Address (optional)</Label>
        <Input
          id="customer-address"
          name="address"
          maxLength={255}
          defaultValue={customer?.address ?? ''}
        />
      </div>
      <div className="flex items-center gap-3">
        <Button type="submit" disabled={pending}>
          {pending ? 'Saving…' : customer ? 'Save changes' : 'Create customer'}
        </Button>
        {customer && onCancelEdit && (
          <Button type="button" variant="ghost" onClick={onCancelEdit} disabled={pending}>
            Cancel
          </Button>
        )}
      </div>
    </form>
  );
}
