import { render, screen, fireEvent, act } from '@testing-library/react';
import { ApiError } from '@/lib/api';
import { CustomerForm } from '@/features/invoicing/customer-form';
import * as invoicingService from '@/features/invoicing/service';

jest.mock('@/features/invoicing/service', () => ({
  createCustomer: jest.fn(),
  updateCustomer: jest.fn(),
}));

const createCustomerMock = jest.mocked(invoicingService.createCustomer);
const updateCustomerMock = jest.mocked(invoicingService.updateCustomer);

const existingCustomer = {
  id: 'customer-1',
  organization_id: 'org-1',
  name: 'Abuja Retail Group',
  email: 'orders@abujaretail.example',
  phone: '0803 111 2222',
  address: null,
  archived_at: null,
  created_at: '2026-11-01T00:00:00Z',
  updated_at: '2026-11-01T00:00:00Z',
};

function typeInto(input: HTMLElement, value: string): void {
  fireEvent.change(input, { target: { value } });
}

describe('CustomerForm', () => {
  beforeEach(() => {
    createCustomerMock.mockReset();
    updateCustomerMock.mockReset();
  });

  it('requires a name before calling the API', async () => {
    render(
      <CustomerForm organizationId="org-1" onCreated={jest.fn()} onUpdated={jest.fn()} />,
    );

    // A whitespace name satisfies the HTML required check but not the
    // trimmed server rule.
    typeInto(screen.getByLabelText('Name'), '   ');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create customer' }));
    });

    expect(screen.getByText(/customer name is required/i)).toBeInTheDocument();
    expect(createCustomerMock).not.toHaveBeenCalled();
  });

  it('creates a customer with contact details', async () => {
    createCustomerMock.mockResolvedValue(existingCustomer);

    render(
      <CustomerForm organizationId="org-1" onCreated={jest.fn()} onUpdated={jest.fn()} />,
    );

    typeInto(screen.getByLabelText('Name'), 'Abuja Retail Group');
    typeInto(screen.getByLabelText('Email (optional)'), 'orders@abujaretail.example');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create customer' }));
    });

    expect(createCustomerMock).toHaveBeenCalledWith('org-1', {
      name: 'Abuja Retail Group',
      email: 'orders@abujaretail.example',
      phone: null,
      address: null,
    });
  });

  it('renders an API validation error without clearing the form', async () => {
    createCustomerMock.mockRejectedValue(
      new ApiError(422, 'VALIDATION_ERROR', 'The given data was invalid.', {
        email: ['The email is not valid.'],
      }),
    );

    render(
      <CustomerForm organizationId="org-1" onCreated={jest.fn()} onUpdated={jest.fn()} />,
    );

    typeInto(screen.getByLabelText('Name'), 'Duplicate Retail');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create customer' }));
    });

    expect(await screen.findByText('The email is not valid.')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Create customer' })).toBeEnabled();
  });

  it('edits an existing customer through updateCustomer', async () => {
    updateCustomerMock.mockResolvedValue(existingCustomer);
    const onUpdated = jest.fn();

    render(
      <CustomerForm
        organizationId="org-1"
        customer={existingCustomer}
        onCreated={jest.fn()}
        onUpdated={onUpdated}
        onCancelEdit={jest.fn()}
      />,
    );

    typeInto(screen.getByLabelText('Phone (optional)'), '0803 111 3333');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Save changes' }));
    });

    expect(updateCustomerMock).toHaveBeenCalledWith('org-1', 'customer-1', {
      name: 'Abuja Retail Group',
      email: 'orders@abujaretail.example',
      phone: '0803 111 3333',
      address: null,
    });
    expect(onUpdated).toHaveBeenCalledWith(existingCustomer);
  });
});
