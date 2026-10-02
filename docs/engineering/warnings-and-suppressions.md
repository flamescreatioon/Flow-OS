# Warnings and Suppressions

No new unexplained warning is acceptable. Every warning found during verification is classified as a new regression, pre-existing debt, or environment issue with evidence. A passing build with dependency advisories is not warning-free.

Suppressions are exceptional. Use the narrowest scope and state the reason next to it. `eslint-disable`, `@ts-ignore`, `@ts-nocheck`, Dart `ignore`/`ignore_for_file`, and C# `#pragma warning disable` must never broadly silence handwritten code without a documented, reviewable rationale. Global warning disabling is prohibited unless formally justified by an ADR or equivalent approved record.

Generated code may be excluded only from rules that cannot apply to generated output; handwritten code must not be placed in generated paths to evade checks. Do not use a suppression to bypass type, nullable, security, financial, tenant, or temporal correctness.
