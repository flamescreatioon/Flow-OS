import { render, screen, fireEvent, act } from '@testing-library/react';
import { RecordReceiptForm } from '@/features/purchasing/record-receipt-form';
import * as purchasingService from '@/features/purchasing/service';
import type { GoodsReceiptLine, Product, InventoryLocation, Supplier } from '@/types';

jest.mock('@/features/purchasing/service', () => ({
  recordGoodsReceipt: jest.fn(),
}));

const recordGoodsReceiptMock = jest.mocked(purchasingService.recordGoodsReceipt);

const products: Product[] = [
  {
    id: 'product-1',
    organization_id: 'org-1',
    name: 'Milo 500g',
    sku: 'MILO-500',
    type: 'stocked',
    tax_code: null,
    price: '2500.0000',
    unit_of_measure: null,
    category: null,
    archived_at: null,
    created_at: '2026-11-01T00:00:00Z',
    updated_at: '2026-11-01T00:00:00Z',
  },
];

const locations: InventoryLocation[] = [
  {
    id: 'location-1',
    organization_id: 'org-1',
    branch_id: null,
    code: 'MAIN',
    name: 'Main Store',
    archived_at: null,
    created_at: '2026-11-01T00:00:00Z',
    updated_at: '2026-11-01T00:00:00Z',
  },
];

const suppliers: Supplier[] = [
  {
    id: 'supplier-1',
    organization_id: 'org-1',
    name: 'Lagos Beverage Supply',
    email: null,
    phone: null,
    address: null,
    archived_at: null,
    created_at: '2026-11-01T00:00:00Z',
    updated_at: '2026-11-01T00:00:00Z',
  },
];

const recordedLines: GoodsReceiptLine[] = [
  {
    id: 'line-1',
    goods_receipt_id: 'receipt-1',
    product_id: 'product-1',
    quantity: '10.000',
    unit_cost: '1500.0000',
    line_total: '15000.0000',
  },
];

const recordedReceipt = {
  id: 'receipt-1',
  organization_id: 'org-1',
  branch_id: null,
  supplier_id: 'supplier-1',
  supplier_name: 'Lagos Beverage Supply',
  location_id: 'location-1',
  receipt_number: 1,
  document_number: 'GRN-000001',
  status: 'recorded' as const,
  received_on: '2026-11-07',
  note: null,
  total_cost: '15000.0000',
  lines: recordedLines,
  created_at: '2026-11-07T00:00:00Z',
  updated_at: '2026-11-07T00:00:00Z',
};

function typeInto(input: HTMLElement, value: string): void {
  fireEvent.change(input, { target: { value } });
}

function renderForm(onRecorded: (receipt: unknown) => void = jest.fn()): void {
  render(
    <RecordReceiptForm
      organizationId="org-1"
      products={products}
      locations={locations}
      suppliers={suppliers}
      onRecorded={onRecorded}
    />,
  );
}

async function fillFirstLine(quantity: string, unitCost: string): Promise<void> {
  fireEvent.change(screen.getByLabelText('Product (line 1)'), { target: { value: 'product-1' } });
  typeInto(screen.getByLabelText('Quantity (line 1)'), quantity);
  typeInto(screen.getByLabelText('Unit cost (line 1)'), unitCost);
}

describe('RecordReceiptForm', () => {
  beforeEach(() => {
    recordGoodsReceiptMock.mockReset();
  });

  it('validates the quantity before calling the API', async () => {
    renderForm();

    await fillFirstLine('abc', '1500.0000');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Record receipt' }));
    });

    expect(screen.getByText(/at most 3 places/i)).toBeInTheDocument();
    expect(recordGoodsReceiptMock).not.toHaveBeenCalled();
  });

  it('rejects a negative unit cost before calling the API', async () => {
    renderForm();

    await fillFirstLine('10.000', '-5.0000');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Record receipt' }));
    });

    expect(screen.getByText(/unit cost must not be negative/i)).toBeInTheDocument();
    expect(recordGoodsReceiptMock).not.toHaveBeenCalled();
  });

  it('rejects a line without a product before calling the API', async () => {
    renderForm();

    typeInto(screen.getByLabelText('Quantity (line 1)'), '10.000');
    typeInto(screen.getByLabelText('Unit cost (line 1)'), '1500.0000');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Record receipt' }));
    });

    expect(screen.getByText(/choose a product/i)).toBeInTheDocument();
    expect(recordGoodsReceiptMock).not.toHaveBeenCalled();
  });

  it('records a receipt with supplier and lines, then resets the form', async () => {
    const onRecorded = jest.fn();
    recordGoodsReceiptMock.mockResolvedValue(recordedReceipt);
    renderForm(onRecorded);

    fireEvent.change(screen.getByLabelText('Supplier (optional)'), {
      target: { value: 'supplier-1' },
    });
    fireEvent.change(screen.getByLabelText('Location'), { target: { value: 'location-1' } });
    await fillFirstLine('10.000', '1500.0000');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Record receipt' }));
    });

    expect(recordGoodsReceiptMock).toHaveBeenCalledTimes(1);
    const [organizationId, input] = recordGoodsReceiptMock.mock.calls[0];
    expect(organizationId).toBe('org-1');
    expect(input.supplier_id).toBe('supplier-1');
    expect(input.location_id).toBe('location-1');
    expect(input.lines).toEqual([
      { product_id: 'product-1', quantity: '10.000', unit_cost: '1500.0000' },
    ]);
    expect(onRecorded).toHaveBeenCalledWith(recordedReceipt);

    // The lines reset for the next receipt.
    expect(screen.getByLabelText('Quantity (line 1)')).toHaveValue('');
  });

  it('adds a line row and submits both lines', async () => {
    renderForm();

    fireEvent.click(screen.getByRole('button', { name: 'Add line' }));
    expect(screen.getByLabelText('Quantity (line 2)')).toBeInTheDocument();

    await fillFirstLine('5.000', '100.0000');
    typeInto(screen.getByLabelText('Quantity (line 2)'), '2.000');
    typeInto(screen.getByLabelText('Unit cost (line 2)'), '50.0000');
    fireEvent.change(screen.getByLabelText('Product (line 2)'), { target: { value: 'product-1' } });

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Record receipt' }));
    });

    expect(recordGoodsReceiptMock.mock.calls[0][1].lines).toHaveLength(2);
  });

  it('removes a line row', () => {
    renderForm();

    fireEvent.click(screen.getByRole('button', { name: 'Add line' }));
    expect(screen.getAllByRole('button', { name: 'Remove line' })).toHaveLength(2);

    fireEvent.click(screen.getAllByRole('button', { name: 'Remove line' })[0]);
    expect(screen.getByLabelText('Quantity (line 1)')).toBeInTheDocument();
    expect(screen.queryByLabelText('Quantity (line 2)')).not.toBeInTheDocument();

    // A single line cannot be removed.
    expect(screen.queryByRole('button', { name: 'Remove line' })).not.toBeInTheDocument();
  });
});
