// Web application configuration

export const config = {
  app: {
    name: 'flowOS',
    description: 'Business Operating System for Nigerian Businesses',
    version: '0.0.0',
  },
  api: {
    baseUrl: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1',
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