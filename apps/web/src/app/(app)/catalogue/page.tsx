'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import {
  listCategories,
  listProducts,
  listUnitOfMeasures,
  archiveProduct,
  restoreProduct,
} from '@/features/catalogue/service';
import { CategoryForm } from '@/features/catalogue/category-form';
import { ProductForm } from '@/features/catalogue/product-form';
import { UnitOfMeasureForm } from '@/features/catalogue/unit-form';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { formatDecimalString } from '@/utils/decimal';
import type {
  Category,
  PaginatedResponse,
  Product,
  ProductType,
  UnitOfMeasure,
} from '@/types';

type ArchivedFilter = 'false' | 'all' | 'true';

const EMPTY_PAGE: PaginatedResponse<Product> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
};

export default function CataloguePage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [products, setProducts] = useState<PaginatedResponse<Product>>(EMPTY_PAGE);
  const [units, setUnits] = useState<UnitOfMeasure[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [page, setPage] = useState(1);
  const [typeFilter, setTypeFilter] = useState('' as ProductType | '');
  const [archivedFilter, setArchivedFilter] = useState<ArchivedFilter>('false');
  const [search, setSearch] = useState('');
  const [reload, setReload] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [rowError, setRowError] = useState<string | null>(null);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const result = await listProducts(organizationId, {
          type: typeFilter || undefined,
          archived: archivedFilter,
          q: search.trim() || undefined,
          page,
          perPage: 25,
        });
        if (!cancelled) setProducts(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load products. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId, typeFilter, archivedFilter, search, page, reload]);

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
        // Reference data loads alongside the primary list; the product list
        // still renders when these fail.
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId]);

  async function toggleArchive(product: Product): Promise<void> {
    if (!organizationId) return;
    setRowError(null);
    try {
      const updated = product.archived_at
        ? await restoreProduct(organizationId, product.id)
        : await archiveProduct(organizationId, product.id);
      setProducts((current) => ({
        ...current,
        data: current.data.map((item) => (item.id === updated.id ? updated : item)),
      }));
    } catch {
      setRowError('Unable to change the product. Please try again.');
    }
  }

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to manage its catalogue.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Catalogue</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Products, units, and categories for {activeOrganization.name}.
        </p>
      </header>

      <section aria-label="Products" className="space-y-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1">
            <Label htmlFor="product-search">Search</Label>
            <Input
              id="product-search"
              value={search}
              onChange={(event) => {
                setPage(1);
                setLoading(true);
                setSearch(event.target.value);
              }}
              placeholder="Name or SKU"
              maxLength={255}
            />
          </div>
          <div className="space-y-1">
            <Label htmlFor="product-type-filter">Type</Label>
            <select
              id="product-type-filter"
              value={typeFilter}
              onChange={(event) => {
                setPage(1);
                setLoading(true);
                setTypeFilter(event.target.value as ProductType | '');
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All types</option>
              <option value="stocked">Stocked</option>
              <option value="non_stocked">Non-stocked</option>
              <option value="service">Service</option>
            </select>
          </div>
          <div className="space-y-1">
            <Label htmlFor="product-archived-filter">State</Label>
            <select
              id="product-archived-filter"
              value={archivedFilter}
              onChange={(event) => {
                setPage(1);
                setLoading(true);
                setArchivedFilter(event.target.value as ArchivedFilter);
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="false">Active</option>
              <option value="true">Archived</option>
              <option value="all">All</option>
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
            Loading products…
          </p>
        ) : products.data.length === 0 ? (
          <p className="text-sm text-on-surface-variant">No products match these filters.</p>
        ) : (
          <ul className="space-y-2">
            {products.data.map((product) => (
              <li
                key={product.id}
                className="flex flex-col gap-3 rounded-2xl bg-surface p-4 sm:flex-row sm:items-center sm:justify-between"
              >
                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <p className="truncate font-medium text-on-surface">{product.name}</p>
                    {product.type === 'stocked' && <Badge>Stocked</Badge>}
                    {product.archived_at && (
                      <Badge tone="warning" className="capitalize">
                        Archived
                      </Badge>
                    )}
                  </div>
                  <p className="mt-0.5 truncate text-sm text-on-surface-variant">
                    {product.sku ? `${product.sku} · ` : ''}
                    {formatDecimalString(product.price)} {activeOrganization.currency_code}
                    {product.unit_of_measure ? ` · ${product.unit_of_measure.code}` : ''}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Button
                    variant="ghost"
                    onClick={() => setEditingProduct(product)}
                  >
                    Edit
                  </Button>
                  <Button
                    variant="ghost"
                    onClick={() => void toggleArchive(product)}
                  >
                    {product.archived_at ? 'Restore' : 'Archive'}
                  </Button>
                </div>
              </li>
            ))}
          </ul>
        )}

        <Pagination
          currentPage={products.current_page}
          lastPage={products.last_page}
          disabled={loading}
          onPageChange={(target) => {
            setLoading(true);
            setPage(target);
          }}
        />
      </section>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardTitle>{editingProduct ? 'Edit product' : 'Create a product'}</CardTitle>
          <div className="mt-4">
            <ProductForm
              organizationId={activeOrganization.id}
              units={units}
              categories={categories}
              product={editingProduct ?? undefined}
              onCreated={() => setReload((current) => current + 1)}
              onUpdated={(updated) => {
                setProducts((current) => ({
                  ...current,
                  data: current.data.map((item) =>
                    item.id === updated.id ? updated : item,
                  ),
                }));
                setEditingProduct(null);
              }}
              onCancelEdit={() => setEditingProduct(null)}
            />
          </div>
        </Card>

        <div className="space-y-4">
          <Card>
            <CardTitle>Units of measure</CardTitle>
            {units.length > 0 && (
              <p className="mt-2 text-sm text-on-surface-variant">
                {units.map((unit) => unit.code).join(', ')}
              </p>
            )}
            <div className="mt-4">
              <UnitOfMeasureForm
                organizationId={activeOrganization.id}
                onCreated={(unit) => setUnits((current) => [...current, unit])}
              />
            </div>
          </Card>

          <Card>
            <CardTitle>{editingCategory ? 'Edit category' : 'Categories'}</CardTitle>
            {categories.length > 0 && !editingCategory && (
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
                    <Button variant="ghost" onClick={() => setEditingCategory(category)}>
                      Edit
                    </Button>
                  </li>
                ))}
              </ul>
            )}
            <div className="mt-4">
              <CategoryForm
                organizationId={activeOrganization.id}
                category={editingCategory ?? undefined}
                onCreated={(category) => setCategories((current) => [...current, category])}
                onUpdated={(updated) => {
                  setCategories((current) =>
                    current.map((item) => (item.id === updated.id ? updated : item)),
                  );
                  setEditingCategory(null);
                }}
                onCancelEdit={() => setEditingCategory(null)}
              />
            </div>
          </Card>
        </div>
      </div>

      <p className="text-sm text-on-surface-variant">
        Stocked products appear in{' '}
        <Link href="/inventory" className="font-medium text-primary hover:underline">
          inventory
        </Link>{' '}
        once stock is recorded.
      </p>
    </div>
  );
}
