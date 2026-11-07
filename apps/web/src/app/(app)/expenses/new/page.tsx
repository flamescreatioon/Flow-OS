'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { listExpenseCategories } from '@/features/expenses/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { ExpenseForm } from '@/features/expenses/expense-form';
import { CategoryForm } from '@/features/expenses/category-form';
import { Card, CardTitle } from '@/components/ui/card';
import type { Expense, ExpenseCategory } from '@/types';

export default function NewExpensePage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [categories, setCategories] = useState<ExpenseCategory[]>([]);
  const [recorded, setRecorded] = useState<Expense | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const categoryPage = await listExpenseCategories(organizationId, { perPage: 100 });
        if (!cancelled) setCategories(categoryPage.data);
      } catch {
        if (!cancelled) setLoadError('Unable to load expense categories. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId]);

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to record expenses.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Record an expense</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Record business spending for {activeOrganization.name}. Set up
          categories here too.
        </p>
      </header>

      {recorded && (
        <div className="rounded-2xl bg-primary-container p-4" aria-live="polite">
          <p className="text-sm font-medium text-on-primary-container">
            {recorded.document_number} was recorded.
          </p>
          <Link
            href="/expenses"
            className="mt-2 inline-flex h-9 items-center rounded-full bg-primary px-4 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90"
          >
            Back to expenses
          </Link>
        </div>
      )}

      {loadError && (
        <p role="alert" className="text-sm text-red-600">
          {loadError}
        </p>
      )}

      {loading ? (
        <p className="text-sm text-on-surface-variant" aria-live="polite">
          Loading expense categories…
        </p>
      ) : (
        <div className="grid gap-4 lg:grid-cols-2">
          <Card>
            <CardTitle>Expense</CardTitle>
            <div className="mt-4">
              <ExpenseForm
                organizationId={activeOrganization.id}
                categories={categories.filter((category) => !category.archived_at)}
                onCreated={(expense) => setRecorded(expense)}
                onUpdated={() => undefined}
              />
            </div>
          </Card>

          <Card>
            <CardTitle>Expense categories</CardTitle>
            {categories.length > 0 && (
              <ul className="mt-4 space-y-2">
                {categories.map((category) => (
                  <li
                    key={category.id}
                    className="flex items-center justify-between gap-3 rounded-xl bg-surface-container-high px-3 py-2"
                  >
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium text-on-surface">
                        {category.name}
                      </p>
                      {category.description && (
                        <p className="truncate text-xs text-on-surface-variant">
                          {category.description}
                        </p>
                      )}
                    </div>
                    {category.archived_at && (
                      <span className="text-xs text-on-surface-variant">Archived</span>
                    )}
                  </li>
                ))}
              </ul>
            )}
            <div className="mt-4">
              <CategoryForm
                organizationId={activeOrganization.id}
                onCreated={(category) => setCategories((current) => [...current, category])}
              />
            </div>
          </Card>
        </div>
      )}

      <p className="text-sm text-on-surface-variant">
        <Link href="/expenses" className="font-medium text-primary hover:underline">
          Back to expenses
        </Link>
      </p>
    </div>
  );
}
