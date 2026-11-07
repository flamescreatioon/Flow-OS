'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { RecordReceiptForm } from '@/features/purchasing/record-receipt-form';
import {
  listGoodsReceipts,
  listSuppliers,
} from '@/features/purchasing/service';
import { listProducts } from '@/features/catalogue/service';
import { listInventoryLocations } from '@/features/inventory/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { Card, CardTitle } from '@/components/ui/card';
import type {
  GoodsReceipt,
  InventoryLocation,
  PaginatedResponse,
  Product,
  Supplier,
} from '@/types';

export default function NewReceiptPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [products, setProducts] = useState<Product[]>([]);
  const [locations, setLocations] = useState<InventoryLocation[]>([]);
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [recorded, setRecorded] = useState<GoodsReceipt | null>(null);
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
        if (!cancelled) setLoadError('Unable to load products, locations, and suppliers. Please try again.');
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
        Select an organization to record purchases.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Record a goods receipt</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Each line moves stock into the location and carries its cost. Receipts
          are finalized on creation.
        </p>
      </header>

      {recorded && (
        <div className="rounded-2xl bg-primary-container p-4" aria-live="polite">
          <p className="text-sm font-medium text-on-primary-container">
            {recorded.document_number} was recorded — stock has moved.
          </p>
          <div className="mt-2 flex flex-wrap gap-2">
            <Link
              href={`/purchasing/receipts/${recorded.id}`}
              className="inline-flex h-9 items-center rounded-full bg-primary px-4 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90"
            >
              View receipt
            </Link>
            <Link
              href="/purchasing"
              className="inline-flex h-9 items-center rounded-full bg-on-primary-container/10 px-4 text-sm font-medium text-on-primary-container transition-colors hover:opacity-90"
            >
              Back to purchasing
            </Link>
          </div>
        </div>
      )}

      {loadError && (
        <p role="alert" className="text-sm text-red-600">
          {loadError}
        </p>
      )}

      {loading ? (
        <p className="text-sm text-on-surface-variant" aria-live="polite">
          Loading products, locations, and suppliers…
        </p>
      ) : (
        <Card>
          <CardTitle>Goods receipt</CardTitle>
          <div className="mt-4">
            <RecordReceiptForm
              organizationId={activeOrganization.id}
              products={products.filter((product) => product.type === 'stocked')}
              locations={locations.filter((location) => !location.archived_at)}
              suppliers={suppliers.filter((supplier) => !supplier.archived_at)}
              onRecorded={(receipt) => setRecorded(receipt)}
            />
          </div>
        </Card>
      )}

      <p className="text-sm text-on-surface-variant">
        <Link href="/purchasing" className="font-medium text-primary hover:underline">
          Back to purchasing
        </Link>
      </p>
    </div>
  );
}
