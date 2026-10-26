import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import { usePathname } from 'next/navigation';
import { ActiveOrganizationProvider } from '@/features/organizations/active-organization-context';
import { SessionProvider } from '@/features/auth/session-context';
import { Sidebar } from '@/components/layout/sidebar';
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

const usePathnameMock = jest.mocked(usePathname);
const listOrganizationsMock = jest.mocked(organizationsService.listOrganizations);
const meMock = jest.mocked(authService.me);

const testUser = {
  id: 'u1',
  name: 'Ada Obi',
  email: 'ada@example.com',
  status: 'active',
  email_verified_at: '2026-10-01T00:00:00Z',
  created_at: '2026-10-01T00:00:00Z',
  updated_at: '2026-10-01T00:00:00Z',
};

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

function renderSidebar(): void {
  render(
    <SessionProvider>
      <ActiveOrganizationProvider>
        <Sidebar mobileOpen={false} onMobileClose={jest.fn()} />
      </ActiveOrganizationProvider>
    </SessionProvider>,
  );
}

describe('Sidebar', () => {
  beforeEach(() => {
    window.localStorage.clear();
    // Seed the persisted selection so the provider restores it as active.
    window.localStorage.setItem(
      'flowos.activeOrganizationId',
      JSON.stringify(organizations[0].id),
    );
    usePathnameMock.mockReset();
    usePathnameMock.mockReturnValue('/dashboard');
    listOrganizationsMock.mockReset();
    listOrganizationsMock.mockResolvedValue({
      data: organizations,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 1,
    });
    meMock.mockReset();
    meMock.mockResolvedValue(testUser);
  });

  it('shows the profile name, active organization, and navigation tabs', async () => {
    renderSidebar();

    expect(
      await screen.findByRole('link', { name: 'Dashboard' }),
    ).toHaveAttribute('href', '/dashboard');
    expect(screen.getByRole('link', { name: 'Organizations' })).toHaveAttribute(
      'href',
      '/organizations',
    );
    expect(await screen.findByText('Ada Obi')).toBeInTheDocument();
    expect(await screen.findByText('Acme Trading')).toBeInTheDocument();
  });

  it('collapses to the icon rail and persists the choice', async () => {
    renderSidebar();

    expect(await screen.findByText('Ada Obi')).toBeInTheDocument();
    expect(screen.getAllByText('Soon').length).toBeGreaterThan(0);

    fireEvent.click(screen.getByRole('button', { name: 'Collapse sidebar' }));

    await waitFor(() => {
      expect(window.localStorage.getItem('flowos.sidebarCollapsed')).toBe('true');
    });
    expect(screen.queryByText('Ada Obi')).not.toBeInTheDocument();
    expect(
      screen.getByRole('button', { name: 'Expand sidebar' }),
    ).toBeInTheDocument();
  });

  it('restores the stored collapsed state on a fresh render', async () => {
    window.localStorage.setItem('flowos.sidebarCollapsed', 'true');
    renderSidebar();

    expect(await screen.findByRole('button', { name: 'Expand sidebar' })).toBeInTheDocument();
  });

  it('keeps a disabled settings entry marked as coming soon', async () => {
    renderSidebar();

    const label = await screen.findByText('Settings');
    const container = label.closest('[aria-disabled]');
    expect(container).not.toBeNull();
    expect(within(container as HTMLElement).getByText('Soon')).toBeInTheDocument();
  });
});
