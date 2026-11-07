'use client';

import { Suspense, useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import { useParams, useSearchParams } from 'next/navigation';
import { Printer } from 'lucide-react';
import { getGoodsReceipt } from '@/features/purchasing/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDecimalString } from '@/utils/decimal';
import type { GoodsReceipt } from '@/types';

function ReceiptDocument({ receipt }: { receipt: GoodsReceipt }) {
  return (
    <div className="print-document space-y-6">
      <header className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold text-on-surface">
            Goods receipt {receipt.document_number}
          </h1>
          <p className="mt-1 text-sm text-on-surface-variant">
            Received on {receipt.received_on} ·{' '}
            <span className="capitalize">{receipt.status}</span>
          </p>
        </div>
        <Badge tone="success" className="capitalize print:hidden">
          {receipt.status}
        </Badge>
      </header>

      <dl className="grid gap-4 rounded-2xl bg-surface p-4 text-sm sm:grid-cols-2">
        <div>
          <dt className="text-on-surface-variant">Supplier</dt>
          <dd className="mt-0.5 font-medium text-on-surface">
            {receipt.supplier_name ?? 'No supplier (one-off purchase)'}
          </dd>
        </div>
        <div>
          <dt className="text-on-surface-variant">Location</dt>
          <dd className="mt-0.5 font-medium text-on-surface">
            {receipt.location?.name ?? receipt.location_id}
            {receipt.location ? ` (${receipt.location.code})` : ''}
          </dd>
        </div>
      </dl>

      <div className="overflow-x-auto rounded-2xl bg-surface">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-gray-200 text-on-surface-variant">
              <th scope="col" className="px-4 py-3 font-medium">Product</th>
              <th scope="col" className="px-4 py-3 font-medium text-right">Quantity</th>
              <th scope="col" className="px-4 py-3 font-medium text-right">Unit cost</th>
              <th scope="col" className="px-4 py-3 font-medium text-right">Line total</th>
            </tr>
          </thead>
          <tbody>
            {receipt.lines.map((line) => (
              <tr key={line.id} className="border-b border-gray-100 last:border-0">
                <td className="px-4 py-3 font-medium text-on-surface">
                  {line.product?.name ?? line.product_id}
                </td>
                <td className="px-4 py-3 text-right font-mono text-on-surface">
                  {formatDecimalString(line.quantity)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-on-surface">
                  {formatDecimalString(line.unit_cost)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-on-surface">
                  {formatDecimalString(line.line_total)}
                </td>
              </tr>
            ))}
          </tbody>
          <tfoot>
            <tr className="border-t border-gray-200">
              <td colSpan={3} className="px-4 py-3 text-right font-medium text-on-surface-variant">
                Total cost
              </td>
              <td className="px-4 py-3 text-right font-mono font-semibold text-on-surface">
                {formatDecimalString(receipt.total_cost)}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      {receipt.note && (
        <p className="rounded-2xl bg-surface p-4 text-sm text-on-surface-variant">
          <span className="font-medium text-on-surface">Note: </span>
          {receipt.note}
        </p>
      )}

      <p className="text-xs text-on-surface-variant">
        This document is a permanent record: it has already moved stock at these
        costs and cannot be edited or deleted.
      </p>
    </div>
  );
}

function ReceiptDetailContent() {
  const params = useParams<{ id: string }>();
  const receiptId = params?.id;
  const searchParams = useSearchParams();
  const autoPrint = searchParams?.get('print') === '1';

  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [receipt, setReceipt] = useState<GoodsReceipt | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  const autoPrintFired = useRef(false);

  useEffect(() => {
    if (!organizationId || !receiptId) return;
    let cancelled = false;
    (async () => {
      try {
        const result = await getGoodsReceipt(organizationId, receiptId);
        if (!cancelled) setReceipt(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load the receipt. It may belong to another organization.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId, receiptId]);

  useEffect(() => {
    if (!autoPrint || !receipt || autoPrintFired.current) return;
    autoPrintFired.current = true;
    window.print();
  }, [autoPrint, receipt]);

  if (!activeOrganization) {
    return <p className="text-sm text-on-surface-variant">Select an organization first.</p>;
  }

  if (loadError) {
    return (
      <p role="alert" className="text-sm text-red-600">
        {loadError}
      </p>
    );
  }

  if (loading || !receipt) {
    return (
      <p className="text-sm text-on-surface-variant" aria-live="polite">
        Loading receipt…
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3 print:hidden">
        <Link
          href="/purchasing"
          className="inline-flex h-9 items-center rounded-full px-3 text-sm font-medium text-on-surface-variant transition-colors hover:bg-secondary-container"
        >
          ← Back to purchasing
        </Link>
        <Button onClick={() => window.print()}>
          <Printer className="mr-2 h-4 w-4" aria-hidden="true" />
          Print
        </Button>
      </div>

      <ReceiptDocument receipt={receipt} />
    </div>
  );
}

export default function ReceiptDetailPage() {
  return (
    <Suspense
      fallback={
        <p className="text-sm text-on-surface-variant" aria-live="polite">
          Loading receipt…
        </p>
      }
    >
      <ReceiptDetailContent />
    </Suspense>
  );
}
