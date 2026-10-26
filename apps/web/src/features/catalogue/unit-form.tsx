'use client';

import { useState } from 'react';
import { createUnitOfMeasure } from '@/features/catalogue/service';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { UnitOfMeasure } from '@/types';

interface UnitOfMeasureFormProps {
  organizationId: string;
  onCreated: (unit: UnitOfMeasure) => void;
}

export function UnitOfMeasureForm({
  organizationId,
  onCreated,
}: UnitOfMeasureFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      const unit = await createUnitOfMeasure(organizationId, {
        code: String(data.get('code') ?? '').trim(),
        name: String(data.get('name') ?? '').trim(),
        precision: Number(data.get('precision') ?? 0),
      });
      onCreated(unit);
      event.currentTarget.reset();
    } catch (caught) {
      setErrors(toFormErrors(caught));
    } finally {
      setPending(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Create unit of measure">
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="unit-code">Code</Label>
        <Input id="unit-code" name="code" required maxLength={20} placeholder="kg" />
        {fieldError(errors, 'code') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'code')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="unit-name">Name</Label>
        <Input id="unit-name" name="name" required maxLength={100} placeholder="Kilogram" />
      </div>
      <div className="space-y-1">
        <Label htmlFor="unit-precision">Decimal places</Label>
        <select
          id="unit-precision"
          name="precision"
          defaultValue="0"
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
        >
          <option value="0">0 — whole units only</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3 — thousandths</option>
        </select>
        <p className="text-xs text-on-surface-variant">
          Bounds how precisely quantities in this unit can be stated.
        </p>
      </div>
      <Button type="submit" disabled={pending}>
        {pending ? 'Creating…' : 'Create unit'}
      </Button>
    </form>
  );
}
