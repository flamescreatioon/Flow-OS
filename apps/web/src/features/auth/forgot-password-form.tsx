'use client';

import { useState } from 'react';
import Link from 'next/link';
import { forgotPassword } from '@/features/auth/service';
import { toFormErrors, type FormErrors } from '@/features/auth/form-errors';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emptyErrors: FormErrors = { form: null, fields: {} };

export function ForgotPasswordForm() {
  const [sentMessage, setSentMessage] = useState<string | null>(null);
  const [pending, setPending] = useState(false);
  const [errors, setErrors] = useState<FormErrors>(emptyErrors);

  async function onSubmit(
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const email = String(data.get('email') ?? '');

    setPending(true);
    setErrors(emptyErrors);
    try {
      setSentMessage(await forgotPassword(email));
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
          Back to sign in
        </Link>
      </div>
    );
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Request password reset">
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
      </div>
      <Button type="submit" disabled={pending} className="w-full">
        {pending ? 'Sending…' : 'Send reset link'}
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
