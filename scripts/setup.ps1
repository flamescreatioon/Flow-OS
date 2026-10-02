#!/usr/bin/env pwsh
# flowOS Setup Script (Windows PowerShell)

param()

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$RootDir = Split-Path -Parent $ScriptDir

Write-Host "Setting up flowOS development environment..." -ForegroundColor Green

function Check-Command {
    param($Command, $Name)
    if (-not (Get-Command $Command -ErrorAction SilentlyContinue)) {
        Write-Host "WARNING: $Name not found (optional for some commands)" -ForegroundColor Yellow
        return $false
    }
    Write-Host "OK: $Name found" -ForegroundColor Green
    return $true
}

function Check-Optional {
    param($Command, $Name)
    if (-not (Get-Command $Command -ErrorAction SilentlyContinue)) {
        Write-Host "WARNING: $Name not found (optional)" -ForegroundColor Yellow
        return $false
    }
    return $true
}

Set-Location $RootDir

Write-Host "Checking prerequisites..." -ForegroundColor Cyan
Check-Command git "Git"
Check-Command docker "Docker"
Check-Command node "Node.js"
Check-Command php "PHP"
Check-Command composer "Composer"
Check-Optional flutter "Flutter"
Check-Optional dotnet ".NET SDK"

# Web
if (Test-Path "$RootDir/apps/web") {
    Write-Host "Setting up Web application..." -ForegroundColor Cyan
    Set-Location "$RootDir/apps/web"
    if (-not (Test-Path ".env.local") -and (Test-Path ".env.example")) {
        Copy-Item .env.example .env.local
        Write-Host "OK: Created apps/web/.env.local" -ForegroundColor Green
    }
    if (Get-Command npm -ErrorAction SilentlyContinue) {
        npm install
    }
}

# API
if (Test-Path "$RootDir/apps/api") {
    Write-Host "Setting up API (Laravel)..." -ForegroundColor Cyan
    Set-Location "$RootDir/apps/api"
    if (-not (Test-Path ".env") -and (Test-Path ".env.example")) {
        Copy-Item .env.example .env
        Write-Host "OK: Created apps/api/.env" -ForegroundColor Green
    }
    if (Get-Command composer -ErrorAction SilentlyContinue) {
        composer install
        # Only generate APP_KEY if missing or empty
        $envContent = Get-Content .env -Raw
        if ($envContent -notmatch "APP_KEY=base64:") {
            php artisan key:generate --force
            Write-Host "OK: Generated APP_KEY" -ForegroundColor Green
        } else {
            Write-Host "OK: APP_KEY already present" -ForegroundColor Green
        }
    }
}

# Mobile
if (Test-Path "$RootDir/apps/mobile") {
    if (Get-Command flutter -ErrorAction SilentlyContinue) {
        Write-Host "Setting up Mobile (Flutter)..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/mobile"
        flutter pub get
    }
}

# Desktop
if (Test-Path "$RootDir/apps/desktop") {
    if (Get-Command dotnet -ErrorAction SilentlyContinue) {
        Write-Host "Setting up Desktop (.NET)..." -ForegroundColor Cyan
        Set-Location "$RootDir/apps/desktop"
        dotnet restore FlowOS.sln
    }
}

Write-Host ""
Write-Host "Setup complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "  1. Start infrastructure: .\scripts\dev.ps1 up"
Write-Host "  2. Run migrations:       .\scripts\dev.ps1 db-migrate"
Write-Host "  3. Run web:              cd apps/web && npm run dev"
Write-Host "  4. Run API:              cd apps/api && php artisan serve"
Write-Host "  5. Run mobile:           cd apps/mobile && flutter run"
Write-Host "  6. Run desktop:          cd apps/desktop && dotnet run --project src/FlowOS.Desktop"
Write-Host ""
Write-Host "Documentation: docs/" -ForegroundColor Cyan
Write-Host "Issues: .github/ISSUE_TEMPLATE/" -ForegroundColor Cyan