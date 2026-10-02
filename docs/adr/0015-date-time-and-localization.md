# ADR 0015: Date, Time and Localization

## Title

Use UTC instants, IANA organization timezones, and separate calendar semantics.

## Status

Accepted

## Context

flowOS spans offline clients, organizations, and future international timezones.

## Decision

Use UTC `TIMESTAMPTZ` instants, IANA timezone IDs with `Africa/Lagos` initial default, separate DATE/time-only/local-zoned types, RFC3339 APIs, organization-zone reporting boundaries, and server-authoritative clock ordering. Locale remains separate from timezone.

## Consequences

Scheduling, DST policy, user/branch zones, fiscal periods, and localization UI remain later work.
