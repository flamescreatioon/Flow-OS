'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { resetPassword } from '@/features/auth/service';
import {
  fieldError,
  toFormErrors,
  type FormErrors,
} from '@/features/auth/form-errors';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emptyErrors: FormErrors = { form: null, fields: {} };

export function ResetPasswordForm() {
  const searchParams = useSearchParams();
  const [sentMessage, setSentMessage] = useState<string | null>(null);
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<FormErrors>(emptyErrors);

  async function onSubmit(
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);

    setPending(true);
    setErrors(emptyErrors);
    try {
      setSentMessage(
        await resetPassword({
          token: searchParams.get('token') ?? '',
          email: String(data.get('email') ?? ''),
          password: String(data.get('password') ?? ''),
          password_confirmation: String(data.get('password_confirmation') ?? ''),
        }),
      );
    } catch (error) {
      setErrors(toFormErrors(error));
    } finally {
      setPending(false);
    }
  }

  if (sentMessage) {
    return (
      <div className="space-y-4">
        <Alert variant="success">{sentMessage}</Alert>
        <Link
          href="/login"
          className="block text-center text-sm text-blue-600 hover:underline"
        >
          Sign in with your new password
        </Link>
      </div>
    );
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Reset password">
      {errors.form ? <Alert variant="error">{errors.form}</Alert> : null}
      <div className="space-y-1">
        <Label htmlFor="email">Email</Label>
        <Input
          id="email"
          name="email"
          type="email"
          autoComplete="email"
          defaultValue={searchParams.get('email') ?? ''}
          required
        />
      </div>
      <div className="space-y-1">
        <Label htmlFor="password">New password</Label>
        <Input
          id="password"
          name="password"
          type="password"
          autoComplete="new-password"
          required
        />
        {fieldError(errors, 'password') ? (
          <p className="text-sm text-red-600">{fieldError(errors, 'password')}</p>
        ) : null}
      </div>
      <div className="space-y-1">
        <Label htmlFor="password_confirmation">Confirm new password</Label>
        <Input
          id="password_confirmation"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          required
        />
      </div>
      <Button type="submit" disabled={pending} className="w-full">
        {pending ? 'Resetting…' : 'Reset password'}
      </Button>
      <Link
        href="/login"
        className="block text-center text-sm text-blue-600 hover:underline"
      >
        Back to sign in
      </Link>
    </form>
  );
}
