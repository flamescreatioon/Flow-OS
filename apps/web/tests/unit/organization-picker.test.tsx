import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { ActiveOrganizationProvider } from '@/features/organizations/active-organization-context';
import { SessionProvider } from '@/features/auth/session-context';
import { OrganizationPicker } from '@/features/organizations/organization-picker';
import * as organizationsService from '@/features/organizations/service';
import * as authService from '@/features/auth/service';

jest.mock('@/features/organizations/service', () => ({
  listOrganizations: jest.fn(),
  createOrganization: jest.fn(),
}));

jest.mock('@/features/auth/service', () => ({
  me: jest.fn(),
  logout: jest.fn(),
}));

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
  {
    id: 'org-2',
    name: 'Lagos Retail',
    slug: 'lagos-retail',
    legal_name: null,
    registration_number: null,
    tax_identification_number: null,
    phone: null,
    email: null,
    country_code: 'NG',
    currency_code: 'NGN',
    timezone: 'Africa/Lagos',
    status: 'active' as const,
    created_at: '2026-10-02T00:00:00Z',
    updated_at: '2026-10-02T00:00:00Z',
  },
];

function renderPicker(): void {
  render(
    <SessionProvider>
      <ActiveOrganizationProvider>
        <OrganizationPicker />
      </ActiveOrganizationProvider>
    </SessionProvider>,
  );
}

describe('OrganizationPicker', () => {
  beforeEach(() => {
    window.localStorage.clear();
    listOrganizationsMock.mockReset();
    meMock.mockReset();
    meMock.mockResolvedValue(testUser);
  });

  it('lists the member organizations once loaded', async () => {
    listOrganizationsMock.mockResolvedValueOnce({
      data: organizations,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 2,
    });

    renderPicker();

    const select = await screen.findByLabelText('Active organization');
    await waitFor(() => {
      expect((select as HTMLSelectElement).options).toHaveLength(3);
    });
    expect(screen.getByRole('option', { name: 'Acme Trading' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Lagos Retail' })).toBeInTheDocument();
  });

  it('persists the selected organization id', async () => {
    listOrganizationsMock.mockResolvedValueOnce({
      data: organizations,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 2,
    });

    renderPicker();

    const select = (await screen.findByLabelText(
      'Active organization',
    )) as HTMLSelectElement;
    await waitFor(() => {
      expect(select.options).toHaveLength(3);
    });

    fireEvent.change(select, { target: { value: 'org-2' } });

    await waitFor(() => {
      expect(window.localStorage.getItem('flowos.activeOrganizationId')).toBe(
        JSON.stringify('org-2'),
      );
    });
    expect(select.value).toBe('org-2');
  });
});
