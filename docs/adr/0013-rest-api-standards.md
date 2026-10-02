# ADR 0013: REST API Standards

## Title

Adopt REST API standards for flowOS.

## Status

Accepted

## Context

Clients need stable cross-platform contracts while APIs remain membership-aware and offline-compatible.

## Decision

Use REST under `/api/v1`, JSON snake_case envelopes, Laravel Resources/Form Requests where endpoints are implemented, standard machine-readable API errors, OpenAPI as public contract, Sanctum authentication, membership-aware tenant selection, and future idempotency/concurrency controls.

## Consequences

Future modules follow the standards in `docs/api`; speculative OpenAPI paths must be labelled planned. No generic CRUD framework or sync API is introduced.
