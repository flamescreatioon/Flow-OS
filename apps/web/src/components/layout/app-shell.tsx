'use client';

import Link from 'next/link';
import { useSession } from '@/features/auth/session-context';
import { OrganizationPicker } from '@/features/organizations/organization-picker';
import { Button } from '@/components/ui/button';

export function AppShell({ children }: { children: React.ReactNode }) {
  const { user, signOut } = useSession();

  async function onSignOut(): Promise<void> {
    await signOut();
  }

  return (
    <div className="min-h-screen">
      <header className="border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
          <div className="flex items-center gap-6">
            <Link href="/dashboard" className="text-lg font-semibold text-gray-900">
              flowOS
            </Link>
            <nav className="flex items-center gap-4 text-sm">
              <Link
                href="/dashboard"
                className="text-gray-600 hover:text-gray-900"
              >
                Dashboard
              </Link>
              <Link
                href="/organizations"
                className="text-gray-600 hover:text-gray-900"
              >
                Organizations
              </Link>
            </nav>
          </div>
          <div className="flex items-center gap-3">
            <OrganizationPicker />
            <span className="hidden text-sm text-gray-600 sm:inline">
              {user?.name}
            </span>
            <Button variant="ghost" onClick={() => void onSignOut()}>
              Sign out
            </Button>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-6xl px-4 py-8">{children}</main>
    </div>
  );
}
