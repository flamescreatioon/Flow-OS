# Types Package

TypeScript-specific shared types for the flowOS web application.

## Purpose

This package contains TypeScript-specific type definitions that are shared within the web application. It is **NOT** the cross-language source of truth for business contracts.

## Important: Cross-Language Boundary

> **TypeScript interfaces are not the cross-platform source of truth.**

Because flowOS uses multiple languages (TypeScript, PHP, Dart, C#), business contracts must be defined in a language-neutral format:

- **Primary source**: OpenAPI specification (`packages/contracts/openapi/v1.yaml`)
- **Generated clients**: Each platform generates its own types from OpenAPI
- **This package**: TypeScript-only utility types and web-specific helpers

## Contents

- `src/index.ts` - TypeScript type definitions for web application
- Core types: `UUID`, `ISODateTime`, `CurrencyCode`, `Timezone`
- Pagination types
- API response wrappers
- Domain entity interfaces (Organization, Branch, User)
- Authentication types
- Money handling utilities
- Common enums (OrderStatus, PaymentStatus, etc.)
- Soft delete support

## Usage

```typescript
import { Organization, User, Money, createMoneyFromMinor, createMoneyFromMajorString, formatMoney } from '@flowos/types';

const org: Organization = { ... };

// Authoritative construction from integer minor units (safe for financial calculations)
const price = createMoneyFromMinor(150050, 'NGN'); // 150050 kobo = ₦1,500.50

// For user input boundaries only — parses decimal string exactly
const userInputPrice = createMoneyFromMajorString('1500.50', 'NGN');

// DISPLAY ONLY — never use formatted value for financial calculations
const formatted = formatMoney(price); // "₦1,500.50"
```

## What NOT to Put Here

- Business contracts that need to be shared with PHP, Dart, or C#
- API request/response types that should come from OpenAPI generation
- Database-specific types
- Server-side validation logic

## Relationship to OpenAPI

```
OpenAPI Spec (packages/contracts/openapi/v1.yaml)
         │
         ├──→ @flowos/types (TypeScript only, web-specific utilities)
         ├──→ Generated PHP models (Laravel)
         ├──→ Generated Dart models (Flutter)
         └──→ Generated C# models (.NET Desktop)
```

Types in this package are **supplementary** to generated API types — they provide:
- Branded types (`UUID`, `ISODateTime`)
- Utility functions (`createMoney`, `formatMoney`, `isUUID`)
- Web-specific type helpers

## Code Generation

Do not manually keep this package in sync with the OpenAPI spec. Instead:

1. Update `packages/contracts/openapi/v1.yaml`
2. Run code generation for each platform:
   - Web: `orval` or `openapi-typescript` → generates API types
   - Mobile: `dio_generator` or `openapi-generator` → Dart models
   - Desktop: `NSwag` or `openapi-generator` → C# models
   - API: `openapi-psr7` → PHP models

## What Should Stay Here

Only types that are:
- Truly TypeScript/web-specific
- Utility functions not expressible in OpenAPI
- Branded types for type safety
- Shared enums used only in web code
- **Authoritative money utilities** (`createMoneyFromMinor`, `createMoneyFromMajorString`, `formatMoney`)

## Versioning

This package follows the monorepo versioning. When OpenAPI changes require type updates, regenerate and bump version.

## Money Utilities

This package provides authoritative money utilities for the web application:

| Function | Purpose | Safety |
|----------|---------|--------|
| `createMoneyFromMinor(amountMinor: number, currency: CurrencyCode): Money` | **Authoritative constructor** — accepts integer minor units directly | ✅ Safe — validates `Number.isSafeInteger()` |
| `createMoneyFromMajorString(amountMajor: string, currency: CurrencyCode): Money` | **Input boundary** — parses exact decimal string to minor units | ✅ Safe — exact string parsing, no floating point |
| `formatMoney(money: Money, locale?: string): string` | **Display only** — formats minor units as localized currency string | ⚠️ Display only — uses float division for formatting only |

**Key principle**: Authoritative money is always represented as integer minor units (`Money.amount`). The `formatMoney` function is explicitly for display formatting and must never be used for financial calculations.