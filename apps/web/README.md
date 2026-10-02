# flowOS Web Application

The Next.js frontend for flowOS — an offline-capable business operating system for Nigerian businesses.

## Technology Stack

- **Framework**: Next.js 14 (App Router)
- **Language**: TypeScript (strict mode)
- **Styling**: Tailwind CSS
- **Testing**: Jest + React Testing Library
- **Linting**: ESLint (eslint-config-next)

## Directory Structure

```
apps/web/
├── public/                 # Static assets
├── src/
│   ├── app/               # App Router pages
│   │   ├── (auth)/        # Auth route group (placeholder)
│   │   ├── (dashboard)/   # Dashboard route group (placeholder)
│   │   ├── api/           # API routes (health check)
│   │   ├── layout.tsx     # Root layout
│   │   ├── page.tsx       # Home page
│   │   ├── globals.css    # Global styles
│   │   └── not-found.tsx  # 404 page
│   │
│   ├── components/
│   │   ├── ui/            # Low-level reusable UI components
│   │   ├── layout/        # Structural components (Sidebar, Header, etc.)
│   │   └── shared/        # Application-level shared components
│   │
│   ├── features/          # Business feature modules (lazy-loaded)
│   │   └── index.ts       # Feature registry placeholder
│   │
│   ├── hooks/             # Shared React hooks
│   ├── lib/               # Technical infrastructure (API client, etc.)
│   ├── services/          # Business logic services
│   ├── stores/            # Global state management (future)
│   ├── types/             # Web-specific TypeScript types
│   ├── utils/             # Utility functions
│   └── config/            # Application configuration
│
├── tests/
│   ├── unit/              # Unit tests
│   ├── integration/       # Integration tests
│   └── e2e/               # End-to-end tests
│
├── .env.example           # Environment variables template
├── .eslintrc.json         # ESLint configuration
├── jest.config.js         # Jest configuration
├── next.config.js         # Next.js configuration
├── package.json           # Dependencies and scripts
├── postcss.config.js      # PostCSS configuration
├── tailwind.config.js     # Tailwind CSS configuration
└── tsconfig.json          # TypeScript configuration
```

## Feature-First Architecture

Business features are organized under `src/features/` using a feature-first approach:

```
src/features/
├── inventory/
│   ├── components/
│   ├── hooks/
│   ├── services/
│   ├── types/
│   └── index.ts
├── pos/
│   └── ...
└── ...
```

Each feature encapsulates its own components, hooks, services, and types. The `features/index.ts` registry documents the intended feature modules.

## Development

### Prerequisites

- Node.js 20 LTS
- npm (comes with Node.js)

### Commands

```bash
# Install dependencies
npm install

# Start development server
npm run dev          # http://localhost:3000

# Build for production
npm run build

# Start production server
npm start

# Linting
npm run lint

# Type checking
npm run typecheck

# Testing
npm run test         # Non-interactive test run
npm run test:watch   # Interactive watch mode
npm run test:coverage # Coverage report
```

### Environment Variables

Copy `.env.example` to `.env.local` and configure:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

| Variable | Description | Default |
|----------|-------------|---------|
| `NEXT_PUBLIC_API_URL` | Base URL for API calls | `http://localhost:8000/api/v1` |
| `NEXT_PUBLIC_APP_URL` | Application URL | `http://localhost:3000` |

### API Integration

API calls are centralized in `src/lib/api.ts` using the `ApiClient` class. The base URL is configured via `NEXT_PUBLIC_API_URL`.

```typescript
import { api } from '@/lib/api';

const organizations = await api.get<Organization[]>('/organizations');
```

## Architecture Guidelines

- **App Router**: Use Server Components by default, Client Components only when needed (`'use client'`)
- **Domain Separation**: Business logic belongs in Domain/Application layers (Laravel API), not in UI components
- **API Layer**: All API communication goes through `src/services/` and `src/lib/api.ts`
- **State Management**: No global state library installed yet — will be evaluated when requirements exist
- **Offline-First**: Structure leaves room for future offline capabilities (local persistence, sync services)

## Testing

```
tests/
├── unit/          # Jest + React Testing Library
├── integration/   # API integration tests
└── e2e/           # Playwright (to be configured)
```

Run tests:

```bash
npm run test         # Non-interactive test run
npm run test:watch   # Interactive watch mode
npm run test:coverage # Coverage report
```

## Code Quality

```bash
npm run lint        # ESLint
npm run typecheck   # TypeScript compiler check
npm run build       # Production build
```

## Conventions

- **Naming**: `PascalCase` for components, `camelCase` for functions/variables
- **Imports**: Use `@/` alias for `src/` (configured in tsconfig.json)
- **TypeScript**: Strict mode enabled — no `any` without justification
- **Imports**: Grouped (external → internal → relative)

## License

Proprietary — Copyright (c) flowOS. All rights reserved.
