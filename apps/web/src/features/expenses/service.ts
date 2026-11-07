import { api } from '@/lib/api';
import type {
  ApiResponse,
  Expense,
  ExpenseCategory,
  ExpenseStatus,
  PaginatedResponse,
  PaymentMethod,
} from '@/types';

export interface ListExpenseCategoriesParams {
  archived?: 'true' | 'false';
  q?: string;
  page?: number;
  perPage?: number;
}

export interface CreateExpenseCategoryInput {
  name: string;
  description?: string | null;
}

export type UpdateExpenseCategoryInput = Partial<CreateExpenseCategoryInput>;

export interface ListExpensesParams {
  expenseCategoryId?: string;
  status?: ExpenseStatus;
  paymentMethod?: PaymentMethod;
  page?: number;
  perPage?: number;
}

export interface CreateExpenseInput {
  expense_category_id: string;
  branch_id?: string | null;
  description: string;
  amount: string;
  spent_on?: string;
  payee?: string | null;
  payment_method?: PaymentMethod;
  note?: string | null;
}

export type UpdateExpenseInput = Partial<CreateExpenseInput>;

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

export async function listExpenseCategories(
  organizationId: string,
  params: ListExpenseCategoriesParams = {},
): Promise<PaginatedResponse<ExpenseCategory>> {
  return api.get<PaginatedResponse<ExpenseCategory>>(
    `${organizationPath(organizationId)}/expense-categories${query({
      archived: params.archived,
      q: params.q,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function createExpenseCategory(
  organizationId: string,
  input: CreateExpenseCategoryInput,
): Promise<ExpenseCategory> {
  const body = await api.post<ApiResponse<ExpenseCategory>>(
    `${organizationPath(organizationId)}/expense-categories`,
    input,
  );
  return body.data;
}

export async function updateExpenseCategory(
  organizationId: string,
  categoryId: string,
  input: UpdateExpenseCategoryInput,
): Promise<ExpenseCategory> {
  const body = await api.patch<ApiResponse<ExpenseCategory>>(
    `${organizationPath(organizationId)}/expense-categories/${categoryId}`,
    input,
  );
  return body.data;
}

export async function archiveExpenseCategory(
  organizationId: string,
  categoryId: string,
): Promise<ExpenseCategory> {
  const body = await api.post<ApiResponse<ExpenseCategory>>(
    `${organizationPath(organizationId)}/expense-categories/${categoryId}/archive`,
  );
  return body.data;
}

export async function listExpenses(
  organizationId: string,
  params: ListExpensesParams = {},
): Promise<PaginatedResponse<Expense>> {
  return api.get<PaginatedResponse<Expense>>(
    `${organizationPath(organizationId)}/expenses${query({
      expense_category_id: params.expenseCategoryId,
      status: params.status,
      payment_method: params.paymentMethod,
      page: params.page,
      per_page: params.perPage,
    })}`,
  );
}

export async function createExpense(
  organizationId: string,
  input: CreateExpenseInput,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<Expense> {
  const body = await api.post<ApiResponse<Expense>>(
    `${organizationPath(organizationId)}/expenses`,
    input,
    { 'Idempotency-Key': idempotencyKey },
  );
  return body.data;
}

export async function updateExpense(
  organizationId: string,
  expenseId: string,
  input: UpdateExpenseInput,
): Promise<Expense> {
  const body = await api.patch<ApiResponse<Expense>>(
    `${organizationPath(organizationId)}/expenses/${expenseId}`,
    input,
  );
  return body.data;
}

export async function voidExpense(
  organizationId: string,
  expenseId: string,
  idempotencyKey: string = newIdempotencyKey(),
): Promise<Expense> {
  const body = await api.post<ApiResponse<Expense>>(
    `${organizationPath(organizationId)}/expenses/${expenseId}/void`,
    {},
    { 'Idempotency-Key': idempotencyKey },
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
