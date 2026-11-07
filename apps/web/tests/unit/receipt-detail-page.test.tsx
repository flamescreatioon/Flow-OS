import { render, screen, fireEvent, act } from '@testing-library/react';
import { usePathname, useParams, useSearchParams } from 'next/navigation';
import { ActiveOrganizationProvider } from '@/features/organizations/active-organization-context';
import { SessionProvider } from '@/features/auth/session-context';
import ReceiptDetailPage from '@/app/(app)/purchasing/receipts/[id]/page';
import * as purchasingService from '@/features/purchasing/service';
import * as authService from '@/features/auth/service';
import * as organizationsService from '@/features/organizations/service';

jest.mock('next/navigation', () => ({
  usePathname: jest.fn(),
  useParams: jest.fn(),
  useSearchParams: jest.fn(),
}));

jest.mock('@/features/purchasing/service', () => ({
  getGoodsReceipt: jest.fn(),
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
const useParamsMock = jest.mocked(useParams);
const useSearchParamsMock = jest.mocked(useSearchParams);
const getGoodsReceiptMock = jest.mocked(purchasingService.getGoodsReceipt);
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

const receipt = {
  id: 'receipt-1',
  organization_id: 'org-1',
  branch_id: null,
  supplier_id: 'supplier-1',
  supplier_name: 'Lagos Beverage Supply',
  location_id: 'location-1',
  location: {
    id: 'location-1',
    organization_id: 'org-1',
    branch_id: null,
    code: 'MAIN',
    name: 'Main Store',
    archived_at: null,
    created_at: '2026-11-01T00:00:00Z',
    updated_at: '2026-11-01T00:00:00Z',
  },
  receipt_number: 1,
  document_number: 'GRN-000001',
  status: 'recorded' as const,
  received_on: '2026-11-07',
  note: null,
  total_cost: '12502.5000',
  lines: [
    {
      id: 'line-1',
      goods_receipt_id: 'receipt-1',
      product_id: 'product-1',
      product: {
        id: 'product-1',
        organization_id: 'org-1',
        name: 'Milo 500g',
        sku: 'MILO-500',
        type: 'stocked' as const,
        price: '2500.0000',
        tax_code: null,
        unit_of_measure: null,
        category: null,
        archived_at: null,
        created_at: '2026-11-01T00:00:00Z',
        updated_at: '2026-11-01T00:00:00Z',
      },
      quantity: '5.000',
      unit_cost: '2500.5000',
      line_total: '12502.5000',
    },
  ],
  created_at: '2026-11-07T00:00:00Z',
  updated_at: '2026-11-07T00:00:00Z',
};

function renderPage(): void {
  render(
    <SessionProvider>
      <ActiveOrganizationProvider>
        <ReceiptDetailPage />
      </ActiveOrganizationProvider>
    </SessionProvider>,
  );
}

describe('ReceiptDetailPage', () => {
  let printSpy: jest.SpyInstance;

  beforeEach(() => {
    window.localStorage.clear();
    window.localStorage.setItem('flowos.activeOrganizationId', JSON.stringify('org-1'));
    usePathnameMock.mockReset();
    usePathnameMock.mockReturnValue('/purchasing/receipts/receipt-1');
    useParamsMock.mockReset();
    useParamsMock.mockReturnValue({ id: 'receipt-1' });
    useSearchParamsMock.mockReset();
    useSearchParamsMock.mockReturnValue(new URLSearchParams() as unknown as ReturnType<typeof useSearchParams>);
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
    getGoodsReceiptMock.mockReset();
    getGoodsReceiptMock.mockResolvedValue(receipt);
    printSpy = jest.spyOn(window, 'print').mockImplementation(() => undefined);
  });

  afterEach(() => {
    printSpy.mockRestore();
  });

  it('renders the receipt document with lines, totals, and location', async () => {
    renderPage();

    expect(
      await screen.findByRole('heading', { name: 'Goods receipt GRN-000001' }),
    ).toBeInTheDocument();
    expect(screen.getByText('Milo 500g')).toBeInTheDocument();
    expect(screen.getByText('Lagos Beverage Supply')).toBeInTheDocument();
    expect(screen.getByText(/Main Store \(MAIN\)/)).toBeInTheDocument();
    expect(screen.getAllByText('12502.5').length).toBeGreaterThan(0);
  });

  it('calls window.print when the Print button is pressed', async () => {
    renderPage();

    const button = await screen.findByRole('button', { name: /print/i });
    await act(async () => {
      fireEvent.click(button);
    });

    expect(printSpy).toHaveBeenCalledTimes(1);
  });

  it('auto-prints exactly once when ?print=1 is present', async () => {
    useSearchParamsMock.mockReturnValue(
      new URLSearchParams({ print: '1' }) as unknown as ReturnType<typeof useSearchParams>,
    );

    renderPage();

    await screen.findByRole('heading', { name: 'Goods receipt GRN-000001' });
    await act(async () => {
      await Promise.resolve();
    });
    await act(async () => {
      await Promise.resolve();
    });

    expect(printSpy).toHaveBeenCalledTimes(1);
  });

  it('renders an error when the receipt cannot be loaded', async () => {
    getGoodsReceiptMock.mockRejectedValue(new Error('not found'));

    renderPage();

    expect(await screen.findByRole('alert')).toBeInTheDocument();
  });
});
