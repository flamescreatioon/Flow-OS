'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { login, verifyMfa } from '@/features/auth/service';
import {
  fieldError,
  toFormErrors,
  type FormErrors,
} from '@/features/auth/form-errors';
import { config } from '@/config';
import type { MfaChallenge } from '@/types';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emptyErrors: FormErrors = { form: null, fields: {} };

export function LoginForm() {
  const router = useRouter();
  const [step, setStep] = useState<'credentials' | 'mfa'>('credentials');
  const [challenge, setChallenge] = useState<MfaChallenge | null>(null);
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<FormErrors>(emptyErrors);

  async function onCredentialsSubmit(
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const email = String(data.get('email') ?? '');
    const password = String(data.get('password') ?? '');

    setPending(true);
    setErrors(emptyErrors);
    try {
      const result = await login(email, password);
      if (result.kind === 'mfa_required') {
        setChallenge(result.challenge);
        setStep('mfa');
        return;
      }
      router.replace(config.auth.dashboardPath);
    } catch (error) {
      setErrors(toFormErrors(error));
    } finally {
      setPending(false);
    }
  }

  async function onMfaSubmit(
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> {
    event.preventDefault();
    if (!challenge) return;
    const data = new FormData(event.currentTarget);
    const code = String(data.get('code') ?? '');

    setPending(true);
    setErrors(emptyErrors);
    try {
      await verifyMfa(challenge.challenge, code);
      router.replace(config.auth.dashboardPath);
    } catch (error) {
      setErrors(toFormErrors(error));
    } finally {
      setPending(false);
    }
  }

  if (step === 'mfa' && challenge) {
    return (
      <form onSubmit={onMfaSubmit} className="space-y-4" aria-label="Two-factor verification">
        <Alert variant="info">
          Enter the 6-digit code from your authenticator app, or one of your
          recovery codes.
        </Alert>
        {errors.form ? <Alert variant="error">{errors.form}</Alert> : null}
        <div className="space-y-1">
          <Label htmlFor="code">Authentication code</Label>
          <Input
            id="code"
            name="code"
            autoComplete="one-time-code"
            autoFocus
            required
            maxLength={64}
          />
          {fieldError(errors, 'code') ? (
            <p className="text-sm text-red-600">{fieldError(errors, 'code')}</p>
          ) : null}
        </div>
        <Button type="submit" disabled={pending} className="w-full">
          {pending ? 'Verifying…' : 'Verify'}
        </Button>
        <Button
          variant="ghost"
          className="w-full"
          disabled={pending}
          onClick={() => {
            setStep('credentials');
            setChallenge(null);
          }}
        >
          Back to sign in
        </Button>
      </form>
    );
  }

  return (
    <form onSubmit={onCredentialsSubmit} className="space-y-4" aria-label="Sign in">
      {errors.form ? <Alert variant="error">{errors.form}</Alert> : null}
      <div className="space-y-1">
        <Label htmlFor="email">Email</Label>
        <Input
          id="email"
          name="email"
          type="email"
          autoComplete="email"
          autoFocus
          required
        />
        {fieldError(errors, 'email') ? (
          <p className="text-sm text-red-600">{fieldError(errors, 'email')}</p>
        ) : null}
      </div>
      <div className="space-y-1">
        <Label htmlFor="password">Password</Label>
        <Input
          id="password"
          name="password"
          type="password"
          autoComplete="current-password"
          required
        />
        {fieldError(errors, 'password') ? (
          <p className="text-sm text-red-600">{fieldError(errors, 'password')}</p>
        ) : null}
      </div>
      <Button type="submit" disabled={pending} className="w-full">
        {pending ? 'Signing in…' : 'Sign in'}
      </Button>
      <div className="flex justify-between text-sm">
        <Link href="/forgot-password" className="text-blue-600 hover:underline">
          Forgot password?
        </Link>
        <Link href="/register" className="text-blue-600 hover:underline">
          Create account
        </Link>
      </div>
    </form>
  );
}
