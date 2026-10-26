import {
  formatDecimalString,
  isCanonicalDecimal,
} from '@/utils/decimal';

describe('isCanonicalDecimal', () => {
  test.each([
    ['12', 3],
    ['12.5', 3],
    ['12.500', 3],
    ['-12.5', 3],
    ['0', 0],
    ['1500.0000', 4],
    ['-0.000', 3],
  ])('accepts %s at scale %i', (value, maxScale) => {
    expect(isCanonicalDecimal(value, maxScale)).toBe(true);
  });

  test.each([
    // More fractional digits than the scale allows.
    ['12.5001', 3],
    ['1.50', 1],
    // Leading plus sign.
    ['+12.5', 3],
    // Missing integer part or trailing point.
    ['.5', 3],
    ['12.', 3],
    // Non-numeric characters and separators.
    ['1,200.50', 3],
    ['abc', 3],
    ['', 3],
    ['12-5', 3],
  ])('rejects %s', (value, maxScale) => {
    expect(isCanonicalDecimal(value, maxScale)).toBe(false);
  });
});

describe('formatDecimalString', () => {
  test.each([
    ['12.500', '12.5'],
    ['12.000', '12'],
    ['12', '12'],
    ['-12.500', '-12.5'],
    ['-0.000', '0'],
    ['0.005', '0.005'],
  ])('formats %s for display as %s', (value, expected) => {
    expect(formatDecimalString(value)).toBe(expected);
  });
});
