#!/usr/bin/env pwsh
# flowOS Test Script (Windows PowerShell)

Write-Host "🧪 Running flowOS tests..." -ForegroundColor Green

# Test web
Write-Host "🌐 Testing Web..." -ForegroundColor Cyan
Set-Location apps/web
npm run test -- --ci --coverage
Set-Location ../..

# Test API
Write-Host "🔧 Testing API..." -ForegroundColor Cyan
Set-Location apps/api
php artisan test --coverage
Set-Location ../..

# Test mobile
Write-Host "📱 Testing Mobile..." -ForegroundColor Cyan
Set-Location apps/mobile
flutter test --coverage
Set-Location ../..

# Test desktop
Write-Host "💻 Testing Desktop..." -ForegroundColor Cyan
Set-Location apps/desktop
dotnet test FlowOS.sln --collect:"XPlat Code Coverage"
Set-Location ../..

Write-Host ""
Write-Host "✅ All tests passed!" -ForegroundColor Green