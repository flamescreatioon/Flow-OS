// Shared TypeScript types for the web application

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
  organization_id: string;
  created_at: string;
  updated_at: string;
}

export interface Organization {
  id: string;
  name: string;
  slug: string;
  timezone: string;
  currencies: string[];
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