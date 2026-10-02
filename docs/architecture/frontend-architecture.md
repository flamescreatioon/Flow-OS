# Frontend Architecture

## Next.js Application Structure

```
apps/web/
├── src/
│   ├── app/                    # App Router pages
│   │   ├── (auth)/            # Auth group (login, register)
│   │   ├── (dashboard)/       # Protected dashboard routes
│   │   ├── api/               # API routes (proxy, webhooks)
│   │   ├── layout.tsx         # Root layout
│   │   └── page.tsx           # Landing page
│   ├── components/
│   │   ├── ui/                # Base UI components (Button, Input, etc.)
│   │   ├── layout/            # Layout components (Header, Sidebar)
│   │   └── shared/            # Shared composite components
│   ├── features/              # Feature modules (lazy loaded)
│   │   └── [feature]/
│   │       ├── components/
│   │       ├── hooks/
│   │       ├── services/
│   │       └── types/
│   ├── hooks/                 # Shared hooks
│   ├── lib/                   # Utilities, API client
│   ├── services/              # Business logic services
│   ├── stores/                # Global state (Zustand/Redux)
│   ├── types/                 # TypeScript types
│   ├── utils/                 # Helper functions
│   └── config/                # App configuration
├── public/                    # Static assets
└── tests/
    ├── unit/
    ├── integration/
    └── e2e/
```

## Key Patterns

### Server Components by Default
- Use React Server Components for data fetching
- Client components only when needed (interactivity)
- `'use client'` directive at top of file

### Data Fetching
```tsx
// Server component - fetch directly
async function Dashboard() {
  const data = await api.get('/dashboard/stats');
  return <StatsView data={data} />;
}

// Client component - use SWR/TanStack Query
'use client';
function Dashboard() {
  const { data } = useSWR('/dashboard/stats', fetcher);
  return <StatsView data={data} />;
}
```

### API Client
```typescript
// lib/api.ts
const api = new ApiClient(process.env.NEXT_PUBLIC_API_URL);

// Type-safe calls
const organizations = await api.get<Organization[]>('/organizations');
```

### State Management
- Server state: TanStack Query / SWR
- Client state: Zustand / React Context
- Form state: React Hook Form + Zod

### Styling
- Tailwind CSS for utility-first styling
- Design tokens from `@flowos/design-tokens`
- Component variants with `class-variance-authority`

## Authentication Flow

```
1. User visits protected route
2. Middleware checks for auth cookie
3. If missing → redirect to /login
4. Login form → POST /api/v1/auth/login
5. Server sets HttpOnly cookie
6. Redirect to dashboard
```

## Offline Support (Future)

- Service Worker for caching
- IndexedDB for local data
- Background sync when online
- Optimistic UI updates

## Performance

- Code splitting via dynamic imports
- Image optimization with `next/image`
- Font optimization with `next/font`
- Bundle analysis with `@next/bundle-analyzer`

## Testing

- Unit: Jest + React Testing Library
- Integration: MSW for API mocking
- E2E: Playwright
- Visual: Storybook + Chromatic