'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import {
  Building2,
  Circle,
  ReceiptText,
  ShoppingBag,
  TrendingUp,
  Truck,
  UserRound,
  Users,
  Warehouse,
  type LucideIcon,
} from 'lucide-react';
import { getDashboardSummary } from '@/features/dashboard/service';
import { listInvoices } from '@/features/invoicing/service';
import { listGoodsReceipts } from '@/features/purchasing/service';
import { useSession } from '@/features/auth/session-context';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Card, CardTitle } from '@/components/ui/card';
import { BarChart } from '@/components/charts/bar-chart';
import { DonutChart, type DonutSegment } from '@/components/charts/donut-chart';
import { formatMoney } from '@/utils/money';
import { formatDecimalString } from '@/utils/decimal';
import type { DashboardSummary, Invoice, GoodsReceipt } from '@/types';

interface SummaryCardProps {
  label: string;
  value: string;
  hint?: string;
  icon: LucideIcon;
  href?: string;
}

function SummaryCard({ label, value, hint, icon: Icon, href }: SummaryCardProps) {
  const body = (
    <>
      <div className="flex items-center gap-3">
        <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
          <Icon className="h-5 w-5" aria-hidden="true" />
        </span>
        <CardTitle>{label}</CardTitle>
      </div>
      <p className="mt-3 text-2xl font-semibold tracking-tight text-on-surface">{value}</p>
      {hint && <p className="mt-1 text-sm text-on-surface-variant">{hint}</p>}
    </>
  );

  if (href) {
    return (
      <Link
        href={href}
        className="block rounded-2xl bg-surface p-5 transition-colors hover:bg-surface-container-high"
      >
        {body}
      </Link>
    );
  }

  return <div className="rounded-2xl bg-surface p-5">{body}</div>;
}

function monthLabel(month: string): string {
  // "2026-11" renders as "Nov".
  const date = new Date(`${month}-01T00:00:00Z`);
  return date.toLocaleString('en', { month: 'short', timeZone: 'UTC' });
}

const STATUS_TONES: Record<string, BadgeTone> = {
  draft: 'neutral',
  issued: 'success',
  voided: 'warning',
};

/**
 * The data-driven part of the dashboard. Mounted with the organization as
 * its key, so switching organizations remounts the component with fresh
 * state instead of resetting state inside effects.
 */
function DashboardContent({
  organizationId,
  currency,
}: {
  organizationId: string;
  currency: string;
}) {
  const [summary, setSummary] = useState<DashboardSummary | null>(null);
  const [summaryLoading, setSummaryLoading] = useState(true);
  const [summaryError, setSummaryError] = useState<string | null>(null);
  const [recentInvoices, setRecentInvoices] = useState<Invoice[]>([]);
  const [recentReceipts, setRecentReceipts] = useState<GoodsReceipt[]>([]);
  const [recentLoading, setRecentLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const result = await getDashboardSummary(organizationId);
        if (!cancelled) setSummary(result);
      } catch {
        if (!cancelled) setSummaryError('Unable to load your dashboard numbers. Please try again.');
      } finally {
        if (!cancelled) setSummaryLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const [invoicePage, receiptPage] = await Promise.all([
          listInvoices(organizationId, { perPage: 5 }),
          listGoodsReceipts(organizationId, { perPage: 5 }),
        ]);
        if (!cancelled) {
          setRecentInvoices(invoicePage.data);
          setRecentReceipts(receiptPage.data);
        }
      } catch {
        // Recent activity stays empty when the lists fail; the summary still shows.
      } finally {
        if (!cancelled) setRecentLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId]);

  const statusSegments: DonutSegment[] = summary
    ? [
        {
          label: 'Draft',
          value: summary.invoices_by_status.draft,
          strokeClass: 'stroke-on-surface-variant',
          swatchClass: 'bg-on-surface-variant',
        },
        {
          label: 'Issued',
          value: summary.invoices_by_status.issued,
          strokeClass: 'stroke-primary',
          swatchClass: 'bg-primary',
        },
        {
          label: 'Voided',
          value: summary.invoices_by_status.voided,
          strokeClass: 'stroke-warning',
          swatchClass: 'bg-warning',
        },
      ]
    : [];

  return (
    <div className="space-y-6">
      {summaryError ? (
        <section aria-label="Dashboard summary">
          <p role="alert" className="text-sm text-red-600">
            {summaryError}
          </p>
        </section>
      ) : summaryLoading || !summary ? (
        <section aria-label="Dashboard summary">
          <p className="text-sm text-on-surface-variant" aria-live="polite">
            Loading summary…
          </p>
        </section>
      ) : (
        <>
          <section aria-label="Summary cards" className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div className="rounded-2xl bg-primary-container p-5">
              <div className="flex items-center gap-3">
                <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-primary text-on-primary">
                  <TrendingUp className="h-5 w-5" aria-hidden="true" />
                </span>
                <CardTitle>Amount made</CardTitle>
              </div>
              <p className="mt-3 text-3xl font-semibold tracking-tight text-on-primary-container">
                {formatMoney(summary.invoiced_total, currency)}
              </p>
              <p className="mt-1 text-sm text-on-primary-container/80">
                From {summary.issued_invoices} issued{' '}
                {summary.issued_invoices === 1 ? 'invoice' : 'invoices'}
              </p>
            </div>
            <SummaryCard
              label="Total sales"
              value={String(summary.issued_invoices)}
              hint="Issued invoices"
              icon={ReceiptText}
              href="/invoicing"
            />
            <SummaryCard
              label="Customers"
              value={String(summary.customers)}
              hint="Active customer records"
              icon={Users}
              href="/invoicing/customers"
            />
            <SummaryCard
              label="Suppliers"
              value={String(summary.suppliers)}
              hint="Active supplier records"
              icon={Truck}
              href="/purchasing/suppliers"
            />
            <SummaryCard
              label="Purchases"
              value={formatMoney(summary.purchases_total, currency)}
              hint="Goods received into stock"
              icon={ShoppingBag}
              href="/purchasing"
            />
            <SummaryCard
              label="Stock on hand"
              value={formatDecimalString(summary.stock_units)}
              hint={`Across ${summary.products_in_stock} ${summary.products_in_stock === 1 ? 'product' : 'products'} (${summary.products} in catalogue)`}
              icon={Warehouse}
              href="/inventory"
            />
          </section>

          <div className="grid gap-4 lg:grid-cols-2">
            <Card>
              <CardTitle>Revenue by month</CardTitle>
              <p className="mt-1 text-sm text-on-surface-variant">
                Issued invoices, last 6 months.
              </p>
              <div className="mt-4">
                <BarChart
                  ariaLabel="Issued invoice totals by month"
                  points={summary.revenue_by_month.map((point) => ({
                    label: monthLabel(point.month),
                    value: Number(point.total),
                  }))}
                  formatValue={(value) => formatMoney(String(value), currency)}
                />
              </div>
            </Card>

            <Card>
              <CardTitle>Invoices by status</CardTitle>
              <p className="mt-1 text-sm text-on-surface-variant">
                Every invoice in the workspace.
              </p>
              <div className="mt-4">
                <DonutChart ariaLabel="Invoice counts by status" segments={statusSegments} />
              </div>
            </Card>
          </div>

          <section aria-label="Recent activity" className="grid gap-4 lg:grid-cols-2">
            <Card>
              <CardTitle>Latest invoices</CardTitle>
              {recentLoading ? (
                <p className="mt-2 text-sm text-on-surface-variant" aria-live="polite">
                  Loading invoices…
                </p>
              ) : recentInvoices.length === 0 ? (
                <p className="mt-2 text-sm text-on-surface-variant">
                  No invoices yet.{' '}
                  <Link href="/invoicing" className="font-medium text-primary hover:underline">
                    Create the first one
                  </Link>
                  .
                </p>
              ) : (
                <ul className="mt-4 space-y-2">
                  {recentInvoices.map((invoice) => (
                    <li
                      key={invoice.id}
                      className="flex items-center justify-between gap-3 rounded-xl bg-surface-container-high px-3 py-2"
                    >
                      <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-on-surface">
                          {invoice.document_number ?? 'Draft'}
                        </p>
                        <p className="truncate text-xs text-on-surface-variant">
                          {invoice.customer_name ?? 'No customer'}
                        </p>
                      </div>
                      <div className="flex shrink-0 items-center gap-2">
                        <Badge tone={STATUS_TONES[invoice.status] ?? 'neutral'}>
                          {invoice.status}
                        </Badge>
                        <p className="font-mono text-sm text-on-surface">
                          {formatMoney(invoice.grand_total, invoice.currency)}
                        </p>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </Card>

            <Card>
              <CardTitle>Latest goods receipts</CardTitle>
              {recentLoading ? (
                <p className="mt-2 text-sm text-on-surface-variant" aria-live="polite">
                  Loading receipts…
                </p>
              ) : recentReceipts.length === 0 ? (
                <p className="mt-2 text-sm text-on-surface-variant">
                  Nothing received yet.{' '}
                  <Link href="/purchasing" className="font-medium text-primary hover:underline">
                    Record a receipt
                  </Link>
                  .
                </p>
              ) : (
                <ul className="mt-4 space-y-2">
                  {recentReceipts.map((receipt) => (
                    <li
                      key={receipt.id}
                      className="flex items-center justify-between gap-3 rounded-xl bg-surface-container-high px-3 py-2"
                    >
                      <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-on-surface">
                          {receipt.document_number}
                        </p>
                        <p className="truncate text-xs text-on-surface-variant">
                          {receipt.supplier_name ?? 'No supplier'} · {receipt.received_on}
                        </p>
                      </div>
                      <p className="shrink-0 font-mono text-sm text-on-surface">
                        {formatMoney(receipt.total_cost, currency)}
                      </p>
                    </li>
                  ))}
                </ul>
              )}
            </Card>
          </section>
        </>
      )}
    </div>
  );
}

export default function DashboardPage() {
  const { user } = useSession();
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;
  const currency = activeOrganization?.currency_code ?? 'NGN';

  const emailVerified = Boolean(user?.email_verified_at);
  const hasOrganization = Boolean(activeOrganization);
  const setupPending: Array<{ label: string; hint: string; done: boolean; href?: string; actionLabel?: string }> = [
    {
      label: 'Create your organization',
      hint: 'Set up the workspace your records will live in.',
      done: hasOrganization,
      href: '/organizations',
      actionLabel: 'Go to organizations',
    },
    {
      label: 'Verify your email address',
      hint: 'Check your inbox for the verification link.',
      done: emailVerified,
    },
  ];
  const pendingSetup = setupPending.filter((item) => !item.done);

  const firstName = user?.name.split(' ')[0]?.trim() ?? '';
  const greeting = firstName ? `Welcome back, ${firstName}` : 'Welcome back';

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-[2rem] font-semibold leading-tight tracking-[-0.02em] text-on-surface">
          {greeting}
        </h1>
        <p className="mt-1 text-base text-on-surface-variant">
          {activeOrganization
            ? `Here is how ${activeOrganization.name} is doing.`
            : 'No organization selected yet.'}
        </p>
      </header>

      {pendingSetup.length > 0 && (
        <section aria-label="Setup checklist" className="rounded-2xl bg-primary-container p-6">
          <h2 className="text-base font-semibold text-on-primary-container">
            Finish setting up
          </h2>
          <p className="mt-1 text-base text-on-primary-container/80">
            A few steps before your workspace is ready.
          </p>
          <ul className="mt-4 space-y-3">
            {pendingSetup.map((item) => (
              <li
                key={item.label}
                className="flex flex-col gap-3 rounded-2xl bg-surface p-4 sm:flex-row sm:items-center sm:justify-between"
              >
                <div className="flex items-center gap-3">
                  <Circle className="h-5 w-5 shrink-0 text-on-surface-variant/40" aria-hidden="true" />
                  <div>
                    <p className="text-base font-medium text-on-surface">{item.label}</p>
                    <p className="text-sm text-on-surface-variant">{item.hint}</p>
                  </div>
                </div>
                {item.href && item.actionLabel && (
                  <Link
                    href={item.href}
                    className="inline-flex h-9 shrink-0 items-center justify-center rounded-full bg-primary px-4 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90"
                  >
                    {item.actionLabel}
                  </Link>
                )}
              </li>
            ))}
          </ul>
        </section>
      )}

      {hasOrganization && organizationId ? (
        <DashboardContent key={organizationId} organizationId={organizationId} currency={currency} />
      ) : (
        <section aria-label="Dashboard summary">
          <p className="text-base text-on-surface-variant">
            Select an organization to see your summary.
          </p>
        </section>
      )}

      <div className="grid gap-4 sm:grid-cols-2">
        <Card>
          <div className="flex items-center gap-3">
            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
              <Building2 className="h-5 w-5" aria-hidden="true" />
            </span>
            <CardTitle>Organization</CardTitle>
            {activeOrganization && (
              <Badge
                tone={activeOrganization.status === 'active' ? 'success' : 'warning'}
                className="ml-auto capitalize"
              >
                {activeOrganization.status}
              </Badge>
            )}
          </div>
          {activeOrganization && (
            <dl className="mt-4 space-y-2 text-base">
              <div className="flex items-center justify-between gap-4">
                <dt className="text-on-surface-variant">Name</dt>
                <dd className="truncate font-medium text-on-surface">{activeOrganization.name}</dd>
              </div>
              <div className="flex items-center justify-between gap-4">
                <dt className="text-on-surface-variant">Currency</dt>
                <dd className="truncate font-medium text-on-surface">
                  {activeOrganization.currency_code}
                </dd>
              </div>
            </dl>
          )}
        </Card>

        <Card>
          <div className="flex items-center gap-3">
            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
              <UserRound className="h-5 w-5" aria-hidden="true" />
            </span>
            <CardTitle>Account</CardTitle>
            {user && (
              <Badge tone={emailVerified ? 'success' : 'warning'} className="ml-auto">
                {emailVerified ? 'Email verified' : 'Email unverified'}
              </Badge>
            )}
          </div>
          {user && (
            <dl className="mt-4 space-y-2 text-base">
              <div className="flex items-center justify-between gap-4">
                <dt className="text-on-surface-variant">Email</dt>
                <dd className="truncate font-medium text-on-surface">{user.email}</dd>
              </div>
              <div className="flex items-center justify-between gap-4">
                <dt className="text-on-surface-variant">Status</dt>
                <dd className="truncate font-medium text-on-surface capitalize">{user.status}</dd>
              </div>
            </dl>
          )}
        </Card>
      </div>
    </div>
  );
}
