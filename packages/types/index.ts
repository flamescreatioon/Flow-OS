// Shared TypeScript types for flowOS
// These types are primarily for the web application
// Other platforms (PHP, Dart, C#) should generate their own from OpenAPI

// ============================================
// Core Types
// ============================================

export type UUID = string & { readonly __brand: unique symbol };

export function isUUID(value: string): value is UUID {
  return /^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value);
}

export type ISODateTime = string & { readonly __brand: unique symbol };

export function isISODateTime(value: string): value is ISODateTime {
  return !isNaN(Date.parse(value));
}

export type CurrencyCode = 'NGN' | 'USD' | 'EUR' | 'GBP' | string;

export type Timezone = string; // IANA timezone, e.g., 'Africa/Lagos'

// ============================================
// Pagination
// ============================================

export interface PaginationParams {
  page?: number;
  per_page?: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// ============================================
// API Response Wrappers
// ============================================

export interface ApiResponse<T> {
  data: T;
  meta?: Record<string, unknown>;
}

export interface ApiErrorResponse {
  message: string;
  errors?: Record<string, string[]>;
}

// ============================================
// Organization
// ============================================

export interface Organization {
  id: UUID;
  name: string;
  slug: string;
  timezone: Timezone;
  currency: CurrencyCode;
  settings?: Record<string, unknown>;
  created_at: ISODateTime;
  updated_at: ISODateTime;
}

export interface CreateOrganizationRequest {
  name: string;
  slug: string;
  timezone?: Timezone;
  currency?: CurrencyCode;
}

export interface UpdateOrganizationRequest {
  name?: string;
  slug?: string;
  timezone?: Timezone;
  currency?: CurrencyCode;
  settings?: Record<string, unknown>;
}

// ============================================
// Branch
// ============================================

export interface Branch {
  id: UUID;
  organization_id: UUID;
  name: string;
  address?: string;
  phone?: string;
  email?: string;
  is_main: boolean;
  created_at: ISODateTime;
  updated_at: ISODateTime;
}

export interface CreateBranchRequest {
  name: string;
  address?: string;
  phone?: string;
  email?: string;
  is_main?: boolean;
}

export interface UpdateBranchRequest {
  name?: string;
  address?: string;
  phone?: string;
  email?: string;
  is_main?: boolean;
}

// ============================================
// User
// ============================================

export type UserRole = 'owner' | 'admin' | 'manager' | 'cashier' | 'viewer';

export interface User {
  id: UUID;
  organization_id: UUID;
  name: string;
  email: string;
  role: UserRole;
  is_active: boolean;
  email_verified_at?: ISODateTime;
  created_at: ISODateTime;
  updated_at: ISODateTime;
}

export interface CreateUserRequest {
  name: string;
  email: string;
  password: string;
  role: UserRole;
}

export interface UpdateUserRequest {
  name?: string;
  email?: string;
  role?: UserRole;
  is_active?: boolean;
}

// ============================================
// Authentication
// ============================================

export interface LoginRequest {
  email: string;
  password: string;
  organization_slug: string;
}

export interface LoginResponse {
  token: string;
  user: User;
  organization: Organization;
}

export interface AuthTokens {
  access_token: string;
  refresh_token?: string;
  expires_in: number;
  token_type: 'Bearer';
}

// ============================================
// Money
// ============================================

export interface Money {
  amount: number; // Minor units (kobo for NGN, cents for USD)
  currency: CurrencyCode;
}

export function createMoneyFromMinor(amountMinor: number, currency: CurrencyCode): Money {
  if (!Number.isSafeInteger(amountMinor)) {
    throw new Error('amountMinor must be a safe integer (within Number.MAX_SAFE_INTEGER)');
  }
  return {
    amount: amountMinor,
    currency,
  };
}

/**
 * Create Money from a major-unit decimal string (e.g., "1500.50").
 * Parses the string exactly to avoid floating-point inaccuracies.
 * This is for INPUT boundaries only (e.g., user input parsing).
 * The returned Money uses integer minor units as the authoritative representation.
 *
 * Rejects:
 * - Excess fractional precision beyond currency's supported digits
 * - Malformed input (empty, multiple dots, non-numeric, commas, leading '+', etc.)
 * - Values exceeding Number.MAX_SAFE_INTEGER / MIN_SAFE_INTEGER
 *
 * Supported formats:
 *   "1500.50", "12", "0.50", "-1500.50", "-0.50", "0", "-0.00"
 * Rejected:
 *   "", ".", "-", ".50", "12.", "12.999", "1,200.50", "+12.50", "abc"
 */
export function createMoneyFromMajorString(amountMajor: string, currency: CurrencyCode): Money {
  // Currency fractional digits map (authoritative)
  const fractionDigits: Record<CurrencyCode, number> = {
    NGN: 2,
    USD: 2,
    EUR: 2,
    GBP: 2,
    JPY: 0,
    KRW: 0,
  };
  const fractionDigitsForCurrency = fractionDigits[currency] ?? 2;
  // 1. Basic validation: non-empty string
  if (!amountMajor || amountMajor.length === 0) {
    throw new Error('Amount string is empty');
  }

  // 2. Reject leading '+' explicitly (not a valid canonical decimal)
  if (amountMajor[0] === '+') {
    throw new Error('Leading plus sign not allowed; use plain decimal string');
  }

  // 3. Parse sign separately
  let sign = 1;
  let absStr = amountMajor;
  if (amountMajor[0] === '-') {
    sign = -1;
    absStr = amountMajor.slice(1);
  }

  // 4. After sign removal, string must not be empty
  if (absStr.length === 0) {
    throw new Error('Amount string is only a sign');
  }

  // 4. Reject multiple dots
  const dotCount = (absStr.match(/\./g) || []).length;
  if (dotCount > 1) {
    throw new Error('Multiple decimal points not allowed');
  }

  // 5. Split integer and fractional parts
  const parts = absStr.split('.');
  const integerStr = parts[0];
  const fractionalStr = parts[1] || '';

  // 5a. Integer part must be digits only (or empty for ".50" which we reject)
  if (integerStr.length === 0) {
    throw new Error('Missing integer part (shorthand ".50" not allowed)');
  }
  if (!/^\d+$/.test(integerStr)) {
    throw new Error('Integer part must contain only digits');
  }

  // 5b. Fractional part: if present, must be digits only and exact length
  if (parts.length === 2) {
    if (fractionalStr.length === 0) {
      throw new Error('Trailing decimal point not allowed (e.g., "12.")');
    }
    if (!/^\d+$/.test(fractionalStr)) {
      throw new Error('Fractional part must contain only digits');
    }
    // Reject excess precision
    if (fractionalStr.length > fractionDigitsForCurrency) {
      throw new Error(`Fractional part exceeds ${fractionDigitsForCurrency} digits for currency ${currency}`);
    }
  } else {
    // No fractional part provided; that's fine (treated as 0)
  }

  // 6. Build absolute minor units using BigInt for exact arithmetic
  const integerPart = BigInt(integerStr); // safe: only digits, no leading zeros issue for BigInt

  let fractionalBigInt = 0n;
  if (parts.length === 2) {
    // Pad fractional to exact fractionDigitsForCurrency with trailing zeros
    const paddedFractional = fractionalStr.padEnd(fractionDigitsForCurrency, '0');
    fractionalBigInt = BigInt(paddedFractional);
  }

  // 6a. Compute absolute minor units
  const divisor = 10n ** BigInt(fractionDigitsForCurrency);
  const absMinorBigInt = integerPart * divisor + fractionalBigInt;

  // 7. Apply sign
  const signedMinorBigInt = sign === 1 ? absMinorBigInt : -absMinorBigInt;

  // 7a. Handle negative zero: treat -0 as 0
  const finalMinorBigInt = signedMinorBigInt === 0n ? 0n : signedMinorBigInt;

  // 8. Safe integer range check BEFORE converting to number
  if (finalMinorBigInt > BigInt(Number.MAX_SAFE_INTEGER) ||
      finalMinorBigInt < BigInt(Number.MIN_SAFE_INTEGER)) {
    throw new Error('Amount exceeds safe integer range');
  }

  return {
    amount: Number(finalMinorBigInt),
    currency,
  };
}

/**
 * DISPLAY ONLY — never use formatted/major-unit floating value for financial calculations.
 * Converts exact minor units to a localized currency string for presentation only.
 */
export function formatMoney(money: Money, locale = 'en-NG'): string {
  const divisor = money.currency === 'JPY' || money.currency === 'KRW' ? 1 : 100;
  const majorAmount = money.amount / divisor;
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: money.currency,
    minimumFractionDigits: money.currency === 'JPY' || money.currency === 'KRW' ? 0 : 2,
  }).format(majorAmount);
}

// ============================================
// Common Enums
// ============================================

export type OrderStatus = 'draft' | 'pending' | 'confirmed' | 'processing' | 'completed' | 'cancelled' | 'refunded';
export type PaymentStatus = 'pending' | 'paid' | 'partial' | 'refunded' | 'failed';
export type PaymentMethod = 'cash' | 'card' | 'transfer' | 'ussd' | 'wallet' | 'pos';
export type StockMovementType = 'in' | 'out' | 'adjustment' | 'transfer' | 'return';
export type InvoiceStatus = 'draft' | 'sent' | 'viewed' | 'paid' | 'overdue' | 'cancelled';
export type ExpenseCategory = 'rent' | 'utilities' | 'salaries' | 'marketing' | 'supplies' | 'transport' | 'other';

// ============================================
// Soft Delete
// ============================================

export interface SoftDeletable {
  deleted_at?: ISODateTime;
}

export function isDeleted(entity: SoftDeletable): boolean {
  return entity.deleted_at !== undefined;
}
