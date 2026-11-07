import { render, screen, fireEvent, act } from '@testing-library/react';
import { usePathname } from 'next/navigation';
import { ActiveOrganizationProvider } from '@/features/organizations/active-organization-context';
import { SessionProvider } from '@/features/auth/session-context';
import NewProductPage from '@/app/(app)/catalogue/new/page';
import * as catalogueService from '@/features/catalogue/service';
import * as authService from '@/features/auth/service';
import * as organizationsService from '@/features/organizations/service';

jest.mock('next/navigation', () => ({
  usePathname: jest.fn(),
}));

jest.mock('@/features/catalogue/service', () => ({
  listCategories: jest.fn(),
  listUnitOfMeasures: jest.fn(),
  createProduct: jest.fn(),
  updateProduct: jest.fn(),
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
const listCategoriesMock = jest.mocked(catalogueService.listCategories);
const listUnitOfMeasuresMock = jest.mocked(catalogueService.listUnitOfMeasures);
const createProductMock = jest.mocked(catalogueService.createProduct);
const meMock = jest.mocked(authService.me);
const listOrganizationsMock = jest.mocked(organizationsService.listOrganizations);

const testUser = {
  id: 'u1',
  name: 'Ada Obi',
  email: 'ada@example.com',
  status: 'active',
  platform_role: null,
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

const createdProduct = {
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
  created_at: '2026-11-07T00:00:00Z',
  updated_at: '2026-11-07T00:00:00Z',
};

function typeInto(input: HTMLElement, value: string): void {
  fireEvent.change(input, { target: { value } });
}

function renderPage(): void {
  render(
    <SessionProvider>
      <ActiveOrganizationProvider>
        <NewProductPage />
      </ActiveOrganizationProvider>
    </SessionProvider>,
  );
}

describe('NewProductPage', () => {
  beforeEach(() => {
    window.localStorage.clear();
    window.localStorage.setItem('flowos.activeOrganizationId', JSON.stringify('org-1'));
    usePathnameMock.mockReset();
    usePathnameMock.mockReturnValue('/catalogue/new');
    meMock.mockReset();
    meMock.mockResolvedValue(testUser);
    listOrganizationsMock.mockReset();
    listOrganizationsMock.mockResolvedValue({
      data: organizations,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 1,
    });
    listCategoriesMock.mockReset();
    listUnitOfMeasuresMock.mockReset();
    createProductMock.mockReset();
    listCategoriesMock.mockResolvedValue({
      data: categories,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 1,
    });
    listUnitOfMeasuresMock.mockResolvedValue({
      data: units,
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 1,
    });
  });

  it('renders the create form, units, and categories sections', async () => {
    renderPage();

    expect(await screen.findByRole('heading', { name: 'Add a product' })).toBeInTheDocument();
    // The form renders once the reference data resolves.
    expect(await screen.findByRole('button', { name: 'Create product' })).toBeInTheDocument();
    expect(screen.getAllByRole('link', { name: /back to catalogue/i }).length).toBeGreaterThan(0);

    // The units and categories sections moved onto this page.
    expect(screen.getByRole('heading', { name: 'Units of measure' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Categories' })).toBeInTheDocument();
    expect(screen.getAllByText('Beverages').length).toBeGreaterThan(0);
    expect(screen.getByRole('button', { name: 'Create unit' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Create category' })).toBeInTheDocument();
  });

  it('creates a product through the form', async () => {
    createProductMock.mockResolvedValue(createdProduct);

    renderPage();

    typeInto(await screen.findByLabelText('Name', { selector: '#product-name' }), 'Milo 500g');
    typeInto(screen.getByLabelText('Price'), '2500.00');

    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Create product' }));
    });

    expect(createProductMock).toHaveBeenCalledTimes(1);
    expect(await screen.findByText('Milo 500g was created.')).toBeInTheDocument();
  });

  it('shows the unit guard when no units exist', async () => {
    listUnitOfMeasuresMock.mockResolvedValue({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 0,
    });

    renderPage();

    expect(await screen.findByText(/create a unit of measure first/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Create product' })).not.toBeInTheDocument();
  });
});
