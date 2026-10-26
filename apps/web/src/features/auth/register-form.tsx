'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { register } from '@/features/auth/service';
import {
  fieldError,
  toFormErrors,
  type FormErrors,
} from '@/features/auth/form-errors';
import { config } from '@/config';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emptyErrors: FormErrors = { form: null, fields: {} };

export function RegisterForm() {
  const router = useRouter();
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
      await register({
        name: String(data.get('name') ?? ''),
        email: String(data.get('email') ?? ''),
        password: String(data.get('password') ?? ''),
        password_confirmation: String(data.get('password_confirmation') ?? ''),
      });
      router.replace(
        `${config.auth.loginPath}?registered=1`,
      );
    } catch (error) {
      setErrors(toFormErrors(error));
    } finally {
      setPending(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Create account">
      {errors.form ? <Alert variant="error">{errors.form}</Alert> : null}
      <div className="space-y-1">
        <Label htmlFor="name">Full name</Label>
        <Input id="name" name="name" autoComplete="name" autoFocus required />
        {fieldError(errors, 'name') ? (
          <p className="text-sm text-red-600">{fieldError(errors, 'name')}</p>
        ) : null}
      </div>
      <div className="space-y-1">
        <Label htmlFor="email">Email</Label>
        <Input
          id="email"
          name="email"
          type="email"
          autoComplete="email"
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
          autoComplete="new-password"
          required
        />
        <p className="text-xs text-gray-500">
          At least 12 characters with upper and lower case letters, numbers,
          and symbols.
        </p>
        {fieldError(errors, 'password') ? (
          <p className="text-sm text-red-600">{fieldError(errors, 'password')}</p>
        ) : null}
      </div>
      <div className="space-y-1">
        <Label htmlFor="password_confirmation">Confirm password</Label>
        <Input
          id="password_confirmation"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          required
        />
        {fieldError(errors, 'password_confirmation') ? (
          <p className="text-sm text-red-600">
            {fieldError(errors, 'password_confirmation')}
          </p>
        ) : null}
      </div>
      <Button type="submit" disabled={pending} className="w-full">
        {pending ? 'Creating account…' : 'Create account'}
      </Button>
      <p className="text-center text-sm text-gray-600">
        Already have an account?{' '}
        <Link href="/login" className="text-blue-600 hover:underline">
          Sign in
        </Link>
      </p>
    </form>
  );
}
