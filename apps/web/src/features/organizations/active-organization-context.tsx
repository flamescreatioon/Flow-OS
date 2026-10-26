'use client';

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import * as organizationsService from '@/features/organizations/service';
import { useSession } from '@/features/auth/session-context';
import { useLocalStorage } from '@/hooks/use-local-storage';
import type { Organization, PaginatedResponse } from '@/types';

const STORAGE_KEY = 'flowos.activeOrganizationId';

const EMPTY_PAGE: PaginatedResponse<Organization> = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 100,
  total: 0,
};

interface ActiveOrganizationContextValue {
  organizations: Organization[];
  activeOrganization: Organization | null;
  loading: boolean;
  selectOrganization: (organization: Organization | null) => void;
}

const ActiveOrganizationContext =
  createContext<ActiveOrganizationContextValue | null>(null);

export function ActiveOrganizationProvider({
  children,
}: {
  children: React.ReactNode;
}) {
  const { status, user } = useSession();
  const [page, setPage] = useState<PaginatedResponse<Organization> | null>(null);
  const [selected, setSelected] = useState<Organization | null>(null);
  const [storedId, setStoredId] = useLocalStorage<string | null>(
    STORAGE_KEY,
    null,
  );

  // The persisted selection is read once per load; a ref keeps the fetch
  // effect from re-running every time the stored value changes.
  const storedIdRef = useRef(storedId);
  useEffect(() => {
    storedIdRef.current = storedId;
  }, [storedId]);

  const isAuthenticated = status === 'authenticated';

  useEffect(() => {
    if (!isAuthenticated) return;
    let cancelled = false;
    organizationsService
      .listOrganizations()
      .then((result) => {
        if (cancelled) return;
        setPage(result);
        setSelected(
          result.data.find(
            (organization) => organization.id === storedIdRef.current,
          ) ?? null,
        );
      })
      .catch(() => {
        if (cancelled) return;
        setPage(EMPTY_PAGE);
      });
    return () => {
      cancelled = true;
    };
  }, [isAuthenticated, user?.id]);

  const selectOrganization = useCallback(
    (organization: Organization | null) => {
      setSelected(organization);
      setStoredId(organization?.id ?? null);
    },
    [setStoredId],
  );

  // While signed out, expose an empty organization set regardless of what an
  // earlier session left in memory.
  const value = useMemo(() => {
    const organizations = isAuthenticated ? (page?.data ?? []) : [];
    const activeOrganization = isAuthenticated ? selected : null;
    const loading = isAuthenticated && page === null;
    return { organizations, activeOrganization, loading, selectOrganization };
  }, [isAuthenticated, page, selected, selectOrganization]);

  return (
    <ActiveOrganizationContext.Provider value={value}>
      {children}
    </ActiveOrganizationContext.Provider>
  );
}

export function useActiveOrganization(): ActiveOrganizationContextValue {
  const context = useContext(ActiveOrganizationContext);
  if (!context) {
    throw new Error(
      'useActiveOrganization must be used within an ActiveOrganizationProvider.',
    );
  }
  return context;
}
