import { SessionProvider } from '@/features/auth/session-context';
import { ActiveOrganizationProvider } from '@/features/organizations/active-organization-context';
import { SessionGuard } from '@/components/layout/session-guard';
import { AppShell } from '@/components/layout/app-shell';

export default function AppLayout({ children }: { children: React.ReactNode }) {
  return (
    <SessionProvider>
      <ActiveOrganizationProvider>
        <SessionGuard>
          <AppShell>{children}</AppShell>
        </SessionGuard>
      </ActiveOrganizationProvider>
    </SessionProvider>
  );
}
