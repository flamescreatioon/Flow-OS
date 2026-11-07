import { render, screen, fireEvent, act } from '@testing-library/react';
import { ApiError } from '@/lib/api';
import { ExpenseForm } from '@/features/expenses/expense-form';
import * as expensesService from '@/features/expenses/service';

jest.mock('@/features/expenses/service', () => ({
  createExpense: jest.fn(),
  updateExpense: jest.fn(),
}));

const createExpenseMock = jest.mocked(expensesService.createExpense);
const updateExpenseMock = jest.mocked(expensesService.updateExpense);

const categories = [
  {
    id: 'cat-1',
    organization_id: 'org-1',
    name: 'Transport',
    description: null,
    archived_at: null,
    created_at: '2026-11-01T00:00:00Z',
    updated_at: '2026-11-01T00:00:00Z',
  },
];

const recordedExpense = {
  id: 'expense-1',
  organization_id: 'org-1',
  branch_id: null,
  expense_category_id: 'cat-1',
  expense_category: categories[0],
  expense_number: 1,
  document_number: 'EXP-000001',
  status: 'recorded' as const,
  voided_at: null,
  description: 'Fuel for delivery bike',
  amount: '1500.0000',
  spent_on: '2026-11-07',
  payee: 'Total Filling Station',
  payment_method: 'cash' as const,
  note: null,
  created_at: '2026-11-07T00:00:00Z',
  updated_at: '2026-11-07T00:00:00Z',
};

function typeInto(input: HTMLElement, value: string): void {
  fireEvent.change(input, { target: { value } });
}

describe('ExpenseForm', () => {
  beforeEach(() => {
    createExpenseMock.mockReset();
    updateExpenseMock.mockReset();
  });

  it('requires a category before submitting', () => {
    render(
      <ExpenseForm
        organizationId="org-1"
        categories={[]}
        onCreated={jest.fn()}
        onUpdated={jest.fn()}
      />,
    );

    expect(screen.getByText(/create an expense category first/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Record expense' })).not.toBeInTheDocument();
  });

  it('validates the amount before calling the API', async () => {
    render(
      <ExpenseForm
        organizationId="org-1"
        categories={categories}
        onCreated={jest.fn()}
        onUpdated={jest.fn()}
      />,
    );

    fireEvent.change(await screen.findByLabelText('Category'), {
      target: { value: 'cat-1' },
    });
    typeInto(screen.getByLabelText('Description'), 'Fuel');
    typeInto(screen.getByLabelText('Amount'), 'abc');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Record expense' }));
    });

    expect(screen.getByText(/at most 4 decimal places/i)).toBeInTheDocument();
    expect(createExpenseMock).not.toHaveBeenCalled();
  });

  it('creates an expense with the selected category and method', async () => {
    createExpenseMock.mockResolvedValue(recordedExpense);

    render(
      <ExpenseForm
        organizationId="org-1"
        categories={categories}
        onCreated={jest.fn()}
        onUpdated={jest.fn()}
      />,
    );

    fireEvent.change(await screen.findByLabelText('Category'), {
      target: { value: 'cat-1' },
    });
    typeInto(screen.getByLabelText('Description'), 'Fuel for delivery bike');
    typeInto(screen.getByLabelText('Amount'), '1500.50');
    typeInto(screen.getByLabelText('Payee (optional)'), 'Total Filling Station');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Record expense' }));
    });

    // The idempotency key defaults inside the real service, so the form
    // calls the 2-argument signature.
    expect(createExpenseMock).toHaveBeenCalledWith(
      'org-1',
      expect.objectContaining({
        expense_category_id: 'cat-1',
        description: 'Fuel for delivery bike',
        amount: '1500.50',
        payee: 'Total Filling Station',
        payment_method: 'cash',
      }),
    );
  });

  it('renders an API validation error without losing the form', async () => {
    createExpenseMock.mockRejectedValue(
      new ApiError(422, 'VALIDATION_ERROR', 'The given data was invalid.', {
        amount: ['The amount must be positive.'],
      }),
    );

    render(
      <ExpenseForm
        organizationId="org-1"
        categories={categories}
        onCreated={jest.fn()}
        onUpdated={jest.fn()}
      />,
    );

    fireEvent.change(await screen.findByLabelText('Category'), {
      target: { value: 'cat-1' },
    });
    typeInto(screen.getByLabelText('Description'), 'Fuel');
    typeInto(screen.getByLabelText('Amount'), '1500.50');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Record expense' }));
    });

    expect(await screen.findByText('The amount must be positive.')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Record expense' })).toBeEnabled();
  });

  it('edits a recorded expense through updateExpense', async () => {
    updateExpenseMock.mockResolvedValue(recordedExpense);
    const onUpdated = jest.fn();

    render(
      <ExpenseForm
        organizationId="org-1"
        categories={categories}
        expense={recordedExpense}
        onCreated={jest.fn()}
        onUpdated={onUpdated}
        onCancelEdit={jest.fn()}
      />,
    );

    typeInto(screen.getByLabelText('Amount'), '2000.00');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Save changes' }));
    });

    expect(updateExpenseMock).toHaveBeenCalledWith(
      'org-1',
      'expense-1',
      expect.objectContaining({ amount: '2000.00' }),
    );
    expect(onUpdated).toHaveBeenCalledWith(recordedExpense);
  });
});
