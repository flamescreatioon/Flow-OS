'use client';

import { useState } from 'react';
import { createCategory, updateCategory } from '@/features/catalogue/service';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Category } from '@/types';

interface CategoryFormProps {
  organizationId: string;
  /** When set, the form edits this category instead of creating one. */
  category?: Category;
  onCreated: (category: Category) => void;
  onUpdated: (category: Category) => void;
  onCancelEdit?: () => void;
}

export function CategoryForm({
  organizationId,
  category,
  onCreated,
  onUpdated,
  onCancelEdit,
}: CategoryFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const description = String(data.get('description') ?? '').trim();

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      if (category) {
        const updated = await updateCategory(organizationId, category.id, {
          name: String(data.get('name') ?? '').trim(),
          description: description || null,
        });
        onUpdated(updated);
      } else {
        const created = await createCategory(organizationId, {
          name: String(data.get('name') ?? '').trim(),
          description: description || null,
        });
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
      key={category?.id ?? 'create'}
      onSubmit={onSubmit}
      className="space-y-4"
      aria-label={category ? 'Edit category' : 'Create category'}
    >
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="category-name">Name</Label>
        <Input
          id="category-name"
          name="name"
          required
          maxLength={150}
          defaultValue={category?.name ?? ''}
        />
        {fieldError(errors, 'name') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'name')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="category-description">Description (optional)</Label>
        <Input
          id="category-description"
          name="description"
          maxLength={500}
          defaultValue={category?.description ?? ''}
        />
      </div>
      <div className="flex items-center gap-3">
        <Button type="submit" disabled={pending}>
          {pending ? 'Saving…' : category ? 'Save changes' : 'Create category'}
        </Button>
        {category && onCancelEdit && (
          <Button type="button" variant="ghost" onClick={onCancelEdit} disabled={pending}>
            Cancel
          </Button>
        )}
      </div>
    </form>
  );
}
