import { api } from '@/lib/api';
import type { ApiResponse, Organization, PaginatedResponse } from '@/types';

export async function listOrganizations(): Promise<PaginatedResponse<Organization>> {
  return api.get<PaginatedResponse<Organization>>('/organizations?per_page=100');
}

export async function createOrganization(input: {
  name: string;
  slug: string;
  timezone?: string;
  currency?: string;
}): Promise<Organization> {
  const body = await api.post<ApiResponse<Organization>>('/organizations', input);
  return body.data;
}
