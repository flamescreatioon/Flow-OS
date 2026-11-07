'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { listGoodsReceipts, listSuppliers } from '@/features/purchasing/service';
import { listProducts } from '@/features/catalogue/service';
import { listInventoryLocations } from '@/features/inventory/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { RecordReceiptForm } from '@/features/purchasing/record-receipt-form';
import { Badge } from '@/components/ui/badge';
import { Card, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { formatDecimalString } from '@/utils/decimal';
import type { GoodsReceipt, InventoryLocation, PaginatedResponse, Product, Supplier } from '@/types';

const EMPTY_RECEIPTS: PaginatedResponse<GoodsReceipt> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
};

export default function PurchasingPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [receipts, setReceipts] = useState<PaginatedResponse<GoodsReceipt>>(EMPTY_RECEIPTS);
  const [products, setProducts] = useState<Product[]>([]);
  const [locations, setLocations] = useState<InventoryLocation[]>([]);
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [supplierFilter, setSupplierFilter] = useState('');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const [productPage, locationPage, supplierPage] = await Promise.all([
          listProducts(organizationId, { perPage: 100 }),
          listInventoryLocations(organizationId, { perPage: 100 }),
          listSuppliers(organizationId, { perPage: 100 }),
        ]);
        if (!cancelled) {
          setProducts(productPage.data);
          setLocations(locationPage.data);
          setSuppliers(supplierPage.data);
        }
      } catch {
        // The receipts list still renders when reference data fails to load.
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
        const result = await listGoodsReceipts(organizationId, {
          supplierId: supplierFilter || undefined,
          page,
        });
        if (!cancelled) setReceipts(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load goods receipts. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId, supplierFilter, page]);

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to record purchases.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Purchasing</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Goods received into stock across {activeOrganization.name}. Each receipt
          records the cost and moves stock through inventory.
        </p>
      </header>

      <section aria-label="Goods receipts" className="space-y-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1">
            <Label htmlFor="receipt-supplier-filter">Supplier</Label>
            <select
              id="receipt-supplier-filter"
              value={supplierFilter}
              onChange={(event) => {
                setLoading(true);
                setPage(1);
                setSupplierFilter(event.target.value);
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All suppliers</option>
              {suppliers.map((supplier) => (
                <option key={supplier.id} value={supplier.id}>
                  {supplier.name}
                </option>
              ))}
            </select>
          </div>
          <Link
            href="/purchasing/suppliers"
            className="inline-flex h-10 items-center rounded-full bg-secondary-container px-4 text-sm font-medium text-on-secondary-container transition-colors hover:opacity-90"
          >
            Manage suppliers
          </Link>
        </div>

        {loadError && (
          <p role="alert" className="text-sm text-red-600">
            {loadError}
          </p>
        )}

        {loading ? (
          <p className="text-sm text-on-surface-variant" aria-live="polite">
            Loading receipts…
          </p>
        ) : receipts.data.length === 0 ? (
          <p className="text-sm text-on-surface-variant">
            No goods receipts yet. Record the first one to bring stock in.
          </p>
        ) : (
          <div className="overflow-x-auto rounded-2xl bg-surface">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-gray-200 text-on-surface-variant">
                  <th scope="col" className="px-4 py-3 font-medium">Receipt</th>
                  <th scope="col" className="px-4 py-3 font-medium">Supplier</th>
                  <th scope="col" className="px-4 py-3 font-medium">Received on</th>
                  <th scope="col" className="px-4 py-3 font-medium">Status</th>
                  <th scope="col" className="px-4 py-3 font-medium text-right">Total cost</th>
                </tr>
              </thead>
              <tbody>
                {receipts.data.map((receipt) => (
                  <tr key={receipt.id} className="border-b border-gray-100 last:border-0">
                    <td className="px-4 py-3 font-mono font-medium text-on-surface">
                      {receipt.document_number}
                    </td>
                    <td className="px-4 py-3 text-on-surface-variant">
                      {receipt.supplier_name ?? '—'}
                    </td>
                    <td className="px-4 py-3 text-on-surface-variant">{receipt.received_on}</td>
                    <td className="px-4 py-3">
                      <Badge tone="success">Recorded</Badge>
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-on-surface">
                      {formatDecimalString(receipt.total_cost)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <Pagination
          currentPage={receipts.current_page}
          lastPage={receipts.last_page}
          disabled={loading}
          onPageChange={(target) => {
            setLoading(true);
            setPage(target);
          }}
        />
      </section>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardTitle>Recent receipt lines</CardTitle>
          {receipts.data.length === 0 ? (
            <p className="mt-2 text-sm text-on-surface-variant">Nothing received yet.</p>
          ) : (
            <ul className="mt-4 space-y-2">
              {receipts.data
                .slice(0, 5)
                .flatMap((receipt) =>
                  receipt.lines.map((line) => (
                    <li
                      key={line.id}
                      className="flex items-center justify-between gap-3 rounded-xl bg-surface-container-high px-3 py-2"
                    >
                      <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-on-surface">
                          {line.product?.name ?? line.product_id}
                        </p>
                        <p className="text-xs text-on-surface-variant">
                          {receipt.document_number} · {formatDecimalString(line.quantity)} ×{' '}
                          {formatDecimalString(line.unit_cost)}
                        </p>
                      </div>
                      <p className="font-mono text-sm text-on-surface">
                        {formatDecimalString(line.line_total)}
                      </p>
                    </li>
                  )),
                )}
            </ul>
          )}
        </Card>

        <Card>
          <CardTitle>Record a goods receipt</CardTitle>
          <div className="mt-4">
            <RecordReceiptForm
              organizationId={activeOrganization.id}
              products={products.filter((product) => product.type === 'stocked')}
              locations={locations.filter((location) => !location.archived_at)}
              suppliers={suppliers.filter((supplier) => !supplier.archived_at)}
              onRecorded={(receipt) =>
                setReceipts((current) => ({
                  ...current,
                  data: [receipt, ...current.data],
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
