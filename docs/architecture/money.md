# Money Handling

## The Problem with Floats

```javascript
// JavaScript
0.1 + 0.2 // 0.30000000000000004

// PHP
0.1 + 0.2 // 0.3 (but not reliable for all values)

// Python
0.1 + 0.2 // 0.30000000000000004
```

**Never use floating-point for money.**

## Recommended Approaches

### 1. Integer Minor Units (Preferred)

Store amounts as integers in the smallest currency unit:

| Currency | Minor Unit | Example |
|----------|------------|---------|
| NGN (Naira) | Kobo | ₦1,500.00 = 150000 |
| USD (Dollar) | Cent | $99.99 = 9999 |
| EUR (Euro) | Cent | €49.95 = 4995 |
| JPY (Yen) | Yen | ¥1000 = 1000 |

```typescript
// TypeScript — Authoritative money uses integer minor units
interface Money {
  amount: number;  // Minor units (kobo for NGN, cents for USD)
  currency: 'NGN' | 'USD' | 'EUR' | 'GBP' | 'JPY';
}

/**
 * Authoritative constructor — accepts integer minor units directly.
 * Validates that amount is a safe integer within Number.MAX_SAFE_INTEGER.
 */
function createMoneyFromMinor(amountMinor: number, currency: Money['currency']): Money {
  if (!Number.isSafeInteger(amountMinor)) {
    throw new Error('amountMinor must be a safe integer');
  }
  return { amount: amountMinor, currency };
}

/**
 * Input boundary helper — parses exact decimal string to minor units.
 * Avoids floating-point inaccuracies by parsing the string directly.
 */
function createMoneyFromMajorString(amountMajor: string, currency: Money['currency']): Money {
  const divisors = { NGN: 100, USD: 100, EUR: 100, GBP: 100, JPY: 1 };
  const divisor = divisors[currency] ?? 100;
  const parts = amountMajor.split('.');
  const integerPart = BigInt(parts[0] || '0');
  const fractionalPart = (parts[1] || '').padEnd(2, '0').slice(0, 2);
  const fractionalPartBigInt = BigInt(fractionalPart);
  const amountMinorBigInt = integerPart * BigInt(divisor) + fractionalPartBigInt;
  if (amountMinorBigInt > BigInt(Number.MAX_SAFE_INTEGER) || amountMinorBigInt < BigInt(Number.MIN_SAFE_INTEGER)) {
    throw new Error('Amount exceeds safe integer range');
  }
  return { amount: Number(amountMinorBigInt), currency };
}

/**
 * DISPLAY ONLY — converts minor units to localized currency string.
 * Uses floating point for formatting only; do not use for calculations.
 */
function formatMoney(money: Money, locale = 'en-NG'): string {
  const divisor = money.currency === 'JPY' ? 1 : 100;
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: money.currency,
    minimumFractionDigits: money.currency === 'JPY' ? 0 : 2,
  }).format(money.amount / divisor);
}

// Usage
const price = createMoneyFromMinor(150050, 'NGN'); // 150050 kobo = ₦1,500.50
formatMoney(price); // "₦1,500.50"

// For user input boundary (exact decimal string parsing)
const userPrice = createMoneyFromMajorString('1500.50', 'NGN');
formatMoney(userPrice); // "₦1,500.50"
```

```php
// PHP (Laravel)
class Money {
    public function __construct(
        public readonly int $amount, // Minor units
        public readonly string $currency = 'NGN'
    ) {}

    public static function fromMajor(float $major, string $currency = 'NGN'): self {
        $divisors = ['NGN' => 100, 'USD' => 100, 'EUR' => 100, 'GBP' => 100, 'JPY' => 1];
        return new self((int) round($major * ($divisors[$currency] ?? 100)), $currency);
    }

    public function toMajor(): float {
        $divisors = ['NGN' => 100, 'USD' => 100, 'EUR' => 100, 'GBP' => 100, 'JPY' => 1];
        return $this->amount / ($divisors[$this->currency] ?? 100);
    }

    public function formatted(string $locale = 'en_NG'): string {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $this->getSymbol());
        return $formatter->formatCurrency($this->toMajor(), $this->currency);
    }

    private function getSymbol(): string {
        return match($this->currency) {
            'NGN' => '₦', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', default => $this->currency
        };
    }
}

// Usage
$price = Money::fromMajor(1500.50, 'NGN');
echo $price->formatted(); // ₦1,500.50
```

```csharp
// C# (.NET)
public readonly record struct Money(long Amount, string Currency = "NGN") {
    private static readonly Dictionary<string, int> Divisors = new() {
        ["NGN"] = 100, ["USD"] = 100, ["EUR"] = 100, ["GBP"] = 100, ["JPY"] = 1
    };

    public static Money FromMajor(decimal major, string currency = "NGN") {
        var divisor = Divisors.GetValueOrDefault(currency, 100);
        return new Money((long)Math.Round(major * divisor), currency);
    }

    public decimal ToMajor() => Amount / (decimal)Divisors.GetValueOrDefault(Currency, 100);

    public string Format(string locale = "en-NG") {
        var culture = new CultureInfo(locale);
        var nfi = (NumberFormatInfo)culture.NumberFormat.Clone();
        nfi.CurrencySymbol = Currency switch {
            "NGN" => "₦", "USD" => "$", "EUR" => "€", "GBP" => "£", _ => Currency
        };
        return ToMajor().ToString("C", nfi);
    }
}

// Usage
var price = Money.FromMajor(1500.50m, "NGN");
Console.WriteLine(price.Format()); // ₦1,500.50
```

```dart
// Dart (Flutter) — DO NOT USE THIS PATTERN FOR AUTHORITATIVE MONEY
// This example demonstrates a DISPLAY-ONLY pattern using double for formatting.
// Authoritative money must use integer minor units (int).
// See docs/finance/README.md and docs/database/money.md for approved patterns.

class Money {
  final int amount; // Minor units (authoritative)
  final String currency;

  const Money(this.amount, {this.currency = 'NGN'});

  // DISPLAY-ONLY: Convert minor units to major for display formatting only.
  // Do not use for calculations or authoritative values.
  double toMajorForDisplay() {
    const divisors = {'NGN': 100, 'USD': 100, 'EUR': 100, 'GBP': 100, 'JPY': 1};
    return amount / (divisors[currency] ?? 100);
  }

  String format({String locale = 'en_NG'}) {
    final formatter = NumberFormat.currency(
      locale: locale,
      symbol: _getSymbol(),
      decimalDigits: currency == 'JPY' ? 0 : 2,
    );
    return formatter.format(toMajorForDisplay());
  }

  String _getSymbol() {
    switch (currency) {
      case 'NGN': return '₦';
      case 'USD': return r'$';
      case 'EUR': return '€';
      case 'GBP': return '£';
      default: return currency;
    }
  }
}

// Usage
final price = Money(150050, currency: 'NGN'); // 150050 kobo = ₦1,500.50
print(price.format()); // ₦1,500.50
```

### 2. PostgreSQL NUMERIC/DECIMAL

For accounting precision:

```sql
-- Exact precision, no rounding errors
CREATE TABLE transactions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    amount NUMERIC(19, 4) NOT NULL,  -- 19 digits, 4 decimal places
    currency VARCHAR(3) NOT NULL,
    ...
);

-- NUMERIC(19,4) supports up to 999,999,999,999,999.9999
-- Enough for any business transaction
```

```php
// Laravel migration
$table->decimal('amount', 19, 4)->default(0);

// Model cast
protected $casts = [
    'amount' => 'decimal:4',
];
```

## Arithmetic Operations

### Integer Minor Units

```typescript
// Addition
function add(a: Money, b: Money): Money {
  if (a.currency !== b.currency) throw new Error('Currency mismatch');
  return { amount: a.amount + b.amount, currency: a.currency };
}

// Multiplication by quantity
function multiply(money: Money, quantity: number): Money {
  return { amount: money.amount * quantity, currency: money.currency };
}

// Division (e.g., split bill)
function divide(money: Money, divisor: number): Money {
  return { amount: Math.floor(money.amount / divisor), currency: money.currency };
}
```

### PostgreSQL

```sql
-- Exact arithmetic
SELECT amount + 10000 FROM transactions WHERE id = '...';
SELECT amount * quantity FROM line_items;
SELECT amount / 3 FROM transactions; -- Returns NUMERIC
```

## Multi-Currency Support

### Exchange Rates

```typescript
interface ExchangeRate {
  from: CurrencyCode;
  to: CurrencyCode;
  rate: number; // 1 from = rate to
  date: string; // Rate date
}

/**
 * NON-AUTHORITATIVE DISPLAY ESTIMATE — uses floating-point rate.
 * For authoritative financial conversions, use exact fixed-point rate arithmetic
 * with NUMERIC(19,8) or higher precision per domain requirements.
 * This function is for UI display estimates only.
 */
function convertDisplayEstimate(money: Money, rate: ExchangeRate): Money {
  if (money.currency !== rate.from) throw new Error('Currency mismatch');
  return {
    amount: Math.round(money.amount * rate.rate),
    currency: rate.to,
  };
}
```

### Storage

```sql
CREATE TABLE exchange_rates (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    rate NUMERIC(19, 8) NOT NULL,
    rate_date DATE NOT NULL,
    source VARCHAR(50), -- 'CBN', 'exchangerate.host', etc.
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (from_currency, to_currency, rate_date)
);
```

## Default Currency: NGN

```typescript
// Configuration
const DEFAULT_CURRENCY = 'NGN';
const DEFAULT_LOCALE = 'en-NG';
const DEFAULT_TIMEZONE = 'Africa/Lagos';
```

## Rounding Rules

| Operation | Rule |
|-----------|------|
| Division | Round down (floor) |
| Tax calculation | Round half up |
| Display | Locale-aware formatting |
| Storage | Exact minor units |

## Testing Money

```typescript
// Property-based tests
test('addition is commutative', () => {
  const a = createMoneyFromMinor(10050, 'NGN'); // 10050 kobo = ₦100.50
  const b = createMoneyFromMinor(20025, 'NGN'); // 20025 kobo = ₦200.25
  expect(add(a, b).amount).toBe(add(b, a).amount);
});

test('round-trip minor/major', () => {
  const minor = 123456; // 123456 kobo = ₦1,234.56
  const money = createMoneyFromMinor(minor, 'NGN');
  expect(money.amount).toBe(minor);
});
```

## Summary

| Approach | Use Case |
|----------|----------|
| Integer minor units | General purpose, high performance |
| PostgreSQL NUMERIC | Accounting, regulatory compliance |
| Decimal type (C#) | Financial calculations in .NET |

**Default**: Integer minor units for application code. Database storage uses BIGINT minor units or field-specific NUMERIC(p,s)/DECIMAL(p,s) chosen per column. No universal NUMERIC(19,4) rule.