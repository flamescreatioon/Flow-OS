import { api } from '@/lib/api';
import type { ApiResponse, DashboardSummary } from '@/types';

export async function getDashboardSummary(organizationId: string): Promise<DashboardSummary> {
  const body = await api.get<ApiResponse<DashboardSummary>>(
    `/organizations/${organizationId}/dashboard-summary`,
  );
  return body.data;
}
