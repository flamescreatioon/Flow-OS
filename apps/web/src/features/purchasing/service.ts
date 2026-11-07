import { api } from '@/lib/api';
import type { ApiResponse, GoodsReceipt, PaginatedResponse, Supplier } from '@/types';

export interface ListSuppliersParams {
  archived?: 'true' | 'false';
  q?: string;
  sort?: string;
  page?: number;
  perPage?: number;
}

export interface CreateSupplierInput {
  name: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
}

export type UpdateSupplierInput = Partial<CreateSupplierInput>;

export interface ListGoodsReceiptsParams {
  supplierId?: string;
  locationId?: string;
  page?: number;
  perPage?: number;
}

export interface RecordGoodsReceiptLineInput {
  product_id: string;
  quantity: string;
  unit_cost: string;
}

export interface RecordGoodsReceiptInput {
  supplier_id?: string | null;
  branch_id?: string | null;
  location_id: string;
  received_on?: string;
  note?: string | null;
  lines: RecordGoodsReceiptLineInput[];
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

export async function listSuppliers(
  organizationId: string,
  params: ListSuppliersParams = {},
): Promise<PaginatedResponse<Supplier>> {
  return api.get<PaginatedResponse<Supplier>>(
    `${organizationPath(organizationId)}/suppliers${query({
      archived: params.archived,
      q: params.q,
      sort: params.sort,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function createSupplier(
  organizationId: string,
  input: CreateSupplierInput,
): Promise<Supplier> {
  const body = await api.post<ApiResponse<Supplier>>(
    `${organizationPath(organizationId)}/suppliers`,
    input,
  );
  return body.data;
}

export async function updateSupplier(
  organizationId: string,
  supplierId: string,
  input: UpdateSupplierInput,
): Promise<Supplier> {
  const body = await api.patch<ApiResponse<Supplier>>(
    `${organizationPath(organizationId)}/suppliers/${supplierId}`,
    input,
  );
  return body.data;
}

export async function archiveSupplier(
  organizationId: string,
  supplierId: string,
): Promise<Supplier> {
  const body = await api.post<ApiResponse<Supplier>>(
    `${organizationPath(organizationId)}/suppliers/${supplierId}/archive`,
  );
  return body.data;
}

export async function listGoodsReceipts(
  organizationId: string,
  params: ListGoodsReceiptsParams = {},
): Promise<PaginatedResponse<GoodsReceipt>> {
  return api.get<PaginatedResponse<GoodsReceipt>>(
    `${organizationPath(organizationId)}/goods-receipts${query({
      supplier_id: params.supplierId,
      location_id: params.locationId,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function recordGoodsReceipt(
  organizationId: string,
  input: RecordGoodsReceiptInput,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<GoodsReceipt> {
  const body = await api.post<ApiResponse<GoodsReceipt>>(
    `${organizationPath(organizationId)}/goods-receipts`,
    input,
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}

/**
 * A fresh key per write. Repeating a submission with the same key replays
 * the original response instead of recording the receipt twice.
 */
export function newIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID();
  }
  return `key-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}
