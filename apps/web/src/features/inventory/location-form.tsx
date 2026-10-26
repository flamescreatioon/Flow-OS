'use client';

import { useState } from 'react';
import {
  createInventoryLocation,
} from '@/features/inventory/service';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Branch, InventoryLocation } from '@/types';

interface LocationFormProps {
  organizationId: string;
  /** Branches of the organization; a location may optionally sit inside one. */
  branches: Branch[];
  onCreated: (location: InventoryLocation) => void;
}

export function LocationForm({ organizationId, branches, onCreated }: LocationFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const branchId = String(data.get('branch_id') ?? '');

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      const location = await createInventoryLocation(organizationId, {
        code: String(data.get('code') ?? '').trim(),
        name: String(data.get('name') ?? '').trim(),
        branch_id: branchId || null,
      });
      onCreated(location);
      event.currentTarget.reset();
    } catch (caught) {
      setErrors(toFormErrors(caught));
    } finally {
      setPending(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Create inventory location">
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="location-code">Code</Label>
        <Input id="location-code" name="code" required maxLength={50} placeholder="MAIN" />
        <p className="text-xs text-on-surface-variant">Unique within the organization.</p>
        {fieldError(errors, 'code') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'code')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="location-name">Name</Label>
        <Input id="location-name" name="name" required maxLength={150} placeholder="Main store" />
      </div>
      <div className="space-y-1">
        <Label htmlFor="location-branch">Branch (optional)</Label>
        <select
          id="location-branch"
          name="branch_id"
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
          defaultValue=""
        >
          <option value="">Organization-wide</option>
          {branches.map((branch) => (
            <option key={branch.id} value={branch.id}>
              {branch.name} ({branch.code})
            </option>
          ))}
        </select>
      </div>
      <Button type="submit" disabled={pending}>
        {pending ? 'Creating…' : 'Create location'}
      </Button>
    </form>
  );
}
