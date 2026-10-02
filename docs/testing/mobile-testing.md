# Mobile Testing

Flutter uses `flutter test` for unit and widget tests. Unit tests cover pure configuration, serialization, domain/application decisions, and future sync state without emulator or network. Widget tests assert meaningful rendering or interaction. Integration tests are reserved for future login, offline operations, reconnect, POS, and other end-to-end mobile flows.

The current app configuration test validates the tenant-facing default name, currency, and timezone. The home-screen widget test validates the current bootstrap UI. No golden or emulator-driven test foundation is introduced in Sprint 0.

`SyncEngine` is a prototype, not a production sync contract. Do not add tests that normalize UUIDv4 operation IDs, timestamp cursors, replace-on-pull, generic CRUD sync, or universal last-write-wins behavior.
