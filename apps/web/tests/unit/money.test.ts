import { createMoneyFromMajorString } from '../../../../packages/types';

describe('createMoneyFromMajorString', () => {
  test.each([
    ['NGN', '1500.50', 150050],
    ['NGN', '-1500.50', -150050],
    ['NGN', '0.01', 1],
    ['JPY', '1500', 1500],
  ])('parses %s %s into exact minor units', (currency, input, expectedAmount) => {
    expect(createMoneyFromMajorString(input, currency)).toEqual({
      amount: expectedAmount,
      currency,
    });
  });

  test.each([
    ['NGN', '12.999'],
    ['JPY', '1500.00'],
    ['NGN', '.50'],
    ['NGN', '1,200.50'],
  ])('rejects non-canonical or over-precise %s input %s', (currency, input) => {
    expect(() => createMoneyFromMajorString(input, currency)).toThrow();
  });
});
