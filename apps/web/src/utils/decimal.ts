/**
 * Canonical decimal strings are the only representation the API accepts for
 * prices and quantities (see the CanonicalDecimal rule in apps/api). An
 * optional leading '-', a required integer part, and at most `maxScale`
 * fractional digits. Values are never converted to binary floats here; these
 * helpers validate input and format strings for display only.
 */

const CANONICAL_PATTERN = /^-?\d+(\.\d+)?$/;

export function isCanonicalDecimal(value: string, maxScale: number): boolean {
  if (!CANONICAL_PATTERN.test(value)) return false;
  const fraction = value.split('.')[1] ?? '';
  return fraction.length <= maxScale;
}

/**
 * Trims trailing zeros for display only, e.g. "12.500" renders as "12.5"
 * while the underlying string stays authoritative.
 */
export function formatDecimalString(value: string): string {
  if (!value.includes('.')) return value;
  const trimmed = value.replace(/0+$/, '').replace(/\.$/, '');
  if (trimmed === '' || /^-?0+$/.test(trimmed)) return '0';
  return trimmed;
}
