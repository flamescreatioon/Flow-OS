'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import {
  listExpenseCategories,
  listExpenses,
  voidExpense,
} from '@/features/expenses/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { ExpenseForm } from '@/features/expenses/expense-form';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { formatMoney } from '@/utils/money';
import type { Expense, ExpenseCategory, ExpenseStatus, PaginatedResponse } from '@/types';

const EMPTY_EXPENSES: PaginatedResponse<Expense> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
};

const STATUS_TONES: Record<ExpenseStatus, 'success' | 'warning'> = {
  recorded: 'success',
  voided: 'warning',
};

export default function ExpensesPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [expenses, setExpenses] = useState<PaginatedResponse<Expense>>(EMPTY_EXPENSES);
  const [categories, setCategories] = useState<ExpenseCategory[]>([]);
  const [categoryFilter, setCategoryFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [rowError, setRowError] = useState<string | null>(null);
  const [rowBusy, setRowBusy] = useState<string | null>(null);
  const [editing, setEditing] = useState<Expense | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const categoryPage = await listExpenseCategories(organizationId, { perPage: 100 });
        if (!cancelled) setCategories(categoryPage.data);
      } catch {
        // The expense list still renders when categories fail to load.
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId]);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const result = await listExpenses(organizationId, {
          expenseCategoryId: categoryFilter || undefined,
          status: statusFilter ? (statusFilter as ExpenseStatus) : undefined,
          page,
        });
        if (!cancelled) setExpenses(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load expenses. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId, categoryFilter, statusFilter, page]);

  async function voidRecorded(expense: Expense): Promise<void> {
    if (!organizationId) return;
    setRowError(null);
    setRowBusy(expense.id);
    try {
      const updated = await voidExpense(organizationId, expense.id);
      setExpenses((current) => ({
        ...current,
        data: current.data.map((item) => (item.id === updated.id ? updated : item)),
      }));
    } catch {
      setRowError('Unable to void the expense. Please try again.');
    } finally {
      setRowBusy(null);
    }
  }

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to manage its expenses.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold text-on-surface">Expenses</h1>
          <p className="mt-1 text-sm text-on-surface-variant">
            Business spending across {activeOrganization.name}. Expenses are
            editable while recorded; voiding keeps the record as evidence.
          </p>
        </div>
        <Link
          href="/expenses/new"
          className="inline-flex h-10 items-center rounded-full bg-primary px-4 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90"
        >
          Record an expense
        </Link>
      </header>

      <section aria-label="Expenses" className="space-y-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1">
            <Label htmlFor="expense-category-filter">Category</Label>
            <select
              id="expense-category-filter"
              value={categoryFilter}
              onChange={(event) => {
                setLoading(true);
                setPage(1);
                setCategoryFilter(event.target.value);
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All categories</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>
          <div className="space-y-1">
            <Label htmlFor="expense-status-filter">Status</Label>
            <select
              id="expense-status-filter"
              value={statusFilter}
              onChange={(event) => {
                setLoading(true);
                setPage(1);
                setStatusFilter(event.target.value);
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All statuses</option>
              <option value="recorded">Recorded</option>
              <option value="voided">Voided</option>
            </select>
          </div>
        </div>

        {loadError && (
          <p role="alert" className="text-sm text-red-600">
            {loadError}
          </p>
        )}
        {rowError && (
          <p role="alert" className="text-sm text-red-600">
            {rowError}
          </p>
        )}

        {loading ? (
          <p className="text-sm text-on-surface-variant" aria-live="polite">
            Loading expenses…
          </p>
        ) : expenses.data.length === 0 ? (
          <p className="text-sm text-on-surface-variant">
            No expenses yet. Record the first one to start tracking spend.
          </p>
        ) : (
          <div className="overflow-x-auto rounded-2xl bg-surface">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-gray-200 text-on-surface-variant">
                  <th scope="col" className="px-4 py-3 font-medium">Expense</th>
                  <th scope="col" className="px-4 py-3 font-medium">Category</th>
                  <th scope="col" className="px-4 py-3 font-medium">Payee</th>
                  <th scope="col" className="px-4 py-3 font-medium">Spent on</th>
                  <th scope="col" className="px-4 py-3 font-medium text-right">Amount</th>
                  <th scope="col" className="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {expenses.data.map((expense) => (
                  <tr key={expense.id} className="border-b border-gray-100 last:border-0">
                    <td className="px-4 py-3">
                      <p className="font-medium text-on-surface">
                        {expense.description}
                      </p>
                      <p className="text-xs text-on-surface-variant">
                        {expense.document_number}
                      </p>
                    </td>
                    <td className="px-4 py-3 text-on-surface-variant">
                      {expense.expense_category?.name ?? '—'}
                    </td>
                    <td className="px-4 py-3 text-on-surface-variant">
                      {expense.payee ?? '—'}
                    </td>
                    <td className="px-4 py-3 text-on-surface-variant">{expense.spent_on}</td>
                    <td className="px-4 py-3 text-right font-mono text-on-surface">
                      {formatMoney(expense.amount)}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Badge tone={STATUS_TONES[expense.status]}>{expense.status}</Badge>
                        {expense.status === 'recorded' && (
                          <>
                            <Button
                              variant="ghost"
                              onClick={() => setEditing(expense)}
                            >
                              Edit
                            </Button>
                            <Button
                              variant="ghost"
                              disabled={rowBusy !== null}
                              onClick={() => void voidRecorded(expense)}
                            >
                              {rowBusy === expense.id ? 'Voiding…' : 'Void'}
                            </Button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <Pagination
          currentPage={expenses.current_page}
          lastPage={expenses.last_page}
          disabled={loading}
          onPageChange={(target) => {
            setLoading(true);
            setPage(target);
          }}
        />
      </section>

      {editing && (
        <Card>
          <CardTitle>Edit expense</CardTitle>
          <div className="mt-4">
            <ExpenseForm
              organizationId={activeOrganization.id}
              categories={categories.filter((category) => !category.archived_at)}
              expense={editing}
              onUpdated={(updated) => {
                setExpenses((current) => ({
                  ...current,
                  data: current.data.map((item) => (item.id === updated.id ? updated : item)),
                }));
                setEditing(null);
              }}
              onCancelEdit={() => setEditing(null)}
            />
          </div>
        </Card>
      )}
    </div>
  );
}
