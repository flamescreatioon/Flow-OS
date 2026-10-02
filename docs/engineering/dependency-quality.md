# Dependency Quality

Before adding a dependency, determine whether the platform/framework already solves the problem, whether the package is maintained and secure, whether it materially reduces complexity, and whether it affects vendor lock-in, offline behavior, security, or financial correctness. Prefer native/framework capability for trivial work. Respect lockfiles and do not run broad upgrade/regeneration commands during unrelated work.

Major upgrades and architectural dependencies require deliberate review; significant architectural choices require an ADR. Pin or constrain versions according to the existing ecosystem conventions. Record justified additions in the change description, including alternatives considered and required verification.

Vulnerability warnings are not compiler warnings and must not be hidden. Assess safe patch/minor upgrades separately; defer only with an explicit debt record, severity, affected package, and follow-up owner/decision. Current desktop advisories are listed in the [current audit](current-code-quality-audit.md).
