import { api } from '@/lib/api';
import type {
  ApiResponse,
  Category,
  PaginatedResponse,
  Product,
  ProductType,
  UnitOfMeasure,
} from '@/types';

export interface ListProductsParams {
  type?: ProductType;
  categoryId?: string;
  /** 'true' lists only archived products, 'all' lists both. */
  archived?: 'true' | 'false' | 'all';
  q?: string;
  page?: number;
  perPage?: number;
}

export interface CreateProductInput {
  name: string;
  sku?: string | null;
  type: ProductType;
  price: string;
  tax_code?: string | null;
  unit_of_measure_id: string;
  category_id?: string | null;
}

/** Every field is optional; only the fields present in the payload change. */
export type UpdateProductInput = Partial<CreateProductInput>;

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

export async function listProducts(
  organizationId: string,
  params: ListProductsParams = {},
): Promise<PaginatedResponse<Product>> {
  return api.get<PaginatedResponse<Product>>(
    `${organizationPath(organizationId)}/products${query({
      type: params.type,
      category_id: params.categoryId,
      archived: params.archived,
      q: params.q,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function createProduct(
  organizationId: string,
  input: CreateProductInput,
): Promise<Product> {
  const body = await api.post<ApiResponse<Product>>(
    `${organizationPath(organizationId)}/products`,
    input,
  );
  return body.data;
}

export async function updateProduct(
  organizationId: string,
  productId: string,
  input: UpdateProductInput,
): Promise<Product> {
  const body = await api.patch<ApiResponse<Product>>(
    `${organizationPath(organizationId)}/products/${productId}`,
    input,
  );
  return body.data;
}

export async function archiveProduct(
  organizationId: string,
  productId: string,
): Promise<Product> {
  const body = await api.post<ApiResponse<Product>>(
    `${organizationPath(organizationId)}/products/${productId}/archive`,
  );
  return body.data;
}

export async function restoreProduct(
  organizationId: string,
  productId: string,
): Promise<Product> {
  const body = await api.post<ApiResponse<Product>>(
    `${organizationPath(organizationId)}/products/${productId}/restore`,
  );
  return body.data;
}

export async function listCategories(
  organizationId: string,
): Promise<PaginatedResponse<Category>> {
  return api.get<PaginatedResponse<Category>>(
    `${organizationPath(organizationId)}/categories?per_page=100`,
  );
}

export async function listUnitOfMeasures(
  organizationId: string,
): Promise<PaginatedResponse<UnitOfMeasure>> {
  return api.get<PaginatedResponse<UnitOfMeasure>>(
    `${organizationPath(organizationId)}/unit-of-measures?per_page=100`,
  );
}

export async function createCategory(
  organizationId: string,
  input: { name: string; description?: string | null },
): Promise<Category> {
  const body = await api.post<ApiResponse<Category>>(
    `${organizationPath(organizationId)}/categories`,
    input,
  );
  return body.data;
}

export async function updateCategory(
  organizationId: string,
  categoryId: string,
  input: { name?: string; description?: string | null },
): Promise<Category> {
  const body = await api.patch<ApiResponse<Category>>(
    `${organizationPath(organizationId)}/categories/${categoryId}`,
    input,
  );
  return body.data;
}

export async function createUnitOfMeasure(
  organizationId: string,
  input: { code: string; name: string; precision?: number },
): Promise<UnitOfMeasure> {
  const body = await api.post<ApiResponse<UnitOfMeasure>>(
    `${organizationPath(organizationId)}/unit-of-measures`,
    input,
  );
  return body.data;
}
