import { render, screen, fireEvent, act } from '@testing-library/react';
import { InvoiceForm } from '@/features/invoicing/invoice-form';
import * as invoicingService from '@/features/invoicing/service';
import type { InvoiceLine, Product } from '@/types';

jest.mock('@/features/invoicing/service', () => ({
  createInvoice: jest.fn(),
}));

const createInvoiceMock = jest.mocked(invoicingService.createInvoice);

const products: Product[] = [
  {
    id: 'product-1',
    organization_id: 'org-1',
    name: 'Milo 500g',
    sku: 'MILO-500',
    type: 'stocked',
    price: '2500.0000',
    tax_code: null,
    unit_of_measure: null,
    category: null,
    archived_at: null,
    created_at: '2026-11-01T00:00:00Z',
    updated_at: '2026-11-01T00:00:00Z',
  },
];

const recordedLines: InvoiceLine[] = [
  {
    id: 'line-1',
    invoice_id: 'invoice-1',
    product_id: null,
    description: 'Milo 500g carton',
    quantity: '2.000',
    unit_price: '1500.0000',
    discount: '0.0000',
    tax_amount: '0.0000',
    line_total: '3000.0000',
  },
];

const recordedInvoice = {
  id: 'invoice-1',
  organization_id: 'org-1',
  branch_id: null,
  customer_id: null,
  customer_name: null,
  invoice_number: null,
  document_number: null,
  status: 'draft' as const,
  issued_at: null,
  voided_at: null,
  due_on: null,
  currency: 'NGN',
  tax_rate_percent: null,
  tax_mode: null,
  subtotal: '3000.0000',
  discount_total: '0.0000',
  tax_total: '0.0000',
  grand_total: '3000.0000',
  note: null,
  lines: recordedLines,
  created_at: '2026-11-07T00:00:00Z',
  updated_at: '2026-11-07T00:00:00Z',
};

function typeInto(input: HTMLElement, value: string): void {
  fireEvent.change(input, { target: { value } });
}

function fillFirstLine(description = 'Milo 500g carton', quantity = '2.000', price = '1500.0000'): void {
  typeInto(screen.getByLabelText('Description (line 1)'), description);
  typeInto(screen.getByLabelText('Quantity (line 1)'), quantity);
  typeInto(screen.getByLabelText('Unit price (line 1)'), price);
}

describe('InvoiceForm', () => {
  beforeEach(() => {
    createInvoiceMock.mockReset();
  });

  it('validates line inputs before calling the API', async () => {
    render(
      <InvoiceForm
        organizationId="org-1"
        products={products}
        customers={[]}
        onCreated={jest.fn()}
      />,
    );

    typeInto(screen.getByLabelText('Description (line 1)'), 'X');
    typeInto(screen.getByLabelText('Quantity (line 1)'), 'abc');
    typeInto(screen.getByLabelText('Unit price (line 1)'), '1500.12345');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create draft invoice' }));
    });

    expect(screen.getByText(/enter a positive quantity/i)).toBeInTheDocument();
    expect(screen.getByText(/at most 4 decimal places/i)).toBeInTheDocument();
    expect(createInvoiceMock).not.toHaveBeenCalled();
  });

  it('creates a draft with free-text lines, then resets the form', async () => {
    const onCreated = jest.fn();
    createInvoiceMock.mockResolvedValue(recordedInvoice);

    render(
      <InvoiceForm
        organizationId="org-1"
        products={products}
        customers={[]}
        onCreated={onCreated}
      />,
    );

    fillFirstLine();

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create draft invoice' }));
    });

    expect(createInvoiceMock).toHaveBeenCalledTimes(1);
    const [organizationId, input] = createInvoiceMock.mock.calls[0];
    expect(organizationId).toBe('org-1');
    expect(input.customer_id).toBeNull();
    expect(input.lines).toEqual([
      {
        product_id: null,
        description: 'Milo 500g carton',
        quantity: '2.000',
        unit_price: '1500.0000',
        discount: null,
      },
    ]);
    expect(onCreated).toHaveBeenCalledWith(recordedInvoice);
    expect(screen.getByLabelText('Quantity (line 1)')).toHaveValue('');
  });

  it('carries the selected product id and discount', async () => {
    createInvoiceMock.mockResolvedValue(recordedInvoice);

    render(
      <InvoiceForm
        organizationId="org-1"
        products={products}
        customers={[]}
        onCreated={jest.fn()}
      />,
    );

    fireEvent.change(screen.getByLabelText('Product (optional)'), {
      target: { value: 'product-1' },
    });
    fillFirstLine();
    typeInto(screen.getByLabelText('Discount (line 1)'), '250.00');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create draft invoice' }));
    });

    expect(createInvoiceMock.mock.calls[0][1].lines[0]).toEqual({
      product_id: 'product-1',
      description: 'Milo 500g carton',
      quantity: '2.000',
      unit_price: '1500.0000',
      discount: '250.00',
    });
  });

  it('carries the selected customer', async () => {
    createInvoiceMock.mockResolvedValue(recordedInvoice);
    const customers = [
      {
        id: 'customer-1',
        organization_id: 'org-1',
        name: 'Abuja Retail Group',
        email: null,
        phone: null,
        address: null,
        archived_at: null,
        created_at: '2026-11-01T00:00:00Z',
        updated_at: '2026-11-01T00:00:00Z',
      },
    ];

    render(
      <InvoiceForm
        organizationId="org-1"
        products={products}
        customers={customers}
        onCreated={jest.fn()}
      />,
    );

    fireEvent.change(screen.getByLabelText('Customer (optional)'), {
      target: { value: 'customer-1' },
    });
    fillFirstLine();

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create draft invoice' }));
    });

    expect(createInvoiceMock.mock.calls[0][1].customer_id).toBe('customer-1');
  });
});
