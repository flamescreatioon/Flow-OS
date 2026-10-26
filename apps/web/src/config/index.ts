// Web application configuration

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

export const config = {
  app: {
    name: 'flowOS',
    description: 'Business Operating System for Nigerian Businesses',
    version: '0.0.0',
  },
  api: {
    baseUrl: API_BASE_URL,
    // The Sanctum CSRF cookie endpoint lives at the API origin root,
    // outside the /api/v1 prefix.
    csrfCookieUrl: `${new URL(API_BASE_URL).origin}/sanctum/csrf-cookie`,
  },
  auth: {
    loginPath: '/login',
    dashboardPath: '/dashboard',
  },
  pagination: {
    defaultPageSize: 25,
  },
  currency: {
    default: 'NGN',
    locale: 'en-NG',
  },
  date: {
    locale: 'en-NG',
    timezone: 'Africa/Lagos',
  },
} as const;
