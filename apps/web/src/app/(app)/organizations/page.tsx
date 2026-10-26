'use client';

import { useRouter } from 'next/navigation';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { CreateOrganizationForm } from '@/features/organizations/create-organization-form';
import { config } from '@/config';
import { Button } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';

export default function OrganizationsPage() {
  const router = useRouter();
  const { organizations, activeOrganization, loading, selectOrganization } =
    useActiveOrganization();

  function enter(organizationId: string): void {
    const organization =
      organizations.find((item) => item.id === organizationId) ?? null;
    selectOrganization(organization);
    router.push(config.auth.dashboardPath);
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-gray-900">Organizations</h1>
        <p className="mt-1 text-sm text-gray-500">
          Choose which organization you are working in.
        </p>
      </div>

      {loading && organizations.length === 0 ? (
        <p className="text-sm text-gray-500" aria-live="polite">
          Loading organizations…
        </p>
      ) : organizations.length === 0 ? (
        <p className="text-sm text-gray-500">
          You are not a member of any organization yet. Create one below.
        </p>
      ) : (
        <ul className="grid gap-4 sm:grid-cols-2">
          {organizations.map((organization) => (
            <li key={organization.id}>
              <Card className="flex h-full flex-col justify-between">
                <div>
                  <CardTitle>{organization.name}</CardTitle>
                  <p className="mt-1 text-sm text-gray-500">
                    {organization.slug} · {organization.currency_code} ·{' '}
                    <span className="capitalize">{organization.status}</span>
                  </p>
                </div>
                <div className="mt-4 flex items-center gap-2">
                  <Button
                    onClick={() => enter(organization.id)}
                    variant={
                      activeOrganization?.id === organization.id
                        ? 'secondary'
                        : 'primary'
                    }
                  >
                    {activeOrganization?.id === organization.id
                      ? 'Current'
                      : 'Enter'}
                  </Button>
                </div>
              </Card>
            </li>
          ))}
        </ul>
      )}

      <Card className="max-w-md">
        <CardTitle>Create a new organization</CardTitle>
        <div className="mt-4">
          <CreateOrganizationForm />
        </div>
      </Card>
    </div>
  );
}
