import { render, screen } from '@testing-library/react';
import { usePathname } from 'next/navigation';
import { ActiveOrganizationProvider } from '@/features/organizations/active-organization-context';
import { SessionProvider } from '@/features/auth/session-context';
import DashboardPage from '@/app/(app)/dashboard/page';
import * as organizationsService from '@/features/organizations/service';
import * as authService from '@/features/auth/service';

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

import type { User } from '@/types';

const usePathnameMock = jest.mocked(usePathname);
const listOrganizationsMock = jest.mocked(organizationsService.listOrganizations);
const meMock = jest.mocked(authService.me);

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

    expect(
      await screen.findByText('Create your organization'),
    ).toBeInTheDocument();
    expect(screen.getByText('Verify your email address')).toBeInTheDocument();
    expect(screen.getByText('Welcome back')).toBeInTheDocument();
  });

  it('hides the setup checklist once the organization is active and email verified', async () => {
    meMock.mockResolvedValue(
      testUserWith({ email_verified_at: '2026-10-02T00:00:00Z' }),
    );
    listOrganizationsMock.mockResolvedValue({
      data: organizations,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 1,
    });

    renderDashboard();

    expect(await screen.findByText('Acme Trading')).toBeInTheDocument();
    expect(
      screen.queryByText('Finish setting up'),
    ).not.toBeInTheDocument();
    expect(screen.getByText('NGN')).toBeInTheDocument();
    expect(screen.getByText('Africa/Lagos')).toBeInTheDocument();
  });

  it('shows the remaining upcoming modules as coming soon', async () => {
    window.localStorage.removeItem('flowos.activeOrganizationId');
    listOrganizationsMock.mockResolvedValue({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 0,
    });

    renderDashboard();

    expect(await screen.findByText('Sales')).toBeInTheDocument();
    expect(screen.getAllByText('Coming soon').length).toBeGreaterThan(0);
    // Catalogue and inventory are shipped modules, not upcoming ones.
    expect(screen.queryByText('Catalogue')).not.toBeInTheDocument();
    expect(screen.queryByText('Inventory')).not.toBeInTheDocument();
  });
});
