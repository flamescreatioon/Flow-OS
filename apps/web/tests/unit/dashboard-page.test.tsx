import { render, screen } from '@testing-library/react';
import { usePathname } from 'next/navigation';
import { ActiveOrganizationProvider } from '@/features/organizations/active-organization-context';
import { SessionProvider } from '@/features/auth/session-context';
import DashboardPage from '@/app/(app)/dashboard/page';
import * as organizationsService from '@/features/organizations/service';
import * as authService from '@/features/auth/service';
import * as dashboardService from '@/features/dashboard/service';
import * as invoicingService from '@/features/invoicing/service';
import * as purchasingService from '@/features/purchasing/service';

jest.mock('next/navigation', () => ({
  usePathname: jest.fn(),
}));

jest.mock('@/features/organizations/service', () => ({
  listOrganizations: jest.fn(),
  createOrganization: jest.fn(),
}));

jest.mock('@/features/auth/service', () => ({
  me: jest.fn(),
  logout: jest.fn(),
}));

jest.mock('@/features/dashboard/service', () => ({
  getDashboardSummary: jest.fn(),
}));

jest.mock('@/features/invoicing/service', () => ({
  listInvoices: jest.fn(),
}));

jest.mock('@/features/purchasing/service', () => ({
  listGoodsReceipts: jest.fn(),
}));

import type { User } from '@/types';

const usePathnameMock = jest.mocked(usePathname);
const listOrganizationsMock = jest.mocked(organizationsService.listOrganizations);
const meMock = jest.mocked(authService.me);
const getDashboardSummaryMock = jest.mocked(dashboardService.getDashboardSummary);
const listInvoicesMock = jest.mocked(invoicingService.listInvoices);
const listGoodsReceiptsMock = jest.mocked(purchasingService.listGoodsReceipts);

function testUserWith(overrides: Partial<User> = {}): User {
  return {
    id: 'u1',
    name: 'Ada Obi',
    email: 'ada@example.com',
    status: 'active',
    email_verified_at: null,
    created_at: '2026-10-01T00:00:00Z',
    updated_at: '2026-10-01T00:00:00Z',
    ...overrides,
  };
}

const testUser = testUserWith();

const organizations = [
  {
    id: 'org-1',
    name: 'Acme Trading',
    slug: 'acme-trading',
    legal_name: null,
    registration_number: null,
    tax_identification_number: null,
    phone: null,
    email: null,
    country_code: 'NG',
    currency_code: 'NGN',
    timezone: 'Africa/Lagos',
    status: 'active' as const,
    created_at: '2026-10-01T00:00:00Z',
    updated_at: '2026-10-01T00:00:00Z',
  },
];

const summary = {
  invoiced_total: '3225.0000',
  issued_invoices: 1,
  purchases_total: '12502.5000',
  customers: 1,
  suppliers: 1,
  products: 1,
  stock_units: '5.000',
  products_in_stock: 1,
  invoices_by_status: { draft: 1, issued: 1, voided: 0 },
  revenue_by_month: [
    { month: '2026-06', total: '0.0000' },
    { month: '2026-07', total: '0.0000' },
    { month: '2026-08', total: '0.0000' },
    { month: '2026-09', total: '0.0000' },
    { month: '2026-10', total: '0.0000' },
    { month: '2026-11', total: '3225.0000' },
  ],
};

function renderDashboard(): void {
  render(
    <SessionProvider>
      <ActiveOrganizationProvider>
        <DashboardPage />
      </ActiveOrganizationProvider>
    </SessionProvider>,
  );
}

describe('DashboardPage', () => {
  beforeEach(() => {
    window.localStorage.clear();
    // Seed the persisted selection so the provider restores it as active.
    window.localStorage.setItem('flowos.activeOrganizationId', JSON.stringify('org-1'));
    usePathnameMock.mockReset();
    usePathnameMock.mockReturnValue('/dashboard');
    listOrganizationsMock.mockReset();
    meMock.mockReset();
    meMock.mockResolvedValue(testUser);
    getDashboardSummaryMock.mockReset();
    listInvoicesMock.mockReset();
    listGoodsReceiptsMock.mockReset();
    listInvoicesMock.mockResolvedValue({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 5,
      total: 0,
    });
    listGoodsReceiptsMock.mockResolvedValue({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 5,
      total: 0,
    });
  });

  it('lists pending setup steps when no organization exists and email is unverified', async () => {
    window.localStorage.removeItem('flowos.activeOrganizationId');
    listOrganizationsMock.mockResolvedValue({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 0,
    });

    renderDashboard();

    expect(await screen.findByText('Create your organization')).toBeInTheDocument();
    expect(screen.getByText('Verify your email address')).toBeInTheDocument();
    expect(screen.getByText('Welcome back')).toBeInTheDocument();
    expect(getDashboardSummaryMock).not.toHaveBeenCalled();
  });

  it('shows the summary cards, charts, and recent activity for an active organization', async () => {
    meMock.mockResolvedValue(testUserWith({ email_verified_at: '2026-10-02T00:00:00Z' }));
    listOrganizationsMock.mockResolvedValue({
      data: organizations,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 1,
    });
    getDashboardSummaryMock.mockResolvedValue(summary);

    renderDashboard();

    expect(await screen.findByText('Amount made')).toBeInTheDocument();
    expect(screen.getByText('₦3,225.00')).toBeInTheDocument();
    expect(screen.getByText('₦12,502.50')).toBeInTheDocument();
    expect(screen.getByText('Total sales')).toBeInTheDocument();
    expect(screen.getByText('Customers')).toBeInTheDocument();
    expect(screen.getByText('Suppliers')).toBeInTheDocument();
    expect(screen.getByText('Stock on hand')).toBeInTheDocument();

    // The charts are graphics with accessible names summarising their data.
    expect(
      screen.getByRole('img', { name: /Issued invoice totals by month/i }),
    ).toBeInTheDocument();
    expect(screen.getByRole('img', { name: /Invoice counts by status/i })).toBeInTheDocument();
  });

  it('renders an error message when the summary fails to load', async () => {
    meMock.mockResolvedValue(testUserWith({ email_verified_at: '2026-10-02T00:00:00Z' }));
    listOrganizationsMock.mockResolvedValue({
      data: organizations,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 1,
    });
    getDashboardSummaryMock.mockRejectedValue(new Error('network down'));

    renderDashboard();

    expect(
      await screen.findByText('Unable to load your dashboard numbers. Please try again.'),
    ).toBeInTheDocument();
    expect(screen.queryByText('Amount made')).not.toBeInTheDocument();
  });
});
