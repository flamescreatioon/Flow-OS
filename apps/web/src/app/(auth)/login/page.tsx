'use client';

import { Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import { LoginForm } from '@/features/auth/login-form';
import { Alert } from '@/components/ui/alert';

function LoginScreen() {
  const searchParams = useSearchParams();

  return (
    <div className="space-y-4">
      {searchParams.get('registered') ? (
        <Alert variant="success">
          Account created. Verify your email, then sign in.
        </Alert>
      ) : null}
      <LoginForm />
    </div>
  );
}

export default function LoginPage() {
  return (
    <Suspense fallback={null}>
      <LoginScreen />
    </Suspense>
  );
}
