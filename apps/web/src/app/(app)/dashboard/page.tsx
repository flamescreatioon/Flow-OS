'use client';

import Link from 'next/link';
import { useSession } from '@/features/auth/session-context';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { Card, CardTitle } from '@/components/ui/card';

const upcomingModules = [
  'Catalogue',
  'Inventory',
  'Sales',
  'Invoicing',
  'Expenses',
  'Reporting',
];

export default function DashboardPage() {
  const { user } = useSession();
  const { activeOrganization } = useActiveOrganization();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-gray-900">
          Welcome{user ? `, ${user.name}` : ''}
        </h1>
        <p className="mt-1 text-sm text-gray-500">
          {activeOrganization
            ? `Working in ${activeOrganization.name}`
            : 'No organization selected yet.'}
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <Card>
          <CardTitle>Account</CardTitle>
          <dl className="mt-3 space-y-1 text-sm text-gray-600">
            <div className="flex justify-between gap-4">
              <dt>Email</dt>
              <dd className="text-gray-900">{user?.email}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt>Status</dt>
              <dd className="capitalize text-gray-900">{user?.status}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt>Email verified</dt>
              <dd className="text-gray-900">
                {user?.email_verified_at ? 'Yes' : 'No'}
              </dd>
            </div>
          </dl>
        </Card>

        <Card>
          <CardTitle>Organization</CardTitle>
          {activeOrganization ? (
            <dl className="mt-3 space-y-1 text-sm text-gray-600">
              <div className="flex justify-between gap-4">
                <dt>Name</dt>
                <dd className="text-gray-900">{activeOrganization.name}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt>Currency</dt>
                <dd className="text-gray-900">
                  {activeOrganization.currency_code}
                </dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt>Timezone</dt>
                <dd className="text-gray-900">{activeOrganization.timezone}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt>Status</dt>
                <dd className="capitalize text-gray-900">
                  {activeOrganization.status}
                </dd>
              </div>
            </dl>
          ) : (
            <p className="mt-3 text-sm text-gray-600">
              Select or create an organization in the{' '}
              <Link href="/organizations" className="text-blue-600 hover:underline">
                organizations
              </Link>{' '}
              page to get started.
            </p>
          )}
        </Card>
      </div>

      <Card>
        <CardTitle>Modules</CardTitle>
        <p className="mt-1 text-sm text-gray-500">
          Business modules arrive with the next platform phases. Catalogue and
          inventory screens are up next.
        </p>
        <ul className="mt-4 grid grid-cols-2 gap-2 text-sm text-gray-600 sm:grid-cols-3">
          {upcomingModules.map((module) => (
            <li
              key={module}
              className="rounded-md border border-dashed border-gray-300 px-3 py-2 text-center"
            >
              {module}
            </li>
          ))}
        </ul>
      </Card>
    </div>
  );
}
