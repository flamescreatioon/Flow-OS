import { render, screen, fireEvent, act } from '@testing-library/react';
import { ProductForm } from '@/features/catalogue/product-form';
import * as catalogueService from '@/features/catalogue/service';

jest.mock('@/features/catalogue/service', () => ({
  createProduct: jest.fn(),
  updateProduct: jest.fn(),
}));

const createProductMock = jest.mocked(catalogueService.createProduct);
const updateProductMock = jest.mocked(catalogueService.updateProduct);

const units = [
  {
    id: 'unit-1',
    organization_id: 'org-1',
    code: 'pcs',
    name: 'Pieces',
    precision: 0,
    created_at: '2026-10-01T00:00:00Z',
    updated_at: '2026-10-01T00:00:00Z',
  },
];

const categories = [
  {
    id: 'cat-1',
    organization_id: 'org-1',
    name: 'Beverages',
    description: null,
    created_at: '2026-10-01T00:00:00Z',
    updated_at: '2026-10-01T00:00:00Z',
  },
];

const existingProduct = {
  id: 'product-1',
  organization_id: 'org-1',
  name: 'Milo 500g',
  sku: 'MILO-500',
  type: 'stocked' as const,
  price: '2500.0000',
  tax_code: null,
  unit_of_measure: units[0],
  category: categories[0],
  archived_at: null,
  created_at: '2026-10-01T00:00:00Z',
  updated_at: '2026-10-01T00:00:00Z',
};

function typeInto(input: HTMLElement, value: string): void {
  fireEvent.change(input, { target: { value } });
}

describe('ProductForm', () => {
  beforeEach(() => {
    createProductMock.mockReset();
    updateProductMock.mockReset();
  });

  it('rejects a price with more than 4 decimal places before calling the API', async () => {
    render(
      <ProductForm
        organizationId="org-1"
        units={units}
        categories={categories}
        onCreated={jest.fn()}
        onUpdated={jest.fn()}
      />,
    );

    typeInto(screen.getByLabelText('Name'), 'Milo 500g');
    typeInto(screen.getByLabelText('Price'), '2500.12345');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create product' }));
    });

    expect(screen.getByText(/at most 4 decimal places/)).toBeInTheDocument();
    expect(createProductMock).not.toHaveBeenCalled();
  });

  it('creates a product with a canonical decimal price', async () => {
    createProductMock.mockResolvedValue(existingProduct);
    const onCreated = jest.fn();

    render(
      <ProductForm
        organizationId="org-1"
        units={units}
        categories={categories}
        onCreated={onCreated}
        onUpdated={jest.fn()}
      />,
    );

    typeInto(screen.getByLabelText('Name'), 'Milo 500g');
    typeInto(screen.getByLabelText('Price'), '2500.50');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create product' }));
    });

    expect(createProductMock).toHaveBeenCalledWith('org-1', {
      name: 'Milo 500g',
      sku: null,
      type: 'stocked',
      price: '2500.50',
      unit_of_measure_id: 'unit-1',
      category_id: null,
    });
    expect(onCreated).toHaveBeenCalledWith(existingProduct);
  });

  it('edits an existing product through the update endpoint', async () => {
    updateProductMock.mockResolvedValue({
      ...existingProduct,
      name: 'Milo 1kg',
      price: '4800.0000',
    });
    const onUpdated = jest.fn();

    render(
      <ProductForm
        organizationId="org-1"
        units={units}
        categories={categories}
        product={existingProduct}
        onCreated={jest.fn()}
        onUpdated={onUpdated}
        onCancelEdit={jest.fn()}
      />,
    );

    expect(screen.getByLabelText('Name')).toHaveValue('Milo 500g');

    typeInto(screen.getByLabelText('Name'), 'Milo 1kg');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Save changes' }));
    });

    expect(updateProductMock).toHaveBeenCalledWith(
      'org-1',
      'product-1',
      expect.objectContaining({ name: 'Milo 1kg' }),
    );
    expect(createProductMock).not.toHaveBeenCalled();
    expect(onUpdated).toHaveBeenCalledWith(
      expect.objectContaining({ name: 'Milo 1kg' }),
    );
  });
});
