# ADR 0002: Use Next.js for Web

## Title
Use Next.js for Web Application

## Status
ACCEPTED

## Current Status Note

The decision to use Next.js remains active. The original `Next.js 14+` wording is historical and does not waive the documented framework security requirement: the repository's Next.js 14 line must migrate to a patched 15.5.24+ line before production. See [web architecture](../architecture/web-architecture.md) and [dependency security](../security/dependency-security.md).

## Context
Need a React framework for the web application with:
- Server-side rendering (SEO, performance)
- TypeScript support
- File-based routing
- API routes for BFF pattern
- Good developer experience
- Strong ecosystem

## Decision
Use **Next.js 14+ with App Router**.

## Consequences

### Positive
- **App Router**: Modern React patterns (Server Components, Suspense)
- **TypeScript**: First-class support
- **Performance**: Automatic code splitting, image optimization
- **SEO**: SSR/SSG/ISR for public pages
- **API Routes**: Built-in BFF capability
- **Ecosystem**: Huge community, plugins, Vercel hosting
- **Developer Experience**: Fast refresh, great error overlay

### Negative
- **Learning Curve**: App Router paradigm shift
- **Bundle Size**: Framework overhead
- **Vendor Lock-in**: Vercel-specific features
- **Complexity**: Many rendering modes to understand

### Mitigations
- Use Server Components by default
- Limit Client Components to interactive UI
- Avoid Vercel-specific features
- Document rendering strategy decisions

## Alternatives Considered

### Remix
- **Pros**: Web standards focus, nested routing, great forms
- **Cons**: Smaller ecosystem, no static export (historically)

### Vite + React Router (SPA)
- **Pros**: Simple, fast, no SSR complexity
- **Cons**: No SSR, SEO challenges, manual code splitting

### Astro
- **Pros**: Island architecture, multi-framework
- **Cons**: Less mature for complex apps, smaller ecosystem

## References
- [Next.js App Router](https://nextjs.org/docs/app)
- [Server Components](https://react.dev/reference/rsc/server-components)
