'use client';

import { useEffect, useState } from 'react';
import {
  archiveSupplier,
  listSuppliers,
} from '@/features/purchasing/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { SupplierForm } from '@/features/purchasing/supplier-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';
import type { PaginatedResponse, Supplier } from '@/types';

const EMPTY_SUPPLIERS: PaginatedResponse<Supplier> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 100,
  total: 0,
};

export default function SuppliersPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [suppliers, setSuppliers] = useState<PaginatedResponse<Supplier>>(EMPTY_SUPPLIERS);
  const [editing, setEditing] = useState<Supplier | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [rowError, setRowError] = useState<string | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const result = await listSuppliers(organizationId, { perPage: 100 });
        if (!cancelled) setSuppliers(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load suppliers. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId]);

  async function archive(supplier: Supplier): Promise<void> {
    if (!organizationId) return;
    setRowError(null);
    try {
      const updated = await archiveSupplier(organizationId, supplier.id);
      setSuppliers((current) => ({
        ...current,
        data: current.data.map((item) => (item.id === updated.id ? updated : item)),
      }));
    } catch {
      setRowError('Unable to archive the supplier. Please try again.');
    }
  }

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to manage its suppliers.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Suppliers</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Procurement contacts for {activeOrganization.name}. Suppliers are
          archived rather than deleted.
        </p>
      </header>

      <section aria-label="Suppliers" className="space-y-4">
        {loadError && (
          <p role="alert" className="text-sm text-red-600">
            {loadError}
          </p>
        )}

        {loading ? (
          <p className="text-sm text-on-surface-variant" aria-live="polite">
            Loading suppliers…
          </p>
        ) : suppliers.data.length === 0 ? (
          <p className="text-sm text-on-surface-variant">
            No suppliers yet. Create the first one to attribute receipts.
          </p>
        ) : (
          <ul className="space-y-2">
            {suppliers.data.map((supplier) => (
              <li
                key={supplier.id}
                className="flex items-center justify-between gap-3 rounded-2xl bg-surface p-4"
              >
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-on-surface">
                    {supplier.name}
                  </p>
                  <p className="truncate text-xs text-on-surface-variant">
                    {[supplier.email, supplier.phone, supplier.address]
                      .filter(Boolean)
                      .join(' · ') || 'No contact details'}
                  </p>
                </div>
                {supplier.archived_at ? (
                  <Badge tone="warning">Archived</Badge>
                ) : (
                  <div className="flex shrink-0 items-center gap-2">
                    <Button variant="ghost" onClick={() => setEditing(supplier)}>
                      Edit
                    </Button>
                    <Button variant="ghost" onClick={() => void archive(supplier)}>
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
        <CardTitle>{editing ? 'Edit supplier' : 'Create a supplier'}</CardTitle>
        {rowError && (
          <p role="alert" className="mt-2 text-sm text-red-600">
            {rowError}
          </p>
        )}
        <div className="mt-4">
          <SupplierForm
            organizationId={activeOrganization.id}
            supplier={editing ?? undefined}
            onCreated={(created) =>
              setSuppliers((current) => ({
                ...current,
                data: [...current.data, created],
                total: current.total + 1,
              }))
            }
            onUpdated={(updated) => {
              setSuppliers((current) => ({
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
