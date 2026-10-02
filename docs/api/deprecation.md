# Deprecation

Within v1 prefer optional additive fields/endpoints. Never silently remove or repurpose a field; announce deprecation, provide a migration window, and mark it in OpenAPI. New enum values require clients to tolerate documented evolution, while security-sensitive enums remain strictly validated.
