'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import {
  listInventoryLocations,
  listStockMovements,
} from '@/features/inventory/service';
import { RecordCountForm } from '@/features/inventory/record-count-form';
import { RecordMovementForm } from '@/features/inventory/record-movement-form';
import { RecordTransferForm } from '@/features/inventory/record-transfer-form';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { listProducts } from '@/features/catalogue/service';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Card, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { config } from '@/config';
import { formatDecimalString } from '@/utils/decimal';
import type {
  InventoryLocation,
  PaginatedResponse,
  Product,
  StockMovement,
  StockMovementType,
} from '@/types';

const EMPTY_MOVEMENTS: PaginatedResponse<StockMovement> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
};

const MOVEMENT_TYPES: StockMovementType[] = [
  'opening',
  'receipt',
  'sale',
  'return',
  'adjustment',
  'transfer_in',
  'transfer_out',
  'count',
];

const typeTone: Partial<Record<StockMovementType, BadgeTone>> = {
  receipt: 'success',
  opening: 'success',
  return: 'success',
  transfer_in: 'success',
  sale: 'danger',
  transfer_out: 'danger',
  adjustment: 'primary',
  count: 'neutral',
};

function formatOccurredAt(value: string): string {
  return new Intl.DateTimeFormat(config.date.locale, {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: config.date.timezone,
  }).format(new Date(value));
}

export default function MovementsPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [movements, setMovements] = useState<PaginatedResponse<StockMovement>>(EMPTY_MOVEMENTS);
  const [products, setProducts] = useState<Product[]>([]);
  const [locations, setLocations] = useState<InventoryLocation[]>([]);
  const [page, setPage] = useState(1);
  const [productFilter, setProductFilter] = useState('');
  const [locationFilter, setLocationFilter] = useState('');
  const [typeFilter, setTypeFilter] = useState('' as StockMovementType | '');
  const [reload, setReload] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const [productPage, locationPage] = await Promise.all([
          listProducts(organizationId, { type: 'stocked', perPage: 100 }),
          listInventoryLocations(organizationId, { perPage: 100 }),
        ]);
        if (!cancelled) {
          setProducts(productPage.data.filter((product) => !product.archived_at));
          setLocations(locationPage.data.filter((location) => !location.archived_at));
        }
      } catch {
        // The movement list remains usable when reference data fails to load.
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
        const result = await listStockMovements(organizationId, {
          productId: productFilter || undefined,
          locationId: locationFilter || undefined,
          type: typeFilter || undefined,
          page,
          perPage: 25,
        });
        if (!cancelled) setMovements(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load stock movements. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId, productFilter, locationFilter, typeFilter, page, reload]);

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to record stock activity.
      </p>
    );
  }

  const productName = (productId: string): string =>
    products.find((product) => product.id === productId)?.name ?? 'Product';
  const locationName = (locationId: string): string =>
    locations.find((location) => location.id === locationId)?.name ?? 'Location';

  const refreshAfterRecord = (): void => {
    setLoading(true);
    setPage(1);
    setReload((current) => current + 1);
  };

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Stock movements</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Every stock change is an immutable record. Mistakes are repaired with
          corrections, never edits.
        </p>
      </header>

      <section aria-label="Record stock activity" className="grid gap-4 lg:grid-cols-3">
        <Card>
          <CardTitle>Movement</CardTitle>
          <div className="mt-4">
            <RecordMovementForm
              organizationId={activeOrganization.id}
              products={products}
              locations={locations}
              onRecorded={refreshAfterRecord}
            />
          </div>
        </Card>
        <Card>
          <CardTitle>Count</CardTitle>
          <div className="mt-4">
            <RecordCountForm
              organizationId={activeOrganization.id}
              products={products}
              locations={locations}
              onRecorded={refreshAfterRecord}
            />
          </div>
        </Card>
        <Card>
          <CardTitle>Transfer</CardTitle>
          <div className="mt-4">
            <RecordTransferForm
              organizationId={activeOrganization.id}
              products={products}
              locations={locations}
              onRecorded={refreshAfterRecord}
            />
          </div>
        </Card>
      </section>

      <section aria-label="Movement history" className="space-y-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1">
            <Label htmlFor="movement-product-filter">Product</Label>
            <select
              id="movement-product-filter"
              value={productFilter}
              onChange={(event) => {
                setLoading(true);
                setPage(1);
                setProductFilter(event.target.value);
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All products</option>
              {products.map((product) => (
                <option key={product.id} value={product.id}>
                  {product.name}
                </option>
              ))}
            </select>
          </div>
          <div className="space-y-1">
            <Label htmlFor="movement-location-filter">Location</Label>
            <select
              id="movement-location-filter"
              value={locationFilter}
              onChange={(event) => {
                setLoading(true);
                setPage(1);
                setLocationFilter(event.target.value);
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All locations</option>
              {locations.map((location) => (
                <option key={location.id} value={location.id}>
                  {location.name}
                </option>
              ))}
            </select>
          </div>
          <div className="space-y-1">
            <Label htmlFor="movement-type-filter">Type</Label>
            <select
              id="movement-type-filter"
              value={typeFilter}
              onChange={(event) => {
                setLoading(true);
                setPage(1);
                setTypeFilter(event.target.value as StockMovementType | '');
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All types</option>
              {MOVEMENT_TYPES.map((type) => (
                <option key={type} value={type}>
                  {type.replace('_', ' ')}
                </option>
              ))}
            </select>
          </div>
          <Link
            href="/inventory"
            className="inline-flex h-10 items-center rounded-full bg-secondary-container px-4 text-sm font-medium text-on-secondary-container transition-colors hover:bg-secondary-container/70"
          >
            View balances
          </Link>
        </div>

        {loadError && (
          <p role="alert" className="text-sm text-red-600">
            {loadError}
          </p>
        )}

        {loading ? (
          <p className="text-sm text-on-surface-variant" aria-live="polite">
            Loading movements…
          </p>
        ) : movements.data.length === 0 ? (
          <p className="text-sm text-on-surface-variant">No movements match these filters.</p>
        ) : (
          <ul className="space-y-2">
            {movements.data.map((movement) => {
              const negative = movement.quantity.startsWith('-');
              return (
                <li
                  key={movement.id}
                  className="flex flex-col gap-2 rounded-2xl bg-surface p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <Badge tone={typeTone[movement.type] ?? 'neutral'} className="capitalize">
                        {movement.type.replace('_', ' ')}
                      </Badge>
                      <p className="truncate font-medium text-on-surface">
                        {productName(movement.product_id)}
                      </p>
                      <span className="text-sm text-on-surface-variant">
                        at {locationName(movement.location_id)}
                      </span>
                    </div>
                    <p className="mt-0.5 text-xs text-on-surface-variant">
                      {formatOccurredAt(movement.occurred_at)}
                      {movement.reference ? ` · ${movement.reference}` : ''}
                      {movement.corrects_movement_id ? ' · correction' : ''}
                      {movement.transfer_movement_id ? ' · transfer half' : ''}
                    </p>
                  </div>
                  <span
                    className={`font-mono text-sm ${negative ? 'text-red-600' : 'text-on-surface'}`}
                  >
                    {negative ? '' : '+'}
                    {formatDecimalString(movement.quantity)}
                  </span>
                </li>
              );
            })}
          </ul>
        )}

        <Pagination
          currentPage={movements.current_page}
          lastPage={movements.last_page}
          disabled={loading}
          onPageChange={(target) => {
            setLoading(true);
            setPage(target);
          }}
        />
      </section>
    </div>
  );
}
