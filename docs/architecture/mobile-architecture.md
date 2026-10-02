# Mobile Architecture

## Flutter Application Structure

```
apps/mobile/
├── lib/
│   ├── app/                   # App entry point
│   │   └── app.dart
│   ├── core/                  # Core infrastructure
│   │   ├── config/            # App configuration
│   │   ├── database/          # Local SQLite (sqflite)
│   │   ├── networking/        # API client (Dio)
│   │   ├── storage/           # Secure/preferences storage
│   │   ├── sync/              # Sync engine
│   │   ├── errors/            # Error handling
│   │   └── utils/             # Helpers
│   ├── shared/                # Shared across features
│   │   ├── widgets/           # Reusable UI components
│   │   ├── models/            # Data models (Freezed)
│   │   └── services/          # Shared services
│   └── features/              # Feature modules
│       └── [feature]/
│           ├── presentation/  # UI (pages, widgets)
│           ├── domain/        # Business logic
│           └── data/          # Repositories, datasources
├── test/
│   ├── unit/
│   ├── widget/
│   └── integration/
└── pubspec.yaml
```

## State Management: Riverpod

```dart
// Provider definition
final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.read(authServiceProvider));
});

// Usage in widget
class LoginPage extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final notifier = ref.read(authProvider.notifier);
    // ...
  }
}
```

## Offline-First Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Local Database (SQLite)              │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────────┐   │
│  │  Orgs   │ │Branches │ │  Users  │ │ Sync Queue  │   │
│  └────┬────┘ └────┬────┘ └────┬────┘ └──────┬──────┘   │
└───────┼────────────┼───────────┼─────────────┼──────────┘
        │            │           │             │
        ▼            ▼           ▼             ▼
┌─────────────────────────────────────────────────────────┐
│                   Application Layer                      │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────┐   │
│  │  Repository │ │  Services   │ │  State (Riverpod)│   │
│  └──────┬──────┘ └──────┬──────┘ └────────┬────────┘   │
└─────────┼───────────────┼─────────────────┼────────────┘
          │               │                 │
          ▼               ▼                 ▼
┌─────────────────────────────────────────────────────────┐
│                      UI Layer                            │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────────┐   │
│  │ Pages   │ │ Widgets │ │ Providers│ │  Navigation  │  │
│  └─────────┘ └─────────┘ └─────────┘ └─────────────┘   │
└─────────────────────────────────────────────────────────┘
```

## Sync Strategy

1. **Local-First Writes**: All mutations go to local DB first
2. **Queue Operations**: Changes queued in `sync_queue` table
3. **Background Sync**: Periodic sync when online
4. **Conflict Resolution**: Server wins, local changes rebased
5. **Optimistic UI**: Immediate feedback, rollback on failure

```dart
// Queue operation for later sync
await syncService.queueCreate(
  entityType: 'products',
  entityId: product.id,
  payload: product.toJson(),
);

// Background sync
Timer.periodic(Duration(minutes: 5), (_) => syncService.sync());
```

## Local Database (sqflite)

```dart
// Schema includes synced_at for incremental sync
// sync_queue table for pending operations
CREATE TABLE sync_queue (
  id TEXT PRIMARY KEY,
  entity_type TEXT NOT NULL,
  entity_id TEXT NOT NULL,
  operation TEXT NOT NULL,  -- CREATE, UPDATE, DELETE
  payload TEXT NOT NULL,    -- JSON
  status TEXT DEFAULT 'pending',
  retry_count INTEGER DEFAULT 0,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);
```

## Networking (Dio)

```dart
// Interceptors for auth, logging, error handling
// Automatic token refresh
// Offline detection
// Request/response logging in debug
```

## Code Generation

- `freezed` for immutable data classes
- `json_serializable` for JSON serialization
- `riverpod_generator` for provider boilerplate
- `build_runner` for code generation

```bash
flutter pub run build_runner build --delete-conflicting-outputs
```

## Navigation

- `go_router` for declarative routing
- Deep linking support
- Auth guards via redirect

## Testing

- Unit: `flutter_test` + `mocktail`
- Widget: `flutter_test` + `golden_toolkit`
- Integration: `integration_test` package
- Static analysis: `flutter analyze`