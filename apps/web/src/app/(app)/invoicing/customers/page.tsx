'use client';

import { useEffect, useState } from 'react';
import {
  archiveCustomer,
  listCustomers,
} from '@/features/invoicing/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { CustomerForm } from '@/features/invoicing/customer-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';
import type { Customer, PaginatedResponse } from '@/types';

const EMPTY_CUSTOMERS: PaginatedResponse<Customer> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 100,
  total: 0,
};

export default function CustomersPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [customers, setCustomers] = useState<PaginatedResponse<Customer>>(EMPTY_CUSTOMERS);
  const [editing, setEditing] = useState<Customer | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [rowError, setRowError] = useState<string | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const result = await listCustomers(organizationId, { perPage: 100 });
        if (!cancelled) setCustomers(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load customers. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId]);

  async function archive(customer: Customer): Promise<void> {
    if (!organizationId) return;
    setRowError(null);
    try {
      const updated = await archiveCustomer(organizationId, customer.id);
      setCustomers((current) => ({
        ...current,
        data: current.data.map((item) => (item.id === updated.id ? updated : item)),
      }));
    } catch {
      setRowError('Unable to archive the customer. Please try again.');
    }
  }

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to manage its customers.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Customers</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Billing contacts for {activeOrganization.name}. Customers are
          archived rather than deleted.
        </p>
      </header>

      <section aria-label="Customers" className="space-y-4">
        {loadError && (
          <p role="alert" className="text-sm text-red-600">
            {loadError}
          </p>
        )}

        {loading ? (
          <p className="text-sm text-on-surface-variant" aria-live="polite">
            Loading customers…
          </p>
        ) : customers.data.length === 0 ? (
          <p className="text-sm text-on-surface-variant">
            No customers yet. Create the first one to attach invoices to.
          </p>
        ) : (
          <ul className="space-y-2">
            {customers.data.map((customer) => (
              <li
                key={customer.id}
                className="flex items-center justify-between gap-3 rounded-2xl bg-surface p-4"
              >
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-on-surface">
                    {customer.name}
                  </p>
                  <p className="truncate text-xs text-on-surface-variant">
                    {[customer.email, customer.phone, customer.address]
                      .filter(Boolean)
                      .join(' · ') || 'No contact details'}
                  </p>
                </div>
                {customer.archived_at ? (
                  <Badge tone="warning">Archived</Badge>
                ) : (
                  <div className="flex shrink-0 items-center gap-2">
                    <Button variant="ghost" onClick={() => setEditing(customer)}>
                      Edit
                    </Button>
                    <Button variant="ghost" onClick={() => void archive(customer)}>
                      Archive
                    </Button>
                  </div>
                )}
              </li>
            ))}
          </ul>
        )}
      </section>

      <Card>
        <CardTitle>{editing ? 'Edit customer' : 'Create a customer'}</CardTitle>
        {rowError && (
          <p role="alert" className="mt-2 text-sm text-red-600">
            {rowError}
          </p>
        )}
        <div className="mt-4">
          <CustomerForm
            organizationId={activeOrganization.id}
            customer={editing ?? undefined}
            onCreated={(created) =>
              setCustomers((current) => ({
                ...current,
                data: [...current.data, created],
                total: current.total + 1,
              }))
            }
            onUpdated={(updated) => {
              setCustomers((current) => ({
                ...current,
                data: current.data.map((item) => (item.id === updated.id ? updated : item)),
              }));
              setEditing(null);
            }}
            onCancelEdit={() => setEditing(null)}
          />
        </div>
      </Card>
    </div>
  );
}
