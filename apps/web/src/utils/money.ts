/**
 * Display-only money formatting for exact decimal strings.
 *
 * The authoritative representation is the decimal string on the wire (see
 * utils/decimal.ts); this helper only turns it into a human-readable amount.
 * Display parsing into a JS number is safe here — a rounded display value
 * never feeds back into any calculation or request.
 */

const AMOUNT_PATTERN = /^-?\d+(\.\d+)?$/;

const CURRENCY_LOCALE: Record<string, string> = {
  NGN: 'en-NG',
  USD: 'en-US',
  EUR: 'de-DE',
  GBP: 'en-GB',
};

const KNOWN_CURRENCIES = new Set(Object.keys(CURRENCY_LOCALE));

export function isKnownCurrency(currency: string): boolean {
  return KNOWN_CURRENCIES.has(currency.toUpperCase());
}

/**
 * Format a decimal string as a currency amount, e.g. ("3225.0000", "NGN")
 * renders "₦3,225.00". Unknown currencies or malformed values fall back to
 * the raw string so the dashboard never crashes on bad data.
 */
export function formatMoney(value: string, currency = 'NGN'): string {
  if (!AMOUNT_PATTERN.test(value)) {
    return value;
  }

  const code = currency.toUpperCase();

  if (!KNOWN_CURRENCIES.has(code)) {
    return `${value} ${code}`;
  }

  try {
    return new Intl.NumberFormat(CURRENCY_LOCALE[code], {
      style: 'currency',
      currency: code,
    }).format(Number(value));
  } catch {
    return `${value} ${code}`;
  }
}
