#!/bin/bash
# flowOS Test Script (Linux/macOS)

set -e

echo "🧪 Running flowOS tests..."

# Test web
echo "🌐 Testing Web..."
cd apps/web
npm run test -- --ci --coverage
cd ../..

# Test API
echo "🔧 Testing API..."
cd apps/api
php artisan test --coverage
cd ../..

# Test mobile
echo "📱 Testing Mobile..."
cd apps/mobile
flutter test --coverage
cd ../..

# Test desktop
echo "💻 Testing Desktop..."
cd apps/desktop
dotnet test FlowOS.sln --collect:"XPlat Code Coverage"
cd ../..

echo ""
echo "✅ All tests passed!"