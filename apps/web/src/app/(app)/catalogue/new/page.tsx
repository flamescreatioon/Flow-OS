'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ProductForm } from '@/features/catalogue/product-form';
import { listCategories, listUnitOfMeasures } from '@/features/catalogue/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { Card, CardTitle } from '@/components/ui/card';
import type { Category, Product, UnitOfMeasure } from '@/types';

export default function NewProductPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [units, setUnits] = useState<UnitOfMeasure[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [created, setCreated] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const [unitPage, categoryPage] = await Promise.all([
          listUnitOfMeasures(organizationId),
          listCategories(organizationId),
        ]);
        if (!cancelled) {
          setUnits(unitPage.data);
          setCategories(categoryPage.data);
        }
      } catch {
        if (!cancelled) setLoadError('Unable to load units and categories. Please try again.');
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
        Select an organization to add products.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Add a product</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Products describe what {activeOrganization.name} buys and sells.
        </p>
      </header>

      {created && (
        <div className="rounded-2xl bg-primary-container p-4" aria-live="polite">
          <p className="text-sm font-medium text-on-primary-container">
            {created.name} was created.
          </p>
          <Link
            href="/catalogue"
            className="mt-2 inline-flex h-9 items-center rounded-full bg-primary px-4 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90"
          >
            Back to catalogue
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
          Loading catalogue reference data…
        </p>
      ) : (
        <Card>
          <CardTitle>Create a product</CardTitle>
          <div className="mt-4">
            <ProductForm
              organizationId={activeOrganization.id}
              units={units}
              categories={categories}
              onCreated={(product) => setCreated(product)}
              onUpdated={() => undefined}
            />
          </div>
        </Card>
      )}

      <p className="text-sm text-on-surface-variant">
        <Link href="/catalogue" className="font-medium text-primary hover:underline">
          Back to catalogue
        </Link>{' '}
        — stocked products appear in inventory once stock is recorded.
      </p>
    </div>
  );
}
