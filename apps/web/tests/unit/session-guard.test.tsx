import { render, screen, waitFor } from '@testing-library/react';
import { SessionGuard, } from '@/components/layout/session-guard';
import { SessionProvider } from '@/features/auth/session-context';
import * as authService from '@/features/auth/service';
import { useRouter } from 'next/navigation';
import { ApiError } from '@/lib/api';

jest.mock('next/navigation', () => ({
  useRouter: jest.fn(),
}));

jest.mock('@/features/auth/service', () => ({
  me: jest.fn(),
  logout: jest.fn(),
}));

const replaceMock = jest.fn();
const meMock = jest.mocked(authService.me);
const useRouterMock = jest.mocked(useRouter);

const testUser = {
  id: 'u1',
  name: 'Ada Obi',
  email: 'ada@example.com',
  status: 'active',
  email_verified_at: '2026-10-01T00:00:00Z',
  created_at: '2026-10-01T00:00:00Z',
  updated_at: '2026-10-01T00:00:00Z',
};

describe('SessionGuard', () => {
  beforeEach(() => {
    replaceMock.mockReset();
    meMock.mockReset();
    useRouterMock.mockReturnValue({ replace: replaceMock } as never);
  });

  it('renders protected content when /auth/me succeeds', async () => {
    meMock.mockResolvedValueOnce(testUser);

    render(
      <SessionProvider>
        <SessionGuard>
          <p>Protected area</p>
        </SessionGuard>
      </SessionProvider>,
    );

    expect(await screen.findByText('Protected area')).toBeInTheDocument();
    expect(replaceMock).not.toHaveBeenCalled();
  });

  it('redirects to the login page and renders nothing on 401', async () => {
    meMock.mockRejectedValueOnce(new ApiError(401, 'UNAUTHENTICATED', 'Unauthenticated.'));

    render(
      <SessionProvider>
        <SessionGuard>
          <p>Protected area</p>
        </SessionGuard>
      </SessionProvider>,
    );

    await waitFor(() => {
      expect(replaceMock).toHaveBeenCalledWith('/login');
    });
    await waitFor(() => {
      expect(screen.queryByText('Protected area')).not.toBeInTheDocument();
    });
  });
});
