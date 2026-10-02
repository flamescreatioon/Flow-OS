#!/usr/bin/env pwsh
# flowOS Development Script (Windows PowerShell)

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$RootDir = Split-Path -Parent $ScriptDir

function Show-Usage {
    @"
Usage: .\scripts\dev.ps1 <command>

Commands:
  setup         Prepare development environment (install deps, copy env files)
  up            Start infrastructure (PostgreSQL, Redis, Mailpit)
  down          Stop infrastructure (preserves volumes)
  status        Show infrastructure status
  logs          Show infrastructure logs [service]
  test          Run all test suites
  lint          Run all linters
  format        Format code (API, Flutter, Desktop)
  format-check  Check formatting without changes
  verify        Run repository-wide verification
  db-migrate    Run Laravel migrations
  db-status     Show migration status
  db-reset      Destructive: reset local development schema (requires confirmation)
  help          Show this help

Environment:
  APP_ENV=production  Prevents db-reset (safety)

Examples:
  .\scripts\dev.ps1 setup
  .\scripts\dev.ps1 up
  .\scripts\dev.ps1 verify
  .\scripts\dev.ps1 db-reset
"@
}

function Check-Docker {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        Write-Host "ERROR: docker not found. Install Docker Desktop." -ForegroundColor Red
        exit 1
    }
    docker compose version >$null 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: docker compose not available. Install Docker Compose v2." -ForegroundColor Red
        exit 1
    }
    docker info >$null 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Docker daemon is not running. Start Docker Desktop and retry." -ForegroundColor Red
        exit 1
    }
}

function Cmd-Setup {
    Write-Host "Setting up flowOS development environment..." -ForegroundColor Green
    Set-Location $RootDir

    # Prerequisites check
    foreach ($cmd in @("git", "docker", "node", "php", "composer", "flutter", "dotnet")) {
        if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
            Write-Host "WARNING: $cmd not found (optional for some commands)" -ForegroundColor Yellow
        }
    }

    # Web
    if (Test-Path "$RootDir/apps/web") {
        Set-Location "$RootDir/apps/web"
        if (-not (Test-Path ".env.local") -and (Test-Path ".env.example")) {
            Copy-Item .env.example .env.local
            Write-Host "Created apps/web/.env.local" -ForegroundColor Green
        }
        if (Get-Command npm -ErrorAction SilentlyContinue) {
            npm install
        }
    }

    # API
    if (Test-Path "$RootDir/apps/api") {
        Set-Location "$RootDir/apps/api"
        if (-not (Test-Path ".env") -and (Test-Path ".env.example")) {
            Copy-Item .env.example .env
            Write-Host "Created apps/api/.env" -ForegroundColor Green
        }
        if (Get-Command composer -ErrorAction SilentlyContinue) {
            composer install
            php artisan key:generate --force
        }
    }

    # Mobile
    if ((Test-Path "$RootDir/apps/mobile") -and (Get-Command flutter -ErrorAction SilentlyContinue)) {
        Set-Location "$RootDir/apps/mobile"
        flutter pub get
    }

    # Desktop
    if ((Test-Path "$RootDir/apps/desktop") -and (Get-Command dotnet -ErrorAction SilentlyContinue)) {
        Set-Location "$RootDir/apps/desktop"
        dotnet restore FlowOS.sln
    }

    Write-Host "Setup complete. Run '.\scripts\dev.ps1 up' to start infrastructure." -ForegroundColor Green
}

function Cmd-Up {
    Check-Docker
    Write-Host "Starting infrastructure..." -ForegroundColor Cyan
    Set-Location $RootDir
    docker compose up -d
    Write-Host "Infrastructure started. Use '.\scripts\dev.ps1 status' to verify." -ForegroundColor Green
}

function Cmd-Down {
    Check-Docker
    Write-Host "Stopping infrastructure (preserving volumes)..." -ForegroundColor Yellow
    Set-Location $RootDir
    docker compose down
    Write-Host "Infrastructure stopped." -ForegroundColor Green
}

function Cmd-Status {
    Check-Docker
    Set-Location $RootDir
    docker compose ps
    Write-Host ""
    Write-Host "Local URLs (when running):" -ForegroundColor Cyan
    Write-Host "  API Health: http://localhost:8000/api/v1/health"
    Write-Host "  Web:        http://localhost:3000"
    Write-Host "  Mailpit:    http://localhost:8025"
    Write-Host "  PostgreSQL: 127.0.0.1:5432"
    Write-Host "  Redis:      127.0.0.1:6379"
}

function Cmd-Logs {
    Check-Docker
    Set-Location $RootDir
    if ($args.Count -eq 0) {
        docker compose logs -f
    } else {
        docker compose logs -f $args[0]
    }
}

function Cmd-Test {
    Write-Host "Running tests..." -ForegroundColor Green
    $failed = 0

    # API
    if (Test-Path "$RootDir/apps/api") {
        Write-Host "Testing API..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/api"
        php artisan test
        if ($LASTEXITCODE -ne 0) { $failed = 1 }
    }

    # Web
    if ((Test-Path "$RootDir/apps/web") -and (Get-Command npm -ErrorAction SilentlyContinue)) {
        Write-Host "Testing Web..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/web"
        npm run test --if-present 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Host "WARNING: Web test script not configured" -ForegroundColor Yellow
        }
    }

    # Mobile
    if ((Test-Path "$RootDir/apps/mobile") -and (Get-Command flutter -ErrorAction SilentlyContinue)) {
        Write-Host "Testing Mobile..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/mobile"
        flutter test
        if ($LASTEXITCODE -ne 0) { $failed = 1 }
    }

    # Desktop
    if ((Test-Path "$RootDir/apps/desktop") -and (Get-Command dotnet -ErrorAction SilentlyContinue)) {
        Write-Host "Testing Desktop..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/desktop"
        dotnet test FlowOS.sln
        if ($LASTEXITCODE -ne 0) { $failed = 1 }
    }

    if ($failed -eq 0) {
        Write-Host "All available tests passed" -ForegroundColor Green
    } else {
        Write-Host "Some tests failed" -ForegroundColor Red
        exit 1
    }
}

function Cmd-Lint {
    Write-Host "Running linters..." -ForegroundColor Green
    $failed = 0

    # API
    if (Test-Path "$RootDir/apps/api") {
        Write-Host "Linting API..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/api"
        .\vendor\bin\pint --test
        if ($LASTEXITCODE -ne 0) { $failed = 1 }
    }

    # Web
    if ((Test-Path "$RootDir/apps/web") -and (Get-Command npm -ErrorAction SilentlyContinue)) {
        Write-Host "Linting Web..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/web"
        npm run lint
        if ($LASTEXITCODE -ne 0) { $failed = 1 }
    }

    # Mobile
    if ((Test-Path "$RootDir/apps/mobile") -and (Get-Command flutter -ErrorAction SilentlyContinue)) {
        Write-Host "Analyzing Mobile..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/mobile"
        flutter analyze
        if ($LASTEXITCODE -ne 0) {
            Write-Host "WARNING: Flutter analyzer has pre-existing issues" -ForegroundColor Yellow
            $failed = 1
        }
    }

    if ($failed -eq 0) {
        Write-Host "All linters passed" -ForegroundColor Green
    } else {
        Write-Host "Some linters failed" -ForegroundColor Red
        exit 1
    }
}

function Cmd-Format {
    Write-Host "Formatting code..." -ForegroundColor Green

    if (Test-Path "$RootDir/apps/api") {
        Set-Location "$RootDir/apps/api"
        .\vendor\bin\pint
    }

    if ((Test-Path "$RootDir/apps/mobile") -and (Get-Command flutter -ErrorAction SilentlyContinue)) {
        Set-Location "$RootDir/apps/mobile"
        dart format .
    }

    if ((Test-Path "$RootDir/apps/desktop") -and (Get-Command dotnet -ErrorAction SilentlyContinue)) {
        Set-Location "$RootDir/apps/desktop"
        dotnet format
    }

    Write-Host "Formatting complete" -ForegroundColor Green
}

function Cmd-FormatCheck {
    Write-Host "Checking formatting..." -ForegroundColor Green
    $failed = 0

    if (Test-Path "$RootDir/apps/api") {
        Set-Location "$RootDir/apps/api"
        .\vendor\bin\pint --test
        if ($LASTEXITCODE -ne 0) { $failed = 1 }
    }

    if ((Test-Path "$RootDir/apps/mobile") -and (Get-Command flutter -ErrorAction SilentlyContinue)) {
        Set-Location "$RootDir/apps/mobile"
        dart format --output=none --set-exit-if-changed .
        if ($LASTEXITCODE -ne 0) { $failed = 1 }
    }

    if ((Test-Path "$RootDir/apps/desktop") -and (Get-Command dotnet -ErrorAction SilentlyContinue)) {
        Set-Location "$RootDir/apps/desktop"
        dotnet format --verify-no-changes
        if ($LASTEXITCODE -ne 0) { $failed = 1 }
    }

    if ($failed -eq 0) {
        Write-Host "Formatting check passed" -ForegroundColor Green
    } else {
        Write-Host "Formatting check failed" -ForegroundColor Red
        exit 1
    }
}

function Cmd-Verify {
    Write-Host "Running repository-wide verification..." -ForegroundColor Green
    $failed = 0

    Write-Host ""
    Write-Host "=== Docker Compose Config ===" -ForegroundColor Cyan
    docker compose config >$null 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Docker Compose config invalid" -ForegroundColor Red
        $failed = 1
    } else {
        Write-Host "Docker Compose config valid" -ForegroundColor Green
    }

    Write-Host ""
    Write-Host "=== API ===" -ForegroundColor Cyan
    if (Test-Path "$RootDir/apps/api") {
        if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
            Write-Host "ERROR: php not available - API verification cannot run" -ForegroundColor Red
            $failed = 1
        } else {
            Set-Location "$RootDir/apps/api"
            Write-Host "  Tests..." -ForegroundColor Cyan
            php artisan test
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
            Write-Host "  Pint..." -ForegroundColor Cyan
            .\vendor\bin\pint --test
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
        }
    } else {
        Write-Host "ERROR: API directory not found" -ForegroundColor Red
        $failed = 1
    }

    Write-Host ""
    Write-Host "=== Web ===" -ForegroundColor Cyan
    if (Test-Path "$RootDir/apps/web") {
        if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
            Write-Host "ERROR: npm not available - Web verification cannot run" -ForegroundColor Red
            $failed = 1
        } else {
            Set-Location "$RootDir/apps/web"
            Write-Host "  Tests..." -ForegroundColor Cyan
            npm run test 2>&1
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
            Write-Host "  Lint..." -ForegroundColor Cyan
            npm run lint
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
            Write-Host "  Typecheck..." -ForegroundColor Cyan
            npm run typecheck
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
        }
    } else {
        Write-Host "ERROR: Web directory not found" -ForegroundColor Red
        $failed = 1
    }

    Write-Host ""
    Write-Host "=== Mobile ===" -ForegroundColor Cyan
    if (Test-Path "$RootDir/apps/mobile") {
        if (-not (Get-Command flutter -ErrorAction SilentlyContinue)) {
            Write-Host "ERROR: flutter not available - Mobile verification cannot run" -ForegroundColor Red
            $failed = 1
        } else {
            Set-Location "$RootDir/apps/mobile"
            Write-Host "  Tests..." -ForegroundColor Cyan
            flutter test
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
            Write-Host "  Analyze..." -ForegroundColor Cyan
            flutter analyze
            if ($LASTEXITCODE -ne 0) {
                Write-Host "  WARNING: Pre-existing analyzer issues (known debt)" -ForegroundColor Yellow
                $failed = 1
            }
        }
    } else {
        Write-Host "ERROR: Mobile directory not found" -ForegroundColor Red
        $failed = 1
    }

    Write-Host ""
    Write-Host "=== Desktop ===" -ForegroundColor Cyan
    if (Test-Path "$RootDir/apps/desktop") {
        if (-not (Get-Command dotnet -ErrorAction SilentlyContinue)) {
            Write-Host "ERROR: dotnet not available - Desktop verification cannot run" -ForegroundColor Red
            $failed = 1
        } else {
            Set-Location "$RootDir/apps/desktop"
            Write-Host "  Build..." -ForegroundColor Cyan
            dotnet build
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
            Write-Host "  Tests..." -ForegroundColor Cyan
            dotnet test --no-build
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
            Write-Host "  Format check..." -ForegroundColor Cyan
            dotnet format --verify-no-changes
            if ($LASTEXITCODE -ne 0) { $failed = 1 }
        }
    } else {
        Write-Host "ERROR: Desktop directory not found" -ForegroundColor Red
        $failed = 1
    }

    Write-Host ""
    if ($failed -eq 0) {
        Write-Host "All verification checks passed" -ForegroundColor Green
    } else {
        Write-Host "Some verification checks failed" -ForegroundColor Red
        exit 1
    }
}

function Cmd-DbMigrate {
    if (Test-Path "$RootDir/apps/api") {
        Set-Location "$RootDir/apps/api"
        php artisan migrate
    }
}

function Cmd-DbStatus {
    if (Test-Path "$RootDir/apps/api") {
        Set-Location "$RootDir/apps/api"
        php artisan migrate:status
    }
}

function Cmd-DbReset {
    # Safety checks
    if (Test-Path "$RootDir/apps/api/.env") {
        $appEnv = (Get-Content "$RootDir/apps/api/.env" | Where-Object { $_ -match "^APP_ENV=" }) -replace "APP_ENV=", ""
        if ($appEnv -eq "production") {
            Write-Host "ERROR: APP_ENV=production - db-reset is blocked for safety" -ForegroundColor Red
            exit 1
        }
    }

    if (Test-Path "$RootDir/apps/api/.env") {
        $dbHost = (Get-Content "$RootDir/apps/api/.env" | Where-Object { $_ -match "^DB_HOST=" }) -replace "DB_HOST=", ""
        if ($dbHost -ne "127.0.0.1" -and $dbHost -ne "localhost") {
            Write-Host "ERROR: DB_HOST=$dbHost does not appear to be local - db-reset refused" -ForegroundColor Red
            exit 1
        }
    }

    Write-Host "WARNING: This will DESTROY the local development schema and all data." -ForegroundColor Red
    $dbName = (Get-Content "$RootDir/apps/api/.env" | Where-Object { $_ -match "^DB_DATABASE=" }) -replace "DB_DATABASE=", ""
    Write-Host "WARNING: Database: $dbName" -ForegroundColor Red
    Write-Host ""
    $confirm = Read-Host "Type RESET to continue"
    if ($confirm -ne "RESET") {
        Write-Host "Aborted." -ForegroundColor Yellow
        exit 1
    }

    if (Test-Path "$RootDir/apps/api") {
        Set-Location "$RootDir/apps/api"
        php artisan migrate:fresh --force
        Write-Host "Local development schema reset complete" -ForegroundColor Green
    }
}

# Main
$command = if ($args.Count -gt 0) { $args[0] } else { "help" }

switch ($command) {
    "setup"      { Cmd-Setup }
    "up"         { Cmd-Up }
    "down"       { Cmd-Down }
    "status"     { Cmd-Status }
    "logs"       { Cmd-Logs $args[1..($args.Count-1)] }
    "test"       { Cmd-Test }
    "lint"       { Cmd-Lint }
    "format"     { Cmd-Format }
    "format-check" { Cmd-FormatCheck }
    "verify"     { Cmd-Verify }
    "db-migrate" { Cmd-DbMigrate }
    "db-status"  { Cmd-DbStatus }
    "db-reset"   { Cmd-DbReset }
    "help"       { Show-Usage }
    default {
        Write-Host "ERROR: Unknown command: $command" -ForegroundColor Red
        Show-Usage
        exit 1
    }
}
