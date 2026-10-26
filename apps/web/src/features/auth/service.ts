import { api, ApiError } from '@/lib/api';
import type { ApiResponse, MfaChallenge, User } from '@/types';

export type LoginResult =
  | { kind: 'authenticated' }
  | { kind: 'mfa_required'; challenge: MfaChallenge };

function isMfaChallenge(body: unknown): body is MfaChallenge {
  return (
    typeof body === 'object' &&
    body !== null &&
    'mfa_required' in body &&
    'challenge' in body
  );
}

export async function login(email: string, password: string): Promise<LoginResult> {
  const body = await api.post<unknown>('/auth/login', { email, password });
  if (isMfaChallenge(body)) {
    return { kind: 'mfa_required', challenge: body };
  }
  const envelope = body as ApiResponse<User>;
  if (!envelope || typeof envelope !== 'object' || !('data' in envelope)) {
    throw new ApiError(500, 'UNKNOWN', 'Unexpected login response.');
  }
  return { kind: 'authenticated' };
}

export async function verifyMfa(challenge: string, code: string): Promise<void> {
  await api.post<ApiResponse<User>>('/auth/mfa/verify', { challenge, code });
}

export async function me(): Promise<User> {
  const body = await api.get<ApiResponse<User>>('/auth/me');
  return body.data;
}

export async function register(input: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}): Promise<void> {
  await api.post<ApiResponse<User>>('/auth/register', input);
}

export async function forgotPassword(email: string): Promise<string> {
  const body = await api.post<{ message: string }>('/auth/forgot-password', { email });
  return body.message;
}

export async function resetPassword(input: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}): Promise<string> {
  const body = await api.post<{ message: string }>('/auth/reset-password', input);
  return body.message;
}

export async function logout(): Promise<void> {
  await api.post('/auth/logout');
}
