import {
  render,
  screen,
  waitFor,
  fireEvent,
  act,
} from '@testing-library/react';
import { LoginForm } from '@/features/auth/login-form';
import * as authService from '@/features/auth/service';
import { useRouter } from 'next/navigation';
import { ApiError } from '@/lib/api';

jest.mock('next/navigation', () => ({
  useRouter: jest.fn(),
}));

jest.mock('@/features/auth/service', () => ({
  login: jest.fn(),
  verifyMfa: jest.fn(),
}));

const replaceMock = jest.fn();
const loginMock = jest.mocked(authService.login);
const verifyMfaMock = jest.mocked(authService.verifyMfa);
const useRouterMock = jest.mocked(useRouter);

const mfaChallenge = {
  mfa_required: true as const,
  challenge: 'challenge-token',
  expires_at: '2026-10-25T12:00:00Z',
  methods: ['totp', 'recovery_code'],
};

function typeInto(input: HTMLElement, value: string): void {
  fireEvent.change(input, { target: { value } });
}

describe('LoginForm', () => {
  beforeEach(() => {
    replaceMock.mockReset();
    loginMock.mockReset();
    verifyMfaMock.mockReset();
    useRouterMock.mockReturnValue({ replace: replaceMock } as never);
  });

  async function submitCredentials(): Promise<void> {
    typeInto(screen.getByLabelText('Email'), 'ada@example.com');
    typeInto(screen.getByLabelText('Password'), 'correct-horse-1!');
    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Sign in' }));
    });
  }

  async function submitMfaCode(): Promise<void> {
    typeInto(await screen.findByLabelText('Authentication code'), '123456');
    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: 'Verify' }));
    });
  }

  it('switches to the MFA step when the API answers with a 202 challenge', async () => {
    loginMock.mockResolvedValueOnce({
      kind: 'mfa_required',
      challenge: mfaChallenge,
    });

    render(<LoginForm />);
    await submitCredentials();

    expect(await screen.findByLabelText('Authentication code')).toBeInTheDocument();
    expect(screen.getByText(/authenticator app/i)).toBeInTheDocument();
    expect(replaceMock).not.toHaveBeenCalled();
  });

  it('navigates to the dashboard after a successful MFA verification', async () => {
    loginMock.mockResolvedValueOnce({
      kind: 'mfa_required',
      challenge: mfaChallenge,
    });
    verifyMfaMock.mockResolvedValueOnce(undefined);

    render(<LoginForm />);
    await submitCredentials();
    await submitMfaCode();

    await waitFor(() => {
      expect(replaceMock).toHaveBeenCalledWith('/dashboard');
    });
    expect(verifyMfaMock).toHaveBeenCalledWith('challenge-token', '123456');
  });

  it('navigates straight to the dashboard when no second factor is enrolled', async () => {
    loginMock.mockResolvedValueOnce({ kind: 'authenticated' });

    render(<LoginForm />);
    await submitCredentials();

    await waitFor(() => {
      expect(replaceMock).toHaveBeenCalledWith('/dashboard');
    });
    expect(screen.queryByLabelText('Authentication code')).not.toBeInTheDocument();
  });

  it('renders field errors from the validation envelope', async () => {
    loginMock.mockRejectedValueOnce(
      new ApiError(422, 'VALIDATION_ERROR', 'The given data was invalid.', {
        email: ['The provided credentials are incorrect.'],
      }),
    );

    render(<LoginForm />);
    await submitCredentials();

    expect(
      await screen.findByText('The provided credentials are incorrect.'),
    ).toBeInTheDocument();
    expect(replaceMock).not.toHaveBeenCalled();
  });

  it('renders the message for non-validation errors', async () => {
    loginMock.mockRejectedValueOnce(
      new ApiError(429, 'RATE_LIMITED', 'Too many requests.'),
    );

    render(<LoginForm />);
    await submitCredentials();

    expect(await screen.findByText('Too many requests.')).toBeInTheDocument();
  });
});
