// Shared TypeScript types for the web application.
// Shapes mirror the Laravel API resources (see packages/contracts/openapi/v1.yaml).

export interface ApiResponse<T> {
  data: T;
  meta?: Record<string, unknown>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface User {
  id: string;
  name: string;
  email: string;
  status: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export type OrganizationStatus = 'active' | 'suspended' | 'closed';

export interface Organization {
  id: string;
  name: string;
  slug: string;
  legal_name: string | null;
  registration_number: string | null;
  tax_identification_number: string | null;
  phone: string | null;
  email: string | null;
  country_code: string | null;
  currency_code: string;
  timezone: string;
  status: OrganizationStatus;
  created_at: string;
  updated_at: string;
}

export interface Branch {
  id: string;
  organization_id: string;
  name: string;
  address?: string;
  phone?: string;
  email?: string;
  is_main: boolean;
  created_at: string;
  updated_at: string;
}

export interface MfaChallenge {
  mfa_required: true;
  challenge: string;
  expires_at: string;
  methods: string[];
}
