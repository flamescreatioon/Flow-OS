# API Date/Time Format

Instants are RFC3339/ISO8601 strings with offset, with server output preferring `2026-09-28T12:15:42Z`; fractional seconds are optional. Dates are `2026-09-28`; times are `09:30:00`. Valid offset input may be normalized to UTC. APIs reject ambiguous localized input.
