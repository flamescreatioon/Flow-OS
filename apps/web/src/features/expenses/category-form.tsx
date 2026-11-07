'use client';

import { useState } from 'react';
import {
  createExpenseCategory,
  updateExpenseCategory,
  type CreateExpenseCategoryInput,
  type UpdateExpenseCategoryInput,
} from '@/features/expenses/service';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExpenseCategory } from '@/types';

interface CategoryFormProps {
  organizationId: string;
  /** When set, the form edits this category instead of creating one. */
  category?: ExpenseCategory;
  /** Only used in create mode; the edit flow updates in place. */
  onCreated?: (category: ExpenseCategory) => void;
  onUpdated?: (category: ExpenseCategory) => void;
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

    const input: CreateExpenseCategoryInput = {
      name: String(data.get('name') ?? '').trim(),
      description: String(data.get('description') ?? '').trim() || null,
    };

    if (!input.name) {
      setErrors({ form: null, fields: { name: ['A category name is required.'] } });
      return;
    }

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      if (category) {
        const changes: UpdateExpenseCategoryInput = input;
        const updated = await updateExpenseCategory(organizationId, category.id, changes);
        onUpdated?.(updated);
      } else {
        const created = await createExpenseCategory(organizationId, input);
        onCreated?.(created);
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
      aria-label={category ? 'Edit expense category' : 'Create expense category'}
    >
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="expense-category-name">Name</Label>
        <Input
          id="expense-category-name"
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
        <Label htmlFor="expense-category-description">Description (optional)</Label>
        <Input
          id="expense-category-description"
          name="description"
          maxLength={255}
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
