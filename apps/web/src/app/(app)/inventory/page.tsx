'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import {
  archiveInventoryLocation,
  listInventoryLocations,
  listStockBalances,
} from '@/features/inventory/service';
import { LocationForm } from '@/features/inventory/location-form';
import { listBranches } from '@/features/organizations/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { formatDecimalString } from '@/utils/decimal';
import type { Branch, InventoryLocation, PaginatedResponse, StockBalance } from '@/types';

const EMPTY_BALANCES: PaginatedResponse<StockBalance> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 100,
  total: 0,
};

const EMPTY_LOCATIONS: PaginatedResponse<InventoryLocation> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 100,
  total: 0,
};

export default function InventoryPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [balances, setBalances] = useState<PaginatedResponse<StockBalance>>(EMPTY_BALANCES);
  const [locations, setLocations] = useState<PaginatedResponse<InventoryLocation>>(EMPTY_LOCATIONS);
  const [branches, setBranches] = useState<Branch[]>([]);
  const [locationFilter, setLocationFilter] = useState('');
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [rowError, setRowError] = useState<string | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const [locationPage, branchPage] = await Promise.all([
          listInventoryLocations(organizationId, { perPage: 100 }),
          listBranches(organizationId),
        ]);
        if (!cancelled) {
          setLocations(locationPage);
          setBranches(branchPage.data);
        }
      } catch {
        // The balances list remains usable when locations fail to load.
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
        const result = await listStockBalances(organizationId, {
          locationId: locationFilter || undefined,
        });
        if (!cancelled) setBalances(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load stock balances. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId, locationFilter]);

  async function archiveLocation(location: InventoryLocation): Promise<void> {
    if (!organizationId) return;
    setRowError(null);
    try {
      const updated = await archiveInventoryLocation(organizationId, location.id);
      setLocations((current) => ({
        ...current,
        data: current.data.map((item) => (item.id === updated.id ? updated : item)),
      }));
    } catch {
      setRowError('Unable to archive the location. Please try again.');
    }
  }

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to view its inventory.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Inventory</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Stock on hand across {activeOrganization.name}. Balances are derived
          from recorded movements.
        </p>
      </header>

      <section aria-label="Stock balances" className="space-y-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1">
            <Label htmlFor="balance-location-filter">Location</Label>
            <select
              id="balance-location-filter"
              value={locationFilter}
              onChange={(event) => {
                setLoading(true);
                setLocationFilter(event.target.value);
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All locations</option>
              {locations.data
                .filter((location) => !location.archived_at)
                .map((location) => (
                  <option key={location.id} value={location.id}>
                    {location.name}
                  </option>
                ))}
            </select>
          </div>
          <Link
            href="/inventory/movements"
            className="inline-flex h-10 items-center rounded-full bg-primary px-4 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90"
          >
            Record stock activity
          </Link>
        </div>

        {loadError && (
          <p role="alert" className="text-sm text-red-600">
            {loadError}
          </p>
        )}

        {loading ? (
          <p className="text-sm text-on-surface-variant" aria-live="polite">
            Loading balances…
          </p>
        ) : balances.data.length === 0 ? (
          <p className="text-sm text-on-surface-variant">
            No stock recorded yet. Record a receipt or opening movement to start.
          </p>
        ) : (
          <div className="overflow-x-auto rounded-2xl bg-surface">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-gray-200 text-on-surface-variant">
                  <th scope="col" className="px-4 py-3 font-medium">Product</th>
                  <th scope="col" className="px-4 py-3 font-medium">Location</th>
                  <th scope="col" className="px-4 py-3 font-medium text-right">Quantity</th>
                </tr>
              </thead>
              <tbody>
                {balances.data.map((balance) => (
                  <tr key={balance.id} className="border-b border-gray-100 last:border-0">
                    <td className="px-4 py-3 font-medium text-on-surface">
                      {balance.product?.name ?? balance.product_id}
                    </td>
                    <td className="px-4 py-3 text-on-surface-variant">
                      {balance.location?.name ?? balance.location_id}
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-on-surface">
                      {formatDecimalString(balance.quantity)}
                      {balance.product?.unit_of_measure
                        ? ` ${balance.product.unit_of_measure.code}`
                        : ''}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardTitle>Locations</CardTitle>
          {rowError && (
            <p role="alert" className="mt-2 text-sm text-red-600">
              {rowError}
            </p>
          )}
          {locations.data.length === 0 ? (
            <p className="mt-2 text-sm text-on-surface-variant">
              No locations yet. Create the first one to hold stock.
            </p>
          ) : (
            <ul className="mt-4 space-y-2">
              {locations.data.map((location) => (
                <li
                  key={location.id}
                  className="flex items-center justify-between gap-3 rounded-xl bg-surface-container-high px-3 py-2"
                >
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-on-surface">
                      {location.name}
                    </p>
                    <p className="text-xs text-on-surface-variant">
                      {location.code}
                      {location.branch_id &&
                        branches.find((branch) => branch.id === location.branch_id)
                        ? ` · ${branches.find((branch) => branch.id === location.branch_id)?.name}`
                        : ''}
                    </p>
                  </div>
                  {location.archived_at ? (
                    <Badge tone="warning">Archived</Badge>
                  ) : (
                    <Button variant="ghost" onClick={() => void archiveLocation(location)}>
                      Archive
                    </Button>
                  )}
                </li>
              ))}
            </ul>
          )}
        </Card>

        <Card>
          <CardTitle>Create a location</CardTitle>
          <div className="mt-4">
            <LocationForm
              organizationId={activeOrganization.id}
              branches={branches}
              onCreated={(location) =>
                setLocations((current) => ({
                  ...current,
                  data: [...current.data, location],
                  total: current.total + 1,
                }))
              }
            />
          </div>
        </Card>
      </div>
    </div>
  );
}
