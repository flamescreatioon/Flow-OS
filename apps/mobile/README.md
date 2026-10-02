# flowOS Mobile Application

The Flutter mobile application for flowOS — an offline-capable business operating system for Nigerian businesses.

## Technology Stack

- **Framework**: Flutter 3.x
- **Language**: Dart 3.x
- **State Management**: Riverpod (currently configured)
- **Networking**: Dio (currently configured)
- **Local Database**: sqflite (currently configured)
- **Local Storage**: shared_preferences (currently configured)
- **Serialization**: freezed + json_serializable (currently configured)
- **Architecture**: Feature-first with shared core

## Supported Platforms

- Android (primary)
- iOS (primary)

Other Flutter platforms (web, Windows, macOS, Linux) are not the focus of this mobile application.

## Directory Structure

```
apps/mobile/
├── android/              # Android platform code (generated)
├── ios/                  # iOS platform code (generated)
├── lib/
│   ├── app/              # Application startup and composition
│   │   ├── app.dart      # Application root widget
│   │   ├── bootstrap.dart # Application initialization
│   │   └── router/       # Routing configuration
│   │
│   ├── core/             # Technical infrastructure (shared across features)
│   │   ├── config/       # Application configuration
│   │   ├── database/     # Local database (SQLite)
│   │   ├── errors/       # Error handling
│   │   ├── networking/   # API client
│   │   ├── storage/      # Local storage
│   │   ├── sync/         # Synchronization engine
│   │   └── utils/        # Utility functions
│   │
│   ├── shared/           # Reusable application-level pieces
│   │   ├── models/       # Data models
│   │   ├── services/     # Cross-feature services
│   │   └── widgets/      # Reusable UI components
│   │
│   ├── features/         # Business feature modules (future)
│   │
│   └── main.dart         # Application entry point
│
├── test/
│   ├── unit/             # Unit tests
│   ├── widget/           # Widget tests
│   └── integration/      # Integration tests
│
├── .env.example          # Environment variables template
├── analysis_options.yaml # Dart linting rules
├── pubspec.yaml          # Dependencies and metadata
└── README.md             # This file
```

## Feature-First Architecture

Business features will be organized under `lib/features/`:

```
lib/features/
├── inventory/
│   ├── components/
│   ├── hooks/
│   ├── services/
│   ├── types/
│   └── index.dart
├── pos/
├── sales/
├── invoices/
└── ...
```

Each feature encapsulates its own components, hooks, services, and types.

## Development

### Prerequisites

- Flutter SDK 3.16+
- Dart SDK 3.3+
- Android Studio / Xcode for platform-specific builds

### Commands

```bash
# Install dependencies
flutter pub get

# Generate code (freezed, json_serializable, riverpod)
flutter pub run build_runner build --delete-conflicting-outputs

# Run development app
flutter run

# Run tests
flutter test                    # All tests
flutter test test/widget/       # Widget tests only
flutter test test/unit/         # Unit tests only
flutter test test/integration/  # Integration tests

# Linting and analysis
flutter analyze

# Format code
dart format .

# Build for Android (debug)
flutter build apk --debug

# Build for iOS (debug, requires macOS)
flutter build ios --debug --no-codesign

# Check Flutter environment
flutter doctor -v
```

### Environment Variables

Copy `.env.example` and configure:

```bash
# Note: Flutter doesn't auto-load .env. Use flutter_dotenv or --dart-define
API_BASE_URL=http://10.0.2.2:8000/api/v1
APP_ENV=development
```

## Architecture

### App Layer (`lib/app/`)
- `main.dart` - Entry point, wraps app in ProviderScope
- `app.dart` - Root widget (MaterialApp with theming)
- `bootstrap.dart` - Application initialization (database, logging, sync)
- `router/` - Routing configuration (currently using built-in navigation)

### Core Layer (`lib/core/`)
- `config/` - Application configuration (AppConfig with theming, API URL)
- `database/` - Local SQLite database (sqflite)
- `errors/` - Error handling (AppError hierarchy)
- `networking/` - API client (Dio-based)
- `storage/` - Local storage (shared_preferences)
- `sync/` - Synchronization engine (outbox pattern)
- `utils/` - Utility functions (date, currency, string formatting)

### Shared Layer (`lib/shared/`)
- `models/` - Data models (Organization, Branch, User with freezed)
- `services/` - Cross-feature services (AuthService, OrganizationService, SyncService)
- `widgets/` - Reusable UI components (LoadingWidget, EmptyStateWidget, ErrorWidget)

### Features (`lib/features/`)
Future business modules will live here.

## Offline-First Architecture

Future offline-capable business actions should follow:

```
User action
   ↓
Validate locally
   ↓
Write locally
   ↓
Update UI
   ↓
Queue synchronization
   ↓
Sync when connectivity permits
```

The application should not require an API round trip before every important business action.

## Record Identity

Synchronized business records should use globally unique identifiers:

**UUIDv7** — enables records to be safely created while offline.

## Multi-Tenancy Readiness

Future mobile records will need organization context:

- `organization_id`
- `branch_id` (where applicable)

## Security Expectations

- Authentication tokens must not be stored as plain text
- Secrets must not be hardcoded
- Sensitive local data requires appropriate secure storage
- API authorization must be enforced by the server
- Organization context must never replace server-side authorization

## Money Rules

- Initial default currency: **NGN**
- Never use Dart `double` as authoritative monetary storage
- Future money representation must use integer minor units or another precise representation
- Formatting and calculation are separate responsibilities

## Date & Time Rules

- APIs communicate standardized timestamps (ISO 8601)
- Backend timestamps are UTC
- Organization timezone is applied where appropriate
- Initial Nigerian deployments may use `Africa/Lagos`
- Do not hardcode Lagos timezone throughout the app

## Connectivity

Future application will need awareness of:
- `online`
- `offline`
- `degraded connectivity`

Actual connectivity management belongs to later offline implementation.

## Background Work

Background synchronization not implemented yet. Future work will require:
- Android WorkManager
- iOS background fetch
- Platform-specific design

## Platform Permissions

The bootstrap application does not request special permissions.

Future modules should request permissions only when needed:
- Camera (barcode scanning)
- Location
- Storage
- Bluetooth

## Testing

```bash
flutter test                    # All tests
flutter test test/widget/       # Widget tests
flutter test test/unit/         # Unit tests
flutter test test/integration/  # Integration tests
flutter test --coverage         # With coverage
```

## Linting & Formatting

```bash
flutter analyze                 # Static analysis
dart format .                   # Format code
dart format --set-exit-if-changed .  # Verify formatting (CI)
```

## Dependencies

Current `pubspec.yaml` dependencies:
- flutter, cupertino_icons
- flutter_riverpod, riverpod_annotation (state management)
- dio (networking)
- shared_preferences (local storage)
- sqflite, path (local database)
- freezed_annotation, json_annotation (serialization)
- uuid (identifiers)
- logger (logging)

Dev dependencies:
- flutter_lints (linting)
- build_runner, riverpod_generator, freezed, json_serializable (code generation)

## Offline-First Direction

The application structure is designed for future offline-first capabilities:
- Local database (`lib/core/database/`) for local source of truth
- Sync engine (`lib/core/sync/`) for background synchronization
- Local storage (`lib/core/storage/`) for preferences and secure data
- Models with `synced_at` tracking for incremental sync

## Sync Boundary

The application will eventually implement:
```
UI
 ↓
Application / Feature
 ↓
Local database
 ↓
Sync engine
 ↓
Laravel API
```

## API Relationship

The mobile app communicates with the Laravel API at `/api/v1`.
API contracts defined in `packages/contracts/openapi/`.

## What Is NOT Implemented Yet

- Authentication / Login / Registration
- Organization / Branch selection
- Dashboard
- POS / Inventory / Products / Sales
- Invoicing / Accounting / Payments
- Customers / Suppliers
- Analytics / Reporting
- Local database (schema defined, not fully implemented)
- Synchronization engine (structure exists, not fully implemented)
- Background sync
- Conflict resolution
- Biometric authentication
- Camera / Barcode scanning
- Push notifications

## License

Proprietary — Copyright (c) flowOS. All rights reserved.