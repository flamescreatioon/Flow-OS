# Money Storage

> Sprint 0.13: use BIGINT minor units where suitable or field-specific exact `NUMERIC(p,s)` where needed. `NUMERIC(19,4)` is not universal; see [financial standards](../finance/README.md).

## Golden Rule

**Never use floating-point types (`FLOAT`, `REAL`, `DOUBLE PRECISION`) for money.**

Floating-point arithmetic is inexact and causes rounding errors that are unacceptable for financial data.

## Approved Approaches

### Option A: Integer Minor Units (Preferred for Operations)

Store amounts as integers in the smallest currency unit.

| Currency | Minor Unit | Example |
|----------|------------|---------|
| NGN | Kobo | ₦1,500.00 = 150,000 kobo |
| USD | Cent | $99.99 = 9,999 cents |
| EUR | Cent | €49.95 = 4,995 cents |
| GBP | Penny | £19.99 = 1,999 pence |
| JPY | Yen | ¥1,000 = 1,000 (no minor unit) |

```sql
-- Schema
price_in_minor_units BIGINT NOT NULL,  -- 150000 = ₦1,500.00
currency CHAR(3) NOT NULL DEFAULT 'NGN',
```

```php
// Laravel model
protected $casts = [
    'price_in_minor_units' => 'integer',
];

// Accessor for display
public function getPriceAttribute(): string
{
    return $this->formatMoney($this->price_in_minor_units, $this->currency);
}

// Helper
function formatMoney(int $minorUnits, string $currency = 'NGN'): string {
    $divisor = match($currency) {
        'JPY', 'KRW' => 1,
        default => 100,
    };
    return number_format($this->amount / $divisor, $currency === 'JPY' ? 0 : 2, '.', ',');
}
```

### Option B: NUMERIC(p,s) / DECIMAL(p,s) (Preferred for Accounting)

Use `NUMERIC(p,s)` or `DECIMAL(p,s)` where precision (p) and scale (s) are chosen based on domain requirements.

**Do not define a universal precision/scale as a global database rule.** The values below are examples only.

```sql
-- Example for general accounting (adjust p,s per domain)
amount NUMERIC(19,4) NOT NULL,
currency CHAR(3) NOT NULL DEFAULT 'NGN',
```

**Example precision/scale combinations by domain:**

| Domain | Example NUMERIC(p,s) | Rationale |
|--------|---------------------|-----------|
| General accounting | NUMERIC(19,4) | Up to 999 trillion, 4 decimal places |
| High-precision accounting | NUMERIC(25,8) | Crypto, forex, micro-payments |
| Tax calculations | NUMERIC(15,6) | Higher precision for rates |
| Exchange rates | NUMERIC(20,10) | High precision for FX rates |

```sql
-- PostgreSQL - precision/scale chosen per column
amount NUMERIC(19,4) NOT NULL,
currency CHAR(3) NOT NULL DEFAULT 'NGN',
```

```php
// Laravel - scale matches column definition
protected $casts = [
    'amount' => 'decimal:4',  // matches NUMERIC(*,4)
];
```

**Key principle:** Choose `p` (precision, total digits) and `s` (scale, decimal places) based on the specific column's domain requirements. Do not impose a single `NUMERIC(19,4)` universally.

## Currency Codes

- Use **ISO 4217** three-letter codes
- Store as `CHAR(3)` (fixed length, indexed well)
- Default: `NGN` (Nigerian Naira)

```sql
currency CHAR(3) NOT NULL DEFAULT 'NGN',
```

Supported initially: `NGN`, `USD`, `EUR`, `GBP`
Extensible for future currencies.

## Conversion & Display

### Minor Units → Major Units

```php
/**
 * Convert minor units to major units for DISPLAY PURPOSES ONLY.
 * Do not use for authoritative financial calculations.
 */
function minorToMajorForDisplay(int $minor, string $currency = 'NGN'): float {
    $divisor = in_array($currency, ['JPY', 'KRW']) ? 1 : 100;
    return $minor / $divisor;
}
```

### Display Formatting

```php
/**
 * Format minor units as a localized currency string for DISPLAY PURPOSES ONLY.
 * Uses floating point for formatting only — do not use for calculations.
 */
function formatMoney(int $minorUnits, string $currency = 'NGN', string $locale = 'en_NG'): string {
    $divisor = in_array($currency, ['JPY', 'KRW']) ? 1 : 100;
    $amount = $minorUnits / $divisor;

    return NumberFormatter::create($locale, NumberFormatter::CURRENCY)
        ->formatCurrency($amount, $currency);
}
```

// ₦1,500.00
// $99.99
```

## Schema Examples

### Transactional (minor units)
```sql
CREATE TABLE sale_items (
    ...
    quantity INTEGER NOT NULL,
    unit_price_minor BIGINT NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    line_total_minor BIGINT GENERATED ALWAYS AS (quantity * unit_price_minor) STORED,
);
```

### Accounting (NUMERIC(p,s) - precision/scale per column)
```sql
CREATE TABLE journal_entries (
    ...
    amount NUMERIC(19,4) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    ...
);
```

```sql
CREATE TABLE exchange_rates (
    ...
    rate NUMERIC(20,10) NOT NULL,  -- Higher precision for FX
    ...
);
```

## Do NOT Use

| Type | Problem |
|------|---------|
| `FLOAT`, `REAL`, `DOUBLE PRECISION` | Binary floating-point = rounding errors |
| `FLOAT` without precision | Undefined precision |
| `MONEY` type | Locale-dependent, limited precision |
| `VARCHAR` for amounts | No math, no constraints |
