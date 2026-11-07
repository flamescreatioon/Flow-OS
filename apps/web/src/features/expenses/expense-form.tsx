'use client';

import { useState } from 'react';
import {
  createExpense,
  updateExpense,
  type CreateExpenseInput,
  type UpdateExpenseInput,
} from '@/features/expenses/service';
import { isCanonicalDecimal } from '@/utils/decimal';
import { toFormErrors, fieldError } from '@/features/auth/form-errors';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Expense, ExpenseCategory, PaymentMethod } from '@/types';

const PAYMENT_METHODS: Array<{ value: PaymentMethod; label: string }> = [
  { value: 'cash', label: 'Cash' },
  { value: 'bank_transfer', label: 'Bank transfer' },
  { value: 'card', label: 'Card' },
  { value: 'other', label: 'Other' },
];

interface ExpenseFormProps {
  organizationId: string;
  categories: ExpenseCategory[];
  /** When set, the form edits this expense instead of creating one. */
  expense?: Expense;
  /** Only used in create mode; the edit flow updates in place. */
  onCreated?: (expense: Expense) => void;
  onUpdated: (expense: Expense) => void;
  onCancelEdit?: () => void;
}

export function ExpenseForm({
  organizationId,
  categories,
  expense,
  onCreated,
  onUpdated,
  onCancelEdit,
}: ExpenseFormProps) {
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<{ form: string | null; fields: Record<string, string[]> }>(
    { form: null, fields: {} },
  );

  if (categories.length === 0) {
    return (
      <p className="text-sm text-on-surface-variant">
        Create an expense category first — every expense needs one.
      </p>
    );
  }

  async function onSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const amount = String(data.get('amount') ?? '').trim();

    if (!isCanonicalDecimal(amount, 4)) {
      setErrors({
        form: null,
        fields: { amount: ['Enter an amount with at most 4 decimal places, e.g. 1500.50.'] },
      });
      return;
    }

    const categoryId = String(data.get('expense_category_id') ?? '');
    const method = String(data.get('payment_method') ?? 'cash') as PaymentMethod;
    const payee = String(data.get('payee') ?? '').trim() || null;
    const note = String(data.get('note') ?? '').trim() || null;
    const spentOn = String(data.get('spent_on') ?? '').trim();

    const input: CreateExpenseInput = {
      expense_category_id: categoryId,
      description: String(data.get('description') ?? '').trim(),
      amount,
      spent_on: spentOn || undefined,
      payee,
      payment_method: method,
      note,
    };

    setPending(true);
    setErrors({ form: null, fields: {} });
    try {
      if (expense) {
        const changes: UpdateExpenseInput = input;
        const updated = await updateExpense(organizationId, expense.id, changes);
        onUpdated(updated);
      } else {
        const created = await createExpense(organizationId, input);
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
      key={expense?.id ?? 'create'}
      onSubmit={onSubmit}
      className="space-y-4"
      aria-label={expense ? 'Edit expense' : 'Record expense'}
    >
      {errors.form ? (
        <p role="alert" className="text-sm text-red-600">
          {errors.form}
        </p>
      ) : null}
      <div className="space-y-1">
        <Label htmlFor="expense-category">Category</Label>
        <select
          id="expense-category"
          name="expense_category_id"
          required
          defaultValue={expense?.expense_category_id ?? ''}
          className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
        >
          <option value="">Choose a category</option>
          {categories.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
        {fieldError(errors, 'expense_category_id') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'expense_category_id')}</p>
        )}
      </div>
      <div className="space-y-1">
        <Label htmlFor="expense-description">Description</Label>
        <Input
          id="expense-description"
          name="description"
          required
          maxLength={300}
          defaultValue={expense?.description ?? ''}
          placeholder="Fuel for delivery bike"
        />
        {fieldError(errors, 'description') && (
          <p className="text-sm text-red-600">{fieldError(errors, 'description')}</p>
        )}
      </div>
      <div className="grid grid-cols-2 gap-3">
        <div className="space-y-1">
          <Label htmlFor="expense-amount">Amount</Label>
          <Input
            id="expense-amount"
            name="amount"
            required
            inputMode="decimal"
            placeholder="1500.50"
            defaultValue={expense?.amount ?? ''}
          />
          <p className="text-xs text-on-surface-variant">
            Exact decimal, at most 4 places.
          </p>
          {fieldError(errors, 'amount') && (
            <p className="text-sm text-red-600">{fieldError(errors, 'amount')}</p>
          )}
        </div>
        <div className="space-y-1">
          <Label htmlFor="expense-spent-on">Spent on (optional)</Label>
          <Input
            id="expense-spent-on"
            name="spent_on"
            type="date"
            defaultValue={expense?.spent_on ?? ''}
          />
          <p className="text-xs text-on-surface-variant">Defaults to today.</p>
        </div>
      </div>
      <div className="grid grid-cols-2 gap-3">
        <div className="space-y-1">
          <Label htmlFor="expense-payee">Payee (optional)</Label>
          <Input
            id="expense-payee"
            name="payee"
            maxLength={200}
            defaultValue={expense?.payee ?? ''}
            placeholder="Total Filling Station"
          />
        </div>
        <div className="space-y-1">
          <Label htmlFor="expense-method">Payment method</Label>
          <select
            id="expense-method"
            name="payment_method"
            defaultValue={expense?.payment_method ?? 'cash'}
            className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
          >
            {PAYMENT_METHODS.map((method) => (
              <option key={method.value} value={method.value}>
                {method.label}
              </option>
            ))}
          </select>
        </div>
      </div>
      <div className="space-y-1">
        <Label htmlFor="expense-note">Note (optional)</Label>
        <Input id="expense-note" name="note" maxLength={500} defaultValue={expense?.note ?? ''} />
      </div>
      <div className="flex items-center gap-3">
        <Button type="submit" disabled={pending}>
          {pending ? 'Saving…' : expense ? 'Save changes' : 'Record expense'}
        </Button>
        {expense && onCancelEdit && (
          <Button type="button" variant="ghost" onClick={onCancelEdit} disabled={pending}>
            Cancel
          </Button>
        )}
      </div>
    </form>
  );
}
