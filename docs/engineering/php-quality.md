# PHP and Laravel Quality

Format PHP with Laravel Pint; `./vendor/bin/pint --test` is the check. PHPStan/Larastan and Psalm are not configured in Sprint 0.18, so agents must not invent a static-analysis command or add one merely for this baseline. Their future adoption is recommended when the application surface justifies its configuration and remediation plan.

New domain and application classes should use `declare(strict_types=1);` where it fits the established file pattern, meaningful parameter/return/property types, and `mixed` only for a real boundary that cannot be made precise. Controllers remain thin: request parsing, framework validation/authorization, application use case, response. Business workflows do not belong in controllers, Eloquent models, migrations, or resources.

Models may contain relationships, casts, and small local invariants. Prefer Eloquent/query builder over raw SQL when it is clearer; justified raw SQL remains tenant-safe and parameterized. Do not use floats for money, direct `env()` calls outside configuration, hidden global state, or generic exception catches that merely continue execution.

Use explicit validation, domain-oriented names, guard clauses, and cohesive functions. Expected failures are handled or translated at a suitable boundary; unexpected failures reach centralized handling. Tests cover important business rules and tenancy/authorization boundaries.
