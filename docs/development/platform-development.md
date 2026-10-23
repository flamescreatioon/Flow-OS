# Platform Development Guide

This guide details day-to-day development across the four client and server applications in the flowOS modular monolith: Web (Next.js), API (Laravel), Mobile (Flutter), and Desktop (.NET WPF).

---

## 1. Web Application (`apps/web`)

The flowOS web frontend is built with **Next.js 15** (App Router), React 19, and TypeScript.

### Setup & Local Execution
```bash
cd apps/web

# Install dependencies cleanly using lockfile
npm ci

# Ensure local environment configuration exists
# (scripts/dev setup automatically copies .env.example if missing)
cp .env.example .env.local

# Start development server
npm run dev
```
The web application is accessible at:
```text
http://localhost:3000
```

### Verification & Quality Commands
```bash
cd apps/web

# Run unit and component tests (Jest + React Testing Library)
npm run test

# Run ESLint check
npm run lint

# Run TypeScript typechecker (non-incremental full typecheck)
npm run typecheck

# Production build check
npm run build
```

### Authentication Direction
Web authentication uses **Laravel Sanctum SPA stateful cookie sessions**. The web app communicates with the API via session cookies over CORS, with stateful domains configured in `apps/api/.env` (`SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000`).
> [!NOTE]
> Authentication UI screens and API login flows are scheduled for Sprint 1. Do not assume live authentication endpoints exist during Sprint 0.

### Known Security Blocker
- **Next.js Version Line**: Next.js 15.5.28 with React 19 is a supported patched line. `npm audit --omit=dev` reports no advisories for the production dependency tree. See [Dependency Security](../security/dependency-security.md).

---

## 2. Backend API (`apps/api`)

The flowOS backend API is built with **Laravel 12**, PHP 8.2+, and PostgreSQL 16.

### Setup & Local Execution
```bash
cd apps/api

# Install PHP dependencies
composer install

# Ensure local environment configuration exists
cp .env.example .env

# Generate unique application encryption key if not set
php artisan key:generate

# Boot the native development server
php artisan serve --host=127.0.0.1 --port=8000
```
The API is accessible at:
```text
http://localhost:8000
```

### Health Check Endpoint
```bash
curl http://127.0.0.1:8000/api/v1/health
```
Expected response:
```json
{"status":"ok","service":"flowos-api"}
```
> [!IMPORTANT]
> `/api/v1/health` is an **unauthenticated process-liveness check**. It verifies that the PHP runtime and HTTP routing pipeline are operational. It **does not prove** that PostgreSQL or Redis are accessible. Local development health checks must not depend on database or cache state.

### Testing & Code Style
```bash
cd apps/api

# Run test suite (Pest / PHPUnit)
php artisan test

# Check code formatting (Laravel Pint)
./vendor/bin/pint --test

# Automatically fix code formatting
./vendor/bin/pint
```
*Note on Testing Database*: Standard API unit/feature tests run in an in-memory SQLite database (`:memory:`) configured in `phpunit.xml` for maximum speed. Full integration against PostgreSQL is executed during CI smoke checks.

### Known Security Blocker
- **Laravel Framework Line**: Laravel 12.70.0 is a supported patched line, and `composer audit` reports no advisories.

---

## 3. Mobile Application (`apps/mobile`)

The mobile client is built with **Flutter 3.16+** and Dart 3.3+, utilizing Riverpod for state management.

### Setup & Local Execution
```bash
cd apps/mobile

# Fetch Flutter dependencies
flutter pub get

# Launch on connected device or emulator
flutter run
```

### Networking & Target Devices
- **Android Virtual Device (AVD Emulator)**:
  - The Android emulator accesses the host workstation via `10.0.2.2`.
  - Pass the API base URL at launch:
    ```bash
    flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
    ```
- **iOS Simulator (macOS only)**:
  - The iOS simulator shares the host network loopback:
    ```bash
    flutter run --dart-define=API_BASE_URL=http://localhost:8000/api/v1
    ```

### Testing & Static Analysis
```bash
cd apps/mobile

# Run Flutter tests
flutter test

# Run Dart static analyzer
flutter analyze

# Check code formatting
dart format --output=none --set-exit-if-changed .

# Format code
dart format .
```

### Known Technical Debt: 186 Analyzer Findings
> [!WARNING]
> While `flutter test` passes, running `flutter analyze` currently produces **186 accepted analyzer findings** (primarily deprecation warnings and lint style suggestions).
> 
> Because of this pre-existing debt, local `dev verify` intentionally exits with code `1`. In hosted CI, `flutter analyze` is temporarily configured with `continue-on-error: true` while debt remediation is underway. Do not claim the repository is analyzer-clean.

### Token Storage & Offline Sync
- **Secure Token Storage**: Authentication tokens are stored using `flutter_secure_storage` (Android Keystore / iOS Keychain), never insecure shared preferences or local files.
- **Sync Engine Status**: The current `SyncEngine` is a prototype with accepted architectural limitations (last-write-wins assumptions and placeholder cursor tracking). Full revision is planned for the synchronization milestone.

---

## 4. Desktop Application (`apps/desktop`)

The flowOS desktop application is built with **.NET 8 WPF** (`net8.0-windows`) and C#.

### System Requirement: Windows
Developing, building, and running the desktop application requires **Windows**, as WPF relies on the Windows desktop runtime.

### Setup & Build Commands
```bash
cd apps/desktop

# Restore NuGet packages
dotnet restore FlowOS.sln

# Build the solution
dotnet build FlowOS.sln

# Run the WPF application
dotnet run --project src/FlowOS.Desktop/FlowOS.Desktop.csproj

# Run test projects
dotnet test FlowOS.sln

# Check C# code formatting
dotnet format FlowOS.sln --verify-no-changes

# Auto-format C# code
dotnet format FlowOS.sln
```

### Tooling Recommendations
- **CLI Support**: The standard `dotnet` CLI (v8.0+) is fully capable of building, testing, and formatting the solution.
- **Visual Studio 2022**: Visual Studio Community/Professional 2022 (v17.8+) with the ".NET desktop development" workload is optional but recommended for visual XAML editing and interactive debugging.

### Known State: Zero Discovered Tests
> [!NOTE]
> Desktop test projects (`FlowOS.Domain.Tests`, `FlowOS.Application.Tests`, `FlowOS.Infrastructure.Tests`) are wired into the solution and build cleanly, but currently contain zero discovered test cases. `dotnet test` passes with zero tests executed. Full desktop test coverage will be implemented in future desktop domain sprints.

### Local Persistence Status
The desktop architecture specifies local offline caching using EF Core SQLite. In Sprint 0, this infrastructure remains foundational placeholder code. Authoritative persistence resides in PostgreSQL via the API.
