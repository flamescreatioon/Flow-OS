// Shared configuration for all applications

export const config = {
  app: {
    name: 'flowOS',
    version: '0.0.0',
    description: 'Business Operating System for Nigerian Businesses',
  },
  api: {
    version: 'v1',
    basePath: '/api/v1',
  },
  currency: {
    default: 'NGN',
    supported: ['NGN', 'USD', 'EUR', 'GBP'],
  },
  date: {
    defaultTimezone: 'Africa/Lagos',
    defaultLocale: 'en-NG',
  },
  pagination: {
    defaultPageSize: 25,
    maxPageSize: 100,
  },
  id: {
    strategy: 'uuidv7',
  },
  multitenancy: {
    tenantEntity: 'organization',
    identifierHeader: 'X-Organization-ID',
  },
} as const;