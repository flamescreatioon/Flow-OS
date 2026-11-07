import { formatMoney } from '@/utils/money';

describe('formatMoney', () => {
  it('formats a decimal string as a currency amount for display', () => {
    expect(formatMoney('3225.0000', 'NGN')).toBe('₦3,225.00');
    expect(formatMoney('12502.5', 'NGN')).toBe('₦12,502.50');
    expect(formatMoney('0.0000', 'NGN')).toBe('₦0.00');
  });

  it('formats negative amounts', () => {
    expect(formatMoney('-1250.5', 'NGN')).toBe('-₦1,250.50');
  });

  it('falls back to the raw value for malformed input', () => {
    expect(formatMoney('not-a-number', 'NGN')).toBe('not-a-number');
    expect(formatMoney('', 'NGN')).toBe('');
  });

  it('falls back to a suffixed value for unknown currencies', () => {
    expect(formatMoney('1500.00', 'XAU')).toBe('1500.00 XAU');
  });

  it('is case-insensitive on the currency code', () => {
    expect(formatMoney('10.00', 'ngn')).toBe('₦10.00');
  });
});
