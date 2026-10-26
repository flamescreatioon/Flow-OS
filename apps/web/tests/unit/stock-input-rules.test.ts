import {
  acceptsSignedQuantity,
  validateCountedQuantity,
  validateMovementQuantity,
  validateTransferQuantity,
} from '@/features/inventory/stock-input-rules';

describe('validateMovementQuantity', () => {
  it('accepts a magnitude for a fixed-direction movement', () => {
    expect(validateMovementQuantity('receipt', '12.5')).toBeNull();
    expect(validateMovementQuantity('sale', '3')).toBeNull();
    expect(validateMovementQuantity('opening', '100.000')).toBeNull();
    expect(validateMovementQuantity('return', '0.125')).toBeNull();
  });

  it('rejects a signed quantity for a fixed-direction movement', () => {
    // The server owns the direction; a receipt that reduces stock is not a
    // thing a client can state.
    expect(validateMovementQuantity('receipt', '-12.5')).toMatch(/magnitude/);
    expect(validateMovementQuantity('sale', '-3')).toMatch(/magnitude/);
  });

  it('accepts a signed quantity only for an adjustment', () => {
    expect(validateMovementQuantity('adjustment', '-12.5')).toBeNull();
    expect(validateMovementQuantity('adjustment', '+5')).toMatch(/decimal/);
  });

  it('rejects zero for every recordable movement type', () => {
    for (const type of ['opening', 'receipt', 'sale', 'return', 'adjustment'] as const) {
      expect(validateMovementQuantity(type, '0')).toMatch(/not be zero/);
      expect(validateMovementQuantity(type, '0.000')).toMatch(/not be zero/);
    }
  });

  it('rejects non-canonical decimals', () => {
    expect(validateMovementQuantity('receipt', '12.5001')).toMatch(/3 places/);
    expect(validateMovementQuantity('receipt', '1,200')).toMatch(/decimal/);
    expect(validateMovementQuantity('receipt', '.5')).toMatch(/decimal/);
  });
});

describe('validateCountedQuantity', () => {
  it('accepts a non-negative observation within the unit precision', () => {
    expect(validateCountedQuantity('40', 0)).toBeNull();
    expect(validateCountedQuantity('40.5', 1)).toBeNull();
    expect(validateCountedQuantity('40.500', 1)).toBeNull();
    expect(validateCountedQuantity('0', 3)).toBeNull();
  });

  it('rejects a negative count', () => {
    expect(validateCountedQuantity('-1', 0)).toMatch(/not be negative/);
  });

  it('rejects more precision than the unit allows', () => {
    expect(validateCountedQuantity('40.25', 1)).toMatch(/1 decimal place/);
    expect(validateCountedQuantity('40.255', 2)).toMatch(/2 decimal places/);
  });

  it('ignores trailing zeros when checking precision, like the server', () => {
    expect(validateCountedQuantity('40.250', 2)).toBeNull();
    expect(validateCountedQuantity('40.500', 1)).toBeNull();
  });
});

describe('validateTransferQuantity', () => {
  it('accepts a positive magnitude', () => {
    expect(validateTransferQuantity('12.5')).toBeNull();
  });

  it('rejects signed and zero quantities', () => {
    expect(validateTransferQuantity('-12.5')).toMatch(/magnitude/);
    expect(validateTransferQuantity('0')).toMatch(/not be zero/);
  });
});

describe('acceptsSignedQuantity', () => {
  it('is true only for adjustments', () => {
    expect(acceptsSignedQuantity('adjustment')).toBe(true);
    expect(acceptsSignedQuantity('receipt')).toBe(false);
    expect(acceptsSignedQuantity('sale')).toBe(false);
  });
});
