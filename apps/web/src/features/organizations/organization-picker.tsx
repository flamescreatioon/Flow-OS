'use client';

import { useActiveOrganization } from '@/features/organizations/active-organization-context';

export function OrganizationPicker() {
  const { organizations, activeOrganization, loading, selectOrganization } =
    useActiveOrganization();

  if (loading && organizations.length === 0) {
    return (
      <span className="text-sm text-gray-500" aria-live="polite">
        Loading organizations…
      </span>
    );
  }

  if (organizations.length === 0) {
    return (
      <a
        href="/organizations"
        className="rounded-md bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-100"
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
      className="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
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
