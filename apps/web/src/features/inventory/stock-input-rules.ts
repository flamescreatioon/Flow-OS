import { isCanonicalDecimal } from '@/utils/decimal';
import type { StockMovementType } from '@/types';

/**
 * Client-side input checks that mirror the server's StockRules so a user
 * sees the problem before submission. The API remains the authority; these
 * helpers never decide the applied direction — for fixed-direction movement
 * types the caller states a magnitude and the server applies the sign.
 */

const QUANTITY_MAX_SCALE = 3;

/** Movement types whose quantity is a magnitude; the server applies the direction. */
export function acceptsSignedQuantity(type: StockMovementType): boolean {
  return type === 'adjustment';
}

/** Returns an error message, or null when the stated quantity is acceptable. */
export function validateMovementQuantity(
  type: StockMovementType,
  value: string,
): string | null {
  if (!isCanonicalDecimal(value, QUANTITY_MAX_SCALE)) {
    return 'Enter a decimal with at most 3 places, e.g. 12.5.';
  }
  if (value.startsWith('-') && !acceptsSignedQuantity(type)) {
    return `A ${type} movement states a magnitude, not a signed quantity.`;
  }
  if (isZeroDecimal(value)) {
    return 'A movement quantity must not be zero.';
  }
  return null;
}

/** A counted quantity is an observation: never negative, never above the unit precision. */
export function validateCountedQuantity(
  value: string,
  unitPrecision: number,
): string | null {
  if (!isCanonicalDecimal(value, QUANTITY_MAX_SCALE)) {
    return 'Enter a decimal with at most 3 places, e.g. 12.5.';
  }
  if (value.startsWith('-')) {
    return 'A counted quantity must not be negative.';
  }
  const fraction = value.split('.')[1] ?? '';
  if (fraction.replace(/0+$/, '').length > unitPrecision) {
    return `The unit of measure allows at most ${unitPrecision} decimal place${unitPrecision === 1 ? '' : 's'}.`;
  }
  return null;
}

/** A transfer states a positive magnitude moved between two locations. */
export function validateTransferQuantity(value: string): string | null {
  if (!isCanonicalDecimal(value, QUANTITY_MAX_SCALE)) {
    return 'Enter a decimal with at most 3 places, e.g. 12.5.';
  }
  if (value.startsWith('-')) {
    return 'A transfer states a magnitude, not a signed quantity.';
  }
  if (isZeroDecimal(value)) {
    return 'A transfer quantity must not be zero.';
  }
  return null;
}

function isZeroDecimal(value: string): boolean {
  return !/[1-9]/.test(value.replace('-', ''));
}
