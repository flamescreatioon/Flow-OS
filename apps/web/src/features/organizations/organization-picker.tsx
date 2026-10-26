'use client';

import { useActiveOrganization } from '@/features/organizations/active-organization-context';

export function OrganizationPicker() {
  const { organizations, activeOrganization, loading, selectOrganization } =
    useActiveOrganization();

  if (loading && organizations.length === 0) {
    return (
      <span className="text-sm text-on-surface-variant" aria-live="polite">
        Loading organizations…
      </span>
    );
  }

  if (organizations.length === 0) {
    return (
      <a
        href="/organizations"
        className="inline-flex h-10 items-center justify-center rounded-full bg-warning-container px-4 text-sm font-medium text-on-warning-container transition-colors hover:bg-warning-container/80"
      >
        Create your first organization
      </a>
    );
  }

  return (
    <select
      aria-label="Active organization"
      value={activeOrganization?.id ?? ''}
      onChange={(event) => {
        const selected =
          organizations.find(
            (organization) => organization.id === event.target.value,
          ) ?? null;
        selectOrganization(selected);
      }}
      className="h-10 rounded-full border-0 bg-secondary-container px-4 text-sm font-medium text-on-secondary-container focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-primary"
    >
      <option value="" disabled>
        Select organization
      </option>
      {organizations.map((organization) => (
        <option key={organization.id} value={organization.id}>
          {organization.name}
        </option>
      ))}
    </select>
  );
}
