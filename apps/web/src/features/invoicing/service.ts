import { api } from '@/lib/api';
import type {
  ApiResponse,
  Customer,
  Invoice,
  InvoiceStatus,
  NotificationDelivery,
  PaginatedResponse,
  TaxMode,
  TaxSettings,
} from '@/types';

export interface ListCustomersParams {
  archived?: 'true' | 'false';
  q?: string;
  page?: number;
  perPage?: number;
}

export interface CreateCustomerInput {
  name: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
}

export type UpdateCustomerInput = Partial<CreateCustomerInput>;

export interface ListInvoicesParams {
  customerId?: string;
  status?: InvoiceStatus;
  page?: number;
  perPage?: number;
}

export interface InvoiceLineInput {
  product_id?: string | null;
  description: string;
  quantity: string;
  unit_price: string;
  discount?: string | null;
}

export interface CreateInvoiceInput {
  customer_id?: string | null;
  due_on?: string;
  note?: string | null;
  lines: InvoiceLineInput[];
}

export type UpdateInvoiceInput = Partial<CreateInvoiceInput>;

export interface UpdateTaxSettingsInput {
  tax_enabled: boolean;
  tax_rate_percent?: string | null;
  tax_mode?: TaxMode;
}

function organizationPath(organizationId: string): string {
  return `/organizations/${organizationId}`;
}

function query(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== '') search.set(key, String(value));
  }
  const encoded = search.toString();
  return encoded ? `?${encoded}` : '';
}

export async function listCustomers(
  organizationId: string,
  params: ListCustomersParams = {},
): Promise<PaginatedResponse<Customer>> {
  return api.get<PaginatedResponse<Customer>>(
    `${organizationPath(organizationId)}/customers${query({
      archived: params.archived,
      q: params.q,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function createCustomer(
  organizationId: string,
  input: CreateCustomerInput,
): Promise<Customer> {
  const body = await api.post<ApiResponse<Customer>>(
    `${organizationPath(organizationId)}/customers`,
    input,
  );
  return body.data;
}

export async function updateCustomer(
  organizationId: string,
  customerId: string,
  input: UpdateCustomerInput,
): Promise<Customer> {
  const body = await api.patch<ApiResponse<Customer>>(
    `${organizationPath(organizationId)}/customers/${customerId}`,
    input,
  );
  return body.data;
}

export async function archiveCustomer(
  organizationId: string,
  customerId: string,
): Promise<Customer> {
  const body = await api.post<ApiResponse<Customer>>(
    `${organizationPath(organizationId)}/customers/${customerId}/archive`,
  );
  return body.data;
}

export async function listInvoices(
  organizationId: string,
  params: ListInvoicesParams = {},
): Promise<PaginatedResponse<Invoice>> {
  return api.get<PaginatedResponse<Invoice>>(
    `${organizationPath(organizationId)}/invoices${query({
      customer_id: params.customerId,
      status: params.status,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function createInvoice(
  organizationId: string,
  input: CreateInvoiceInput,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<Invoice> {
  const body = await api.post<ApiResponse<Invoice>>(
    `${organizationPath(organizationId)}/invoices`,
    input,
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}

export async function updateInvoice(
  organizationId: string,
  invoiceId: string,
  input: UpdateInvoiceInput,
): Promise<Invoice> {
  const body = await api.patch<ApiResponse<Invoice>>(
    `${organizationPath(organizationId)}/invoices/${invoiceId}`,
    input,
  );
  return body.data;
}

export async function issueInvoice(
  organizationId: string,
  invoiceId: string,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<Invoice> {
  const body = await api.post<ApiResponse<Invoice>>(
    `${organizationPath(organizationId)}/invoices/${invoiceId}/issue`,
    {},
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}

export async function voidInvoice(
  organizationId: string,
  invoiceId: string,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<Invoice> {
  const body = await api.post<ApiResponse<Invoice>>(
    `${organizationPath(organizationId)}/invoices/${invoiceId}/void`,
    {},
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}

export async function sendInvoice(
  organizationId: string,
  invoiceId: string,
  channel: 'email' | 'sms' | 'whatsapp',
  recipient?: string,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<NotificationDelivery> {
  const body = await api.post<ApiResponse<NotificationDelivery>>(
    `${organizationPath(organizationId)}/invoices/${invoiceId}/send`,
    { channel, recipient: recipient || null },
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}

export async function getTaxSettings(organizationId: string): Promise<TaxSettings> {
  const body = await api.get<ApiResponse<TaxSettings>>(
    `${organizationPath(organizationId)}/tax-settings`,
  );
  return body.data;
}

export async function updateTaxSettings(
  organizationId: string,
  input: UpdateTaxSettingsInput,
): Promise<TaxSettings> {
  const body = await api.patch<ApiResponse<TaxSettings>>(
    `${organizationPath(organizationId)}/tax-settings`,
    input,
  );
  return body.data;
}

/**
 * A fresh key per write. Repeating a submission with the same key replays
 * the original response instead of recording the operation twice.
 */
export function newIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID();
  }
  return `key-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}
