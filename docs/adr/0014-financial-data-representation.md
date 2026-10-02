# ADR 0014: Financial Data Representation

## Title

Use exact, currency-aware financial values.

## Status

Accepted

## Context

Financial operations span PostgreSQL, PHP, TypeScript, Dart, C#, APIs, and offline SQLite clients.

## Decision

Authoritative money excludes binary floats; use BIGINT minor units where suitable and field-specific exact decimals where needed, ISO 4217 currencies, explicit rounding, deterministic cross-platform vectors, and immutable snapshots for finalized records.

## Consequences

No money library is selected now; financial modules must implement exact value objects/calculations and server validation later.
