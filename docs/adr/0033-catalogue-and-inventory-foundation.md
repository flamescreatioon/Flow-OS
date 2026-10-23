# ADR 0033: Catalogue and Inventory Foundation

Status: ACCEPTED
Date: 2026-10-21

## Context

Sprint 2 is the first business domain slice for flowOS. The catalogue and inventory boundaries are
already accepted in `docs/domain/catalogue-domain.md` and `docs/domain/inventory-domain.md`, and
the governing invariants are recorded in `docs/domain/domain-invariants.md`.

Three decisions are not settled by those documents and must be recorded before the foundation is
built.

**Quantity and price representation.** `docs/finance/precision-and-scale.md` requires exact
decimals for quantities and unit prices, with locale-neutral string transport across the API, and
`docs/finance/quantities-and-unit-prices.md` confirms that quantities are not money and may be
fractional against a unit of measure. `docs/database/money.md` allows either integer minor units
or field-specific `NUMERIC(p,s)`, and explicitly forbids a universal scale.

**Negative stock.** `docs/domain/inventory-domain.md` states that negative-stock policy is an
organization or product decision, and `docs/domain/open-domain-questions.md` lists it as deferred.
A foundation still has to behave in some defined way, and a client must not be able to make the
decision implicitly.

**Correction and balance semantics.** Invariant 4 states that stock state changes only through
valid `StockMovement` records, and invariant 6 states that finalized inventory movements use
explicit correction semantics rather than casual mutation or deletion. The concrete mechanism for
both corrections and the derived balance has to be fixed.

## Decision

### Exact decimals as scaled integers

Quantities and unit prices are stored as scaled `BIGINT` integers and transported as
locale-neutral decimal strings.

- Quantity scale is **3 decimal places**. A quantity is stored as thousandths of one unit of
  measure, so 3.125 units is stored as `3125`.
- Unit price scale is **4 decimal places**. A unit price is stored as ten-thousandths of the
  currency's major unit, so 82.375 is stored as `823750`.
- The API accepts and returns decimal strings with a period separator and no commas, for example
  `"3.125"` and `"82.3750"`. Clients keep the string until they parse it exactly.

This is the integer-minor-units pattern from `docs/database/money.md` applied to quantities, with a
per-field scale chosen for the domain. It is preferred over `NUMERIC(p,s)` for two reasons. First,
PostgreSQL `NUMERIC` and SQLite `NUMERIC` affinity do not have the same storage behaviour, and
SQLite converts non-integer values to floating point, so a `NUMERIC` column would be exact on
PostgreSQL and inexact on SQLite. Second, scaled integers keep the arithmetic exact in the
application layer, where the balance and the movement total are computed, instead of relying on
each database's decimal arithmetic.

The scale is a domain-selected constant, not a global rule. A quantity multiplied by a unit price
yields seven decimal places of intermediate precision, which is more than the six required by the
financial test vectors, and intermediate results are never truncated.

### Negative stock is permitted in the foundation, and explicitly so

A stock movement is recorded even when it drives the balance for that product and location below
zero. The foundation does not silently block the movement and does not silently allow a client to
decide the policy: the behaviour is a documented default, and the balance exposes the negative
position so the business can act on it.

This is deliberate for the target market. A sale of an item that has not yet been received is a
real event in a retail operation with supplier lead times, and blocking it would make the
foundation unable to record reality. An organization-level policy that forbids negative stock is a
later decision, recorded in a later ADR, and it will be applied as a validation rule at the
movement boundary rather than as a database constraint, because the policy is per organization.

### Movements are immutable and corrections are new movements

A recorded `StockMovement` is never updated and never deleted. There is no endpoint that updates
or deletes a movement, and there is no endpoint that writes a stock balance.

A correction is a new movement that references the movement it corrects. The referenced movement
is identified by `corrects_movement_id`, which must point at a movement of the same organization,
product, and location. A correction must move stock in the opposite direction to the movement it
corrects, so it either reverses that movement exactly or states the smaller difference that was
wrong. A correction can never move stock in the same direction as the movement it corrects, and a
movement that has already been corrected cannot be corrected again.

A stock count is not a correction and not a direct balance write. The server computes the variance
between the counted quantity and the current balance and records the difference as a `count`
movement. The client supplies what was counted, never the canonical quantity.

A transfer creates a paired outbound and inbound movement that reference each other, so the two
halves cannot drift apart.

### Stock balance is a derived read model

`StockBalance` is a read model maintained transactionally inside the same transaction that records
a movement. It is never business authority: the movement records are the authority, and the
balance is derived from them. There is no endpoint that writes a balance directly, so invariant 4
holds by construction rather than by convention.

### Product types govern whether stock may move

A product is `stocked`, `non_stocked`, or `service`. Only a `stocked` product may have stock
movements and a stock balance. A `non_stocked` product is bought and resold without being tracked,
and a `service` has no stock at all. A movement against a product of the wrong type is rejected.

### Catalogue scope and money

The catalogue owns `Product`, `Category`, and `UnitOfMeasure`. A product carries a name, an
optional SKU that is unique within the organization, a type, an optional category, a unit of
measure, a tax classification string, and a list price.

A product's price is a unit price in the organization's currency. The currency is not duplicated
on the product, because price books and branch-specific pricing are a deferred decision.

Units of measure are organization-configurable. A unit carries a code that is unique within the
organization and the number of decimal places that clients may use for quantities of that unit.
The storage scale is fixed by the platform; the unit's precision constrains input, so a unit with
precision `0` accepts only whole quantities while a unit with precision `3` accepts quantities
such as `3.125`.

Categories are flat. Nesting is not introduced, because the catalogue domain does not define a
hierarchy and inventing one would be a design decision that is not recorded anywhere.

Products and categories are archived rather than deleted, because a product referenced by a
movement must remain readable.

## Alternatives Considered

- Store quantities and prices as `NUMERIC(p,s)`: rejected. SQLite stores non-integer `NUMERIC`
  values with floating point affinity, so the same schema would be exact on PostgreSQL and inexact
  on SQLite, and the two engines would disagree on balances. Scaled integers are exact on both.
- Store quantities as `float` or `double`: rejected. Binary floating point cannot represent
  fractional quantities exactly, and stock balances are sums of movements.
- Use a single universal scale for every field: rejected. `docs/database/money.md` explicitly
  rejects a universal precision and scale rule; quantity and unit price have different needs.
- Block negative stock in the foundation: rejected. It would make the foundation unable to record
  a real sale of an unreceived item, and the policy is explicitly an organization decision.
- Allow a client to write a stock balance directly: rejected. Invariant 4 forbids it, and it would
  make the balance authoritative over the movement records.
- Update or delete movements to correct them: rejected. Invariant 6 requires explicit correction
  semantics, and mutating a movement destroys the history that the balance was derived from.
- Introduce a category hierarchy: rejected. The catalogue domain does not define one.
- Store a currency on every product: rejected. The organization's currency applies, and price books
  are a deferred decision.

## Consequences

### Positive

- Stock state has a single authoritative representation: the movement records.
- Balances are exact on both supported database engines, and the arithmetic that produces them is
  exact in the application layer.
- A client cannot set a canonical quantity, because no endpoint accepts one.
- Corrections preserve history, and a transfer cannot be half-applied.
- The negative-stock default is explicit and testable rather than an accident of validation order.

### Negative / Trade-offs

- Quantities and prices cross the API as strings rather than JSON numbers, which is less
  convenient for clients and must be handled deliberately in the web, mobile, and desktop clients.
- Scaled integers require conversion at the API boundary, and a mistake in the scale is a silent
  data error rather than a type error. The conversion is therefore a single value object with its
  own tests.
- The foundation permits negative balances. A business that needs a hard stop must implement the
  deferred organization policy before relying on the balance as a constraint.
- Categories are flat, so a large catalogue may need a hierarchy later, which would be a data
  migration.

## Security / Tenancy Impact

Every catalogue and inventory record is organization-scoped and carries an explicit
`organization_id`. A record from another organization is never addressable, and the API rejects a
cross-organization reference rather than resolving it. Inventory locations belong to one
organization and may optionally sit inside a branch of that same organization; a location that
belongs to another organization is not found, following the existing branch rule.

Reading and changing the catalogue and stock requires the organization membership and the
matching permission. Stock movements are additionally subject to the idempotency middleware, so a
retried write cannot record the same movement twice.

## Offline / Data Impact

The catalogue is offline-capable: products, categories, and units of measure are organization
scoped reference data that a client can cache. Stock movements are high-sensitivity and are
offline-queued rather than created offline, and the reconciliation of queued movements belongs to
the sync protocol in Sprint 4. The `reference` field on a movement carries the business reference
that links a queued movement to its source document once that document exists.

## References

- `docs/domain/catalogue-domain.md`, `docs/domain/inventory-domain.md`
- `docs/domain/domain-invariants.md`, `docs/domain/tenant-scoping.md`
- `docs/finance/precision-and-scale.md`, `docs/finance/quantities-and-unit-prices.md`,
  `docs/finance/test-vectors.md`
- `docs/database/money.md`
- ADR 0023 (tenancy and authorization), ADR 0024 (modular monolith architecture)

## Supersedes / Superseded By

Implements the boundaries accepted in ADR 0023 and the modular monolith style of ADR 0024. It does
not change the tenancy model, the authorization model, or the offline model.
