import { render, screen, fireEvent, act } from '@testing-library/react';
import { ApiError } from '@/lib/api';
import { SupplierForm } from '@/features/purchasing/supplier-form';
import * as purchasingService from '@/features/purchasing/service';

jest.mock('@/features/purchasing/service', () => ({
  createSupplier: jest.fn(),
  updateSupplier: jest.fn(),
}));

const createSupplierMock = jest.mocked(purchasingService.createSupplier);
const updateSupplierMock = jest.mocked(purchasingService.updateSupplier);

const existingSupplier = {
  id: 'supplier-1',
  organization_id: 'org-1',
  name: 'Lagos Beverage Supply',
  email: 'sales@lagosbev.example',
  phone: '0803 000 0001',
  address: null,
  archived_at: null,
  created_at: '2026-11-01T00:00:00Z',
  updated_at: '2026-11-01T00:00:00Z',
};

function typeInto(input: HTMLElement, value: string): void {
  fireEvent.change(input, { target: { value } });
}

describe('SupplierForm', () => {
  beforeEach(() => {
    createSupplierMock.mockReset();
    updateSupplierMock.mockReset();
  });

  it('requires a name before calling the API', async () => {
    render(
      <SupplierForm organizationId="org-1" onCreated={jest.fn()} onUpdated={jest.fn()} />,
    );

    // A whitespace name satisfies the HTML required check but not the
    // trimmed server rule.
    typeInto(screen.getByLabelText('Name'), '   ');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create supplier' }));
    });

    expect(screen.getByText(/supplier name is required/i)).toBeInTheDocument();
    expect(createSupplierMock).not.toHaveBeenCalled();
  });

  it('creates a supplier with contact details', async () => {
    createSupplierMock.mockResolvedValue(existingSupplier);

    render(
      <SupplierForm organizationId="org-1" onCreated={jest.fn()} onUpdated={jest.fn()} />,
    );

    typeInto(screen.getByLabelText('Name'), 'Lagos Beverage Supply');
    typeInto(screen.getByLabelText('Email (optional)'), 'sales@lagosbev.example');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create supplier' }));
    });

    expect(createSupplierMock).toHaveBeenCalledWith('org-1', {
      name: 'Lagos Beverage Supply',
      email: 'sales@lagosbev.example',
      phone: null,
      address: null,
    });
  });

  it('renders an API validation error without clearing the form', async () => {
    createSupplierMock.mockRejectedValue(
      new ApiError(422, 'VALIDATION_ERROR', 'The given data was invalid.', {
        email: ['The email is not valid.'],
      }),
    );

    render(
      <SupplierForm organizationId="org-1" onCreated={jest.fn()} onUpdated={jest.fn()} />,
    );

    typeInto(screen.getByLabelText('Name'), 'Duplicate Trading');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create supplier' }));
    });

    expect(await screen.findByText('The email is not valid.')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Create supplier' })).toBeEnabled();
  });

  it('edits an existing supplier through updateSupplier', async () => {
    updateSupplierMock.mockResolvedValue(existingSupplier);
    const onUpdated = jest.fn();

    render(
      <SupplierForm
        organizationId="org-1"
        supplier={existingSupplier}
        onCreated={jest.fn()}
        onUpdated={onUpdated}
        onCancelEdit={jest.fn()}
      />,
    );

    typeInto(screen.getByLabelText('Phone (optional)'), '0803 000 0002');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Save changes' }));
    });

    expect(updateSupplierMock).toHaveBeenCalledWith('org-1', 'supplier-1', {
      name: 'Lagos Beverage Supply',
      email: 'sales@lagosbev.example',
      phone: '0803 000 0002',
      address: null,
    });
    expect(onUpdated).toHaveBeenCalledWith(existingSupplier);
  });
});
