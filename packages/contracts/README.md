# Contracts Package

This package contains shared contract definitions that are conceptually shared between clients and backend.

## Important Note

Cross-language applications (TypeScript, PHP, Dart, C#) **cannot directly share TypeScript types**. Each language needs its own type definitions.

## API Contracts

API contracts should be generated from an API specification such as **OpenAPI (Swagger)**. This ensures:

1. Single source of truth for API contracts
2. Type-safe clients for all languages
3. Automatic validation of requests/responses
4. Documentation that stays in sync with implementation

## Structure

```
packages/contracts/
├── openapi/          # OpenAPI/Swagger specifications
│   └── v1.yaml       # Main API specification
├── schemas/          # JSON Schema definitions
│   └── ...
├── examples/         # Example JSON payloads
│   ├── health-response.json
│   └── api-error.json
└── README.md         # This file
```

## Generation Workflow (Future)

The OpenAPI specification is the source of truth for API contracts. Client generation is **not yet implemented**. When client generation is adopted, the workflow will be:

1. Define API in `openapi/v1.yaml`
2. Generate TypeScript types for web (tool TBD)
3. Generate PHP types for Laravel (tool TBD)
4. Generate Dart types for Flutter (tool TBD)
5. Generate C# types for .NET (tool TBD)

The specific generators (Orval, NSwag, openapi-generator, dio_generator, openapi-psr7, etc.) are **not yet selected**. Tool selection will be documented via an ADR when client generation is implemented.

Currently, applications manually define their own type definitions aligned with the OpenAPI specification.

## Versioning

API versions follow the URL pattern: `/api/v1/`, `/api/v2/`, etc.

Each version should have its own OpenAPI spec file.

## Breaking Changes

Never introduce breaking changes without version bump. Deprecate fields before removal.