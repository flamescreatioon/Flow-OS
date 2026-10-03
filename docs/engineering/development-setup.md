# Development Setup

> [!IMPORTANT]
> **Canonical Documentation Notice**: The canonical developer setup and workstation workflow documentation has moved to [docs/development/README.md](../development/README.md) and [docs/development/local-setup.md](../development/local-setup.md). Please refer to those guides for the latest authoritative instructions.

## Prerequisites

| Tool | Version | Install |
|------|---------|---------|
| Git | 2.40+ | `winget install Git.Git` |
| Node.js | 20 LTS | `winget install OpenJS.NodeJS.LTS` |
| PHP | 8.2+ | `winget install PHP.PHP.8.2` |
| Composer | 2.6+ | `winget install Composer.Composer` |
| Flutter | 3.16+ | `winget install Flutter.Flutter` |
| .NET SDK | 8.0+ | `winget install Microsoft.DotNet.SDK.8` |
| Docker Desktop | 4.25+ | `winget install Docker.DockerDesktop` |
| PostgreSQL | 16+ | Via Docker |
| Redis | 7+ | Via Docker |

## Initial Setup

```bash
# Clone repository
git clone https://github.com/your-org/flowos.git
cd flowos

# Start infrastructure
docker compose up -d

# Verify services
docker compose ps
```

## Web Application (Next.js)

```bash
cd apps/web

# Install dependencies
npm install

# Copy environment
cp .env.example .env.local

# Run development server
npm run dev

# Run type checking
npm run typecheck

# Run linting
npm run lint

# Run tests
npm run test
```

## API (Laravel)

```bash
cd apps/api

# Install dependencies
composer install

# Copy environment
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Run seeders (optional)
php artisan db:seed

# Start development server
php artisan serve

# Run tests
php artisan test

# Run linting
composer lint
```

## Mobile (Flutter)

```bash
cd apps/mobile

# Get dependencies
flutter pub get

# Generate code
flutter pub run build_runner build --delete-conflicting-outputs

# Run on device/emulator
flutter run

# Run tests
flutter test

# Analyze code
flutter analyze
```

## Desktop (.NET)

```bash
cd apps/desktop

# Restore packages
dotnet restore FlowOS.sln

# Build solution
dotnet build FlowOS.sln

# Run desktop app
dotnet run --project src/FlowOS.Desktop

# Run tests
dotnet test FlowOS.sln
```

## Useful Scripts

```bash
# Root scripts (run from repository root)
./scripts/setup.sh      # Full setup
./scripts/dev.sh        # Start all dev servers
./scripts/test.sh       # Run all tests
```

### Windows (PowerShell)

```powershell
# Root scripts
.\scripts\setup.ps1
.\scripts\dev.ps1
.\scripts\test.ps1
```

## Environment Variables

### Web (.env.local)
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

### API (.env)
```env
APP_NAME=flowOS
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flowos
DB_USERNAME=flowos
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## IDE Configuration

### VS Code Extensions

```json
{
  "recommendations": [
    "bradlc.vscode-tailwindcss",
    "esbenp.prettier-vscode",
    "dbaeumer.vscode-eslint",
    "ms-vscode.vscode-typescript-next",
    "bmewburn.vscode-intelephense-client",
    "dart-code.flutter",
    "dart-code.dart-code",
    "ms-dotnettools.csharp",
    "ms-dotnettools.vscode-dotnet-runtime"
  ]
}
```

### Settings

```json
{
  "editor.formatOnSave": true,
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": "explicit",
    "source.organizeImports": "explicit"
  },
  "typescript.tsdk": "apps/web/node_modules/typescript/lib",
  "php.validate.executablePath": "C:/php/php.exe"
}
```

## Common Issues

### Docker: Port Already in Use
```bash
# Check what's using the port
netstat -ano | findstr :5432
# Kill process
taskkill /PID <PID> /F
```

### Laravel: Permission Denied
```bash
# Windows - run as administrator or
icacls storage /grant "Everyone:(OI)(CI)F"
icacls bootstrap/cache /grant "Everyone:(OI)(CI)F"
```

### Flutter: Android License Issues
```bash
flutter doctor --android-licenses
```

### .NET: SDK Not Found
```bash
# Verify installation
dotnet --list-sdks
# Add to PATH if missing
```

## Git Workflow

```bash
# Feature branch
git checkout -b feature/your-feature

# Commit with conventional messages
git commit -m "feat: add user authentication"

# Push and create PR
git push origin feature/your-feature
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Docker containers won't start | `docker compose down -v && docker compose up -d` |
| Laravel migration fails | Check DB connection, run `php artisan migrate:fresh` |
| Flutter build fails | `flutter clean && flutter pub get` |
| .NET build fails | `dotnet clean && dotnet restore` |
| Node modules issues | `rm -rf node_modules package-lock.json && npm install` |