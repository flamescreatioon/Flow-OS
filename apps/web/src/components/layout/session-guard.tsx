'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useSession } from '@/features/auth/session-context';
import { config } from '@/config';

/**
 * Client-side gate for the authenticated area. The Laravel session cookie
 * lives on the API origin, so Next.js middleware cannot inspect it; the
 * authoritative check is the API's own 401 handling.
 */
export function SessionGuard({ children }: { children: React.ReactNode }) {
  const { status } = useSession();
  const router = useRouter();

  useEffect(() => {
    if (status === 'unauthenticated') {
      router.replace(config.auth.loginPath);
    }
  }, [status, router]);

  if (status === 'loading') {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <p className="text-sm text-gray-500" aria-live="polite">
          Loading…
        </p>
      </div>
    );
  }

  if (status === 'unauthenticated') {
    return null;
  }

  return <>{children}</>;
}
