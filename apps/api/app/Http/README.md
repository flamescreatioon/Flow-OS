# HTTP Layer

API-facing controllers, middleware, requests and resources.

## Structure

```
Http/
├── Controllers/
│   └── Api/
│       └── V1/
├── Middleware/
├── Requests/
│   └── Api/
│       └── V1/
└── Resources/
    └── Api/
        └── V1/
```

## Principles

1. Controllers should be thin - delegate to Application layer
2. Use Form Requests for validation
3. Use API Resources for response transformation
4. Version APIs in URL: `/api/v1/`
5. Keep controllers focused on HTTP concerns only