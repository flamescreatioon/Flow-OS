# Flutter and Dart Quality

Run `dart format --output=none --set-exit-if-changed .`, `flutter analyze`, and `flutter test` for mobile changes. `analysis_options.yaml` includes `flutter_lints` plus project rules; do not disable null safety or weaken rules to hide debt. Existing analyzer findings are recorded in the current audit and must not increase.

Use explicit types and avoid `dynamic` except at a documented untyped/external boundary. Prefer nullable handling to casual `!` assertions. Keep imports clean and follow the repository's Dart import convention. Widgets handle presentation and UI state; business logic, financial calculations, and sync rules stay behind testable application/domain boundaries.

Use `int` minor units for authoritative money. Follow the documented local-first sync design; never use client clocks as server ordering authority. Generated Freezed/JSON files are not handwritten sources; see [generated-code.md](generated-code.md).
