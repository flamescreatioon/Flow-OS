# Generated Code

Generated artifacts are outputs, not sources of truth. Do not hand-edit Freezed/JSON files, designer output, or future generated OpenAPI clients/models unless the owning generator explicitly requires it. Edit the source annotation/schema/contract and regenerate.

For Flutter, Freezed and JSON serialization output is generated from annotated Dart sources using `dart run build_runner build --delete-conflicting-outputs` when generation is needed. Future API clients derive from the approved OpenAPI/schema source, never the reverse. Generated files may receive appropriately narrow lint exclusions, but that never exempts handwritten source.

Commit generated output only when the repository's generator workflow expects it. Regeneration must be reproducible from declared tools and should be accompanied by the relevant format/analyze/test checks.
