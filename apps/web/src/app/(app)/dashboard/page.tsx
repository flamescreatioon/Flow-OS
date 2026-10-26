'use client';

import Link from 'next/link';
import {
  BarChart3,
  Building2,
  Circle,
  ReceiptText,
  ShoppingCart,
  UserRound,
  Wallet,
  type LucideIcon,
} from 'lucide-react';
import { useSession } from '@/features/auth/session-context';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Card, CardTitle } from '@/components/ui/card';

interface UpcomingModule {
  name: string;
  description: string;
  icon: LucideIcon;
}

const upcomingModules: UpcomingModule[] = [
  {
    name: 'Sales',
    description: 'Orders and receipts for every sale.',
    icon: ShoppingCart,
  },
  {
    name: 'Invoicing',
    description: 'Send invoices and track what you are owed.',
    icon: ReceiptText,
  },
  {
    name: 'Expenses',
    description: 'Record and categorise business spending.',
    icon: Wallet,
  },
  {
    name: 'Reporting',
    description: 'Performance across every module.',
    icon: BarChart3,
  },
];

function statusTone(status: string): BadgeTone {
  if (status === 'active') return 'success';
  if (status === 'suspended') return 'warning';
  if (status === 'closed') return 'danger';
  return 'neutral';
}

interface DetailRowProps {
  label: string;
  children: React.ReactNode;
}

function DetailRow({ label, children }: DetailRowProps) {
  return (
    <div className="flex items-center justify-between gap-4 text-base">
      <dt className="text-on-surface-variant">{label}</dt>
      <dd className="truncate font-medium text-on-surface">{children}</dd>
    </div>
  );
}

export default function DashboardPage() {
  const { user } = useSession();
  const { activeOrganization } = useActiveOrganization();

  const emailVerified = Boolean(user?.email_verified_at);
  const hasOrganization = Boolean(activeOrganization);
  const setupPending: Array<{ label: string; hint: string; done: boolean; href?: string; actionLabel?: string }> = [
    {
      label: 'Create your organization',
      hint: 'Set up the workspace your records will live in.',
      done: hasOrganization,
      href: '/organizations',
      actionLabel: 'Go to organizations',
    },
    {
      label: 'Verify your email address',
      hint: 'Check your inbox for the verification link.',
      done: emailVerified,
    },
  ];
  const pendingSetup = setupPending.filter((item) => !item.done);

  const firstName = user?.name.split(' ')[0]?.trim() ?? '';
  const greeting = firstName ? `Welcome back, ${firstName}` : 'Welcome back';

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-[2rem] font-semibold leading-tight tracking-[-0.02em] text-on-surface">
          {greeting}
        </h1>
        <p className="mt-1 text-base text-on-surface-variant">
          {activeOrganization
            ? `Working in ${activeOrganization.name}`
            : 'No organization selected yet.'}
        </p>
      </header>

      {pendingSetup.length > 0 && (
        <section aria-label="Setup checklist" className="rounded-2xl bg-primary-container p-6">
          <h2 className="text-base font-semibold text-on-primary-container">
            Finish setting up
          </h2>
          <p className="mt-1 text-base text-on-primary-container/80">
            A few steps before your workspace is ready.
          </p>
          <ul className="mt-4 space-y-3">
            {pendingSetup.map((item) => (
              <li
                key={item.label}
                className="flex flex-col gap-3 rounded-2xl bg-surface p-4 sm:flex-row sm:items-center sm:justify-between"
              >
                <div className="flex items-center gap-3">
                  <Circle className="h-5 w-5 shrink-0 text-on-surface-variant/40" aria-hidden="true" />
                  <div>
                    <p className="text-base font-medium text-on-surface">{item.label}</p>
                    <p className="text-sm text-on-surface-variant">{item.hint}</p>
                  </div>
                </div>
                {item.href && item.actionLabel && (
                  <Link
                    href={item.href}
                    className="inline-flex h-9 shrink-0 items-center justify-center rounded-full bg-primary px-4 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90"
                  >
                    {item.actionLabel}
                  </Link>
                )}
              </li>
            ))}
          </ul>
        </section>
      )}

      <div className="grid gap-4 sm:grid-cols-2">
        <Card>
          <div className="flex items-center gap-3">
            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
              <Building2 className="h-5 w-5" aria-hidden="true" />
            </span>
            <CardTitle>Organization</CardTitle>
            {activeOrganization && (
              <Badge tone={statusTone(activeOrganization.status)} className="ml-auto capitalize">
                {activeOrganization.status}
              </Badge>
            )}
          </div>
          {activeOrganization ? (
            <dl className="mt-4 space-y-2">
              <DetailRow label="Name">{activeOrganization.name}</DetailRow>
              <DetailRow label="Currency">{activeOrganization.currency_code}</DetailRow>
              <DetailRow label="Timezone">{activeOrganization.timezone}</DetailRow>
            </dl>
          ) : (
            <p className="mt-4 text-base text-on-surface-variant">
              Select or create an organization in the{' '}
              <Link href="/organizations" className="font-medium text-primary hover:underline">
                organizations
              </Link>{' '}
              page to get started.
            </p>
          )}
        </Card>

        <Card>
          <div className="flex items-center gap-3">
            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
              <UserRound className="h-5 w-5" aria-hidden="true" />
            </span>
            <CardTitle>Account</CardTitle>
            {user && (
              <Badge
                tone={emailVerified ? 'success' : 'warning'}
                className="ml-auto"
              >
                {emailVerified ? 'Email verified' : 'Email unverified'}
              </Badge>
            )}
          </div>
          {user ? (
            <dl className="mt-4 space-y-2">
              <DetailRow label="Email">{user.email}</DetailRow>
              <DetailRow label="Status">
                <span className="capitalize">{user.status}</span>
              </DetailRow>
              <DetailRow label="Email verified">
                {emailVerified ? 'Yes' : 'No'}
              </DetailRow>
            </dl>
          ) : (
            <p className="mt-4 text-base text-on-surface-variant">
              Account details appear once your session is ready.
            </p>
          )}
        </Card>
      </div>

      <section aria-label="Upcoming modules" className="space-y-4">
        <div>
          <h2 className="text-lg font-semibold text-on-surface">Modules</h2>
          <p className="mt-1 max-w-[60ch] text-base text-on-surface-variant">
            Catalogue and inventory are live — manage products under Catalogue
            and stock under Inventory. More modules arrive with the next
            platform phases.
          </p>
        </div>
        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {upcomingModules.map((module) => {
            const Icon = module.icon;
            return (
              <li key={module.name} className="flex flex-col gap-3 rounded-2xl bg-surface p-5">
                <div className="flex items-center gap-3">
                  <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-container text-on-primary-container">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                  </span>
                  <h3 className="text-base font-semibold text-on-surface">{module.name}</h3>
                  <Badge className="ml-auto">Coming soon</Badge>
                </div>
                <p className="text-base text-on-surface-variant">{module.description}</p>
              </li>
            );
          })}
        </ul>
      </section>
    </div>
  );
}
