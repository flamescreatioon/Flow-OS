'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import {
  getTaxSettings,
  issueInvoice,
  listCustomers,
  listInvoices,
  sendInvoice,
  updateTaxSettings,
  voidInvoice,
  type UpdateTaxSettingsInput,
} from '@/features/invoicing/service';
import { listProducts } from '@/features/catalogue/service';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { InvoiceForm } from '@/features/invoicing/invoice-form';
import { isCanonicalDecimal } from '@/utils/decimal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { formatDecimalString } from '@/utils/decimal';
import type { Customer, Invoice, InvoiceStatus, PaginatedResponse, Product, TaxSettings } from '@/types';

const EMPTY_INVOICES: PaginatedResponse<Invoice> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
};

const STATUS_TONES: Record<InvoiceStatus, 'success' | 'neutral' | 'warning'> = {
  draft: 'neutral',
  issued: 'success',
  voided: 'warning',
};

export default function InvoicingPage() {
  const { activeOrganization } = useActiveOrganization();
  const organizationId = activeOrganization?.id;

  const [invoices, setInvoices] = useState<PaginatedResponse<Invoice>>(EMPTY_INVOICES);
  const [products, setProducts] = useState<Product[]>([]);
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [taxSettings, setTaxSettings] = useState<TaxSettings | null>(null);
  const [taxRate, setTaxRate] = useState('');
  const [taxMode, setTaxMode] = useState<'exclusive' | 'inclusive'>('exclusive');
  const [taxMessage, setTaxMessage] = useState<string | null>(null);
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [rowError, setRowError] = useState<string | null>(null);
  const [rowBusy, setRowBusy] = useState<string | null>(null);

  useEffect(() => {
    if (!organizationId) return;
    let cancelled = false;
    (async () => {
      try {
        const [productPage, customerPage] = await Promise.all([
          listProducts(organizationId, { perPage: 100 }),
          listCustomers(organizationId, { perPage: 100 }),
        ]);
        if (!cancelled) {
          setProducts(productPage.data);
          setCustomers(customerPage.data);
        }
      } catch {
        // The invoice list still renders when reference data fails to load.
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
        const result = await getTaxSettings(organizationId);
        if (!cancelled) {
          setTaxSettings(result);
          setTaxRate(result.tax_rate_percent ?? '');
          setTaxMode(result.tax_mode);
        }
      } catch {
        // Tax settings stay unconfigured when the read fails.
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
        const result = await listInvoices(organizationId, {
          status: statusFilter ? (statusFilter as InvoiceStatus) : undefined,
          page,
        });
        if (!cancelled) setInvoices(result);
      } catch {
        if (!cancelled) setLoadError('Unable to load invoices. Please try again.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [organizationId, statusFilter, page]);

  async function saveTaxSettings(): Promise<void> {
    if (!organizationId || !taxSettings) return;
    setTaxMessage(null);

    const enabled = taxSettings.tax_enabled;
    if (enabled && !isCanonicalDecimal(taxRate, 4)) {
      setTaxMessage('Enter a tax rate with at most 4 decimal places, e.g. 7.5.');
      return;
    }

    const input: UpdateTaxSettingsInput = {
      tax_enabled: enabled,
      tax_rate_percent: enabled ? taxRate : null,
      tax_mode: taxMode,
    };

    try {
      const saved = await updateTaxSettings(organizationId, input);
      setTaxSettings(saved);
      setTaxMessage('Tax settings saved.');
    } catch {
      setTaxMessage('Unable to save tax settings. Please try again.');
    }
  }

  async function actionInvoice(invoice: Invoice, action: 'issue' | 'void' | 'send'): Promise<void> {
    if (!organizationId) return;
    setRowError(null);
    setRowBusy(`${invoice.id}:${action}`);
    try {
      if (action === 'issue') {
        const updated = await issueInvoice(organizationId, invoice.id);
        replaceInvoice(updated);
      } else if (action === 'void') {
        const updated = await voidInvoice(organizationId, invoice.id);
        replaceInvoice(updated);
      } else {
        // The recipient defaults from the attached customer on the server.
        await sendInvoice(organizationId, invoice.id, 'email');
      }
    } catch {
      setRowError(
        action === 'send'
          ? 'Unable to send the invoice. Attach a customer with an email or try again.'
          : 'Unable to update the invoice. Please try again.',
      );
    } finally {
      setRowBusy(null);
    }
  }

  function replaceInvoice(updated: Invoice): void {
    setInvoices((current) => ({
      ...current,
      data: current.data.map((item) => (item.id === updated.id ? updated : item)),
    }));
  }

  if (!activeOrganization) {
    return (
      <p className="text-sm text-on-surface-variant">
        Select an organization to manage its invoices.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-on-surface">Invoicing</h1>
        <p className="mt-1 text-sm text-on-surface-variant">
          Bill customers across {activeOrganization.name}. Issuing freezes the
          document; corrections use void, not deletion.
        </p>
      </header>

      <section aria-label="Invoices" className="space-y-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1">
            <Label htmlFor="invoice-status-filter">Status</Label>
            <select
              id="invoice-status-filter"
              value={statusFilter}
              onChange={(event) => {
                setLoading(true);
                setPage(1);
                setStatusFilter(event.target.value);
              }}
              className="h-10 rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
            >
              <option value="">All statuses</option>
              <option value="draft">Draft</option>
              <option value="issued">Issued</option>
              <option value="voided">Voided</option>
            </select>
          </div>
          <Link
            href="/invoicing/customers"
            className="inline-flex h-10 items-center rounded-full bg-secondary-container px-4 text-sm font-medium text-on-secondary-container transition-colors hover:opacity-90"
          >
            Manage customers
          </Link>
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
            Loading invoices…
          </p>
        ) : invoices.data.length === 0 ? (
          <p className="text-sm text-on-surface-variant">
            No invoices yet. Create the first draft below.
          </p>
        ) : (
          <div className="overflow-x-auto rounded-2xl bg-surface">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-gray-200 text-on-surface-variant">
                  <th scope="col" className="px-4 py-3 font-medium">Invoice</th>
                  <th scope="col" className="px-4 py-3 font-medium">Customer</th>
                  <th scope="col" className="px-4 py-3 font-medium">Status</th>
                  <th scope="col" className="px-4 py-3 font-medium text-right">Total</th>
                  <th scope="col" className="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {invoices.data.map((invoice) => (
                  <tr key={invoice.id} className="border-b border-gray-100 last:border-0">
                    <td className="px-4 py-3 font-mono font-medium text-on-surface">
                      {invoice.document_number ?? '— (draft)'}
                    </td>
                    <td className="px-4 py-3 text-on-surface-variant">
                      {invoice.customer_name ?? '—'}
                    </td>
                    <td className="px-4 py-3">
                      <Badge tone={STATUS_TONES[invoice.status]}>{invoice.status}</Badge>
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-on-surface">
                      {formatDecimalString(invoice.grand_total)} {invoice.currency}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <div className="flex justify-end gap-2">
                        {invoice.status === 'draft' && (
                          <Button
                            variant="secondary"
                            disabled={rowBusy !== null}
                            onClick={() => void actionInvoice(invoice, 'issue')}
                          >
                            {rowBusy === `${invoice.id}:issue` ? 'Issuing…' : 'Issue'}
                          </Button>
                        )}
                        {invoice.status === 'issued' && (
                          <Button
                            variant="secondary"
                            disabled={rowBusy !== null}
                            onClick={() => void actionInvoice(invoice, 'send')}
                          >
                            {rowBusy === `${invoice.id}:send` ? 'Sending…' : 'Email'}
                          </Button>
                        )}
                        {invoice.status !== 'voided' && (
                          <Button
                            variant="ghost"
                            disabled={rowBusy !== null}
                            onClick={() => void actionInvoice(invoice, 'void')}
                          >
                            {rowBusy === `${invoice.id}:void` ? 'Voiding…' : 'Void'}
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <Pagination
          currentPage={invoices.current_page}
          lastPage={invoices.last_page}
          disabled={loading}
          onPageChange={(target) => {
            setLoading(true);
            setPage(target);
          }}
        />
      </section>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardTitle>Create a draft invoice</CardTitle>
          <div className="mt-4">
            <InvoiceForm
              organizationId={activeOrganization.id}
              products={products.filter((product) => !product.archived_at)}
              customers={customers.filter((customer) => !customer.archived_at)}
              onCreated={(invoice) =>
                setInvoices((current) => ({
                  ...current,
                  data: [invoice, ...current.data],
                  total: current.total + 1,
                }))
              }
            />
          </div>
        </Card>

        <Card>
          <CardTitle>Tax settings</CardTitle>
          <p className="mt-2 text-xs text-on-surface-variant">
            Applied to invoices at issue time. Issued invoices keep their snapshot.
          </p>
          {taxSettings && (
            <div className="mt-4 space-y-4">
              <div className="flex items-center gap-3">
                <input
                  id="tax-enabled"
                  type="checkbox"
                  checked={taxSettings.tax_enabled}
                  onChange={(event) =>
                    setTaxSettings({ ...taxSettings, tax_enabled: event.target.checked })
                  }
                  className="h-4 w-4"
                />
                <Label htmlFor="tax-enabled">Charge tax on invoices</Label>
              </div>
              {taxSettings.tax_enabled && (
                <div className="space-y-1">
                  <Label htmlFor="tax-rate">Tax rate (%)</Label>
                  <Input
                    id="tax-rate"
                    inputMode="decimal"
                    placeholder="7.5"
                    value={taxRate}
                    onChange={(event) => setTaxRate(event.target.value)}
                  />
                  {taxSettings.tax_enabled && !isCanonicalDecimal(taxRate, 4) && taxRate !== '' && (
                    <p className="text-sm text-red-600">
                      Enter a tax rate with at most 4 decimal places.
                    </p>
                  )}
                </div>
              )}
              {taxSettings.tax_enabled && (
                <div className="space-y-1">
                  <Label htmlFor="tax-mode">Tax mode</Label>
                  <select
                    id="tax-mode"
                    value={taxMode}
                    onChange={(event) =>
                      setTaxMode(event.target.value as 'exclusive' | 'inclusive')
                    }
                    className="h-10 w-full rounded-lg border border-gray-300 bg-surface px-3 text-sm text-on-surface"
                  >
                    <option value="exclusive">Exclusive (added on top)</option>
                    <option value="inclusive">Inclusive (contained in the price)</option>
                  </select>
                </div>
              )}
              <Button type="button" onClick={() => void saveTaxSettings()}>
                Save tax settings
              </Button>
              {taxMessage && (
                <p aria-live="polite" className="text-sm text-on-surface-variant">
                  {taxMessage}
                </p>
              )}
            </div>
          )}
        </Card>
      </div>
    </div>
  );
}
