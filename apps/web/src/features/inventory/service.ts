import { api } from '@/lib/api';
import type {
  ApiResponse,
  InventoryLocation,
  PaginatedResponse,
  StockBalance,
  StockMovement,
  StockMovementType,
  StockTransfer,
} from '@/types';

export interface ListInventoryLocationsParams {
  branchId?: string;
  archived?: 'true' | 'false';
  q?: string;
  page?: number;
  perPage?: number;
}

export interface CreateInventoryLocationInput {
  code: string;
  name: string;
  branch_id?: string | null;
}

export interface ListStockMovementsParams {
  productId?: string;
  locationId?: string;
  type?: StockMovementType;
  page?: number;
  perPage?: number;
}

export interface ListStockBalancesParams {
  productId?: string;
  locationId?: string;
  page?: number;
}

export interface RecordStockMovementInput {
  product_id: string;
  location_id: string;
  type: StockMovementType;
  quantity: string;
  unit_cost?: string | null;
  reference?: string | null;
  note?: string | null;
  occurred_at?: string;
  corrects_movement_id?: string | null;
}

export interface RecordStockCountInput {
  product_id: string;
  location_id: string;
  counted_quantity: string;
  reference?: string | null;
  note?: string | null;
  occurred_at?: string;
}

export interface RecordStockTransferInput {
  product_id: string;
  from_location_id: string;
  to_location_id: string;
  quantity: string;
  reference?: string | null;
  note?: string | null;
  occurred_at?: string;
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

export async function listInventoryLocations(
  organizationId: string,
  params: ListInventoryLocationsParams = {},
): Promise<PaginatedResponse<InventoryLocation>> {
  return api.get<PaginatedResponse<InventoryLocation>>(
    `${organizationPath(organizationId)}/inventory-locations${query({
      branch_id: params.branchId,
      archived: params.archived,
      q: params.q,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function createInventoryLocation(
  organizationId: string,
  input: CreateInventoryLocationInput,
): Promise<InventoryLocation> {
  const body = await api.post<ApiResponse<InventoryLocation>>(
    `${organizationPath(organizationId)}/inventory-locations`,
    input,
  );
  return body.data;
}

export async function archiveInventoryLocation(
  organizationId: string,
  locationId: string,
): Promise<InventoryLocation> {
  const body = await api.post<ApiResponse<InventoryLocation>>(
    `${organizationPath(organizationId)}/inventory-locations/${locationId}/archive`,
  );
  return body.data;
}

export async function listStockMovements(
  organizationId: string,
  params: ListStockMovementsParams = {},
): Promise<PaginatedResponse<StockMovement>> {
  return api.get<PaginatedResponse<StockMovement>>(
    `${organizationPath(organizationId)}/stock-movements${query({
      product_id: params.productId,
      location_id: params.locationId,
      type: params.type,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function listStockBalances(
  organizationId: string,
  params: ListStockBalancesParams = {},
): Promise<PaginatedResponse<StockBalance>> {
  return api.get<PaginatedResponse<StockBalance>>(
    `${organizationPath(organizationId)}/stock-balances${query({
      product_id: params.productId,
      location_id: params.locationId,
      page: params.page,
    })}`,
  );
}

export async function recordStockMovement(
  organizationId: string,
  input: RecordStockMovementInput,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<StockMovement> {
  const body = await api.post<ApiResponse<StockMovement>>(
    `${organizationPath(organizationId)}/stock-movements`,
    input,
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}

export async function recordStockCount(
  organizationId: string,
  input: RecordStockCountInput,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<StockMovement> {
  const body = await api.post<ApiResponse<StockMovement>>(
    `${organizationPath(organizationId)}/stock-counts`,
    input,
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}

export async function recordStockTransfer(
  organizationId: string,
  input: RecordStockTransferInput,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<StockTransfer> {
  const body = await api.post<ApiResponse<StockTransfer>>(
    `${organizationPath(organizationId)}/stock-transfers`,
    input,
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}
