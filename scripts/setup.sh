#!/bin/bash
# flowOS Setup Script (Linux/macOS/WSL)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

usage() {
    cat <<EOF
Usage: ./scripts/setup.sh

This script prepares a new developer environment:
  - Checks prerequisites
  - Copies missing .env files from examples
  - Installs dependencies
  - Optionally starts infrastructure
  - Prepares Laravel app key
  - Runs migrations when infrastructure available

Does NOT:
  - Overwrite existing .env files
  - Run migrations without confirmed PostgreSQL availability
  - Start application servers
EOF
}

check_command() {
    if ! command -v "$1" &> /dev/null; then
        echo "⚠️  $1 not found (optional for some commands)"
        return 1
    fi
    echo "✅ $1 found"
    return 0
}

echo "🚀 Setting up flowOS development environment..."
cd "${ROOT_DIR}"

# Prerequisites check
echo "🔍 Checking prerequisites..."
check_command git
check_command docker
check_command node
check_command php
check_command composer
check_command flutter
check_command dotnet

# Web
if [ -d "${ROOT_DIR}/apps/web" ]; then
    echo "🌐 Setting up Web application..."
    cd "${ROOT_DIR}/apps/web"
    if [ ! -f .env.local ] && [ -f .env.example ]; then
        cp .env.example .env.local
        echo "✅ Created apps/web/.env.local"
    fi
    if command -v npm &> /dev/null; then
        npm install
    fi
fi

# API
    if [ -d "${ROOT_DIR}/apps/api" ]; then
        echo "🔧 Setting up API (Laravel)..."
        cd "${ROOT_DIR}/apps/api"
        if [ ! -f .env ] && [ -f .env.example ]; then
            cp .env.example .env
            echo "✅ Created apps/api/.env"
        fi
        if command -v composer &> /dev/null; then
            composer install
            # Only generate APP_KEY if missing or empty
            if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
                php artisan key:generate --force
                echo "✅ Generated APP_KEY"
            else
                echo "✅ APP_KEY already present"
            fi
        fi
    fi

# Mobile
if [ -d "${ROOT_DIR}/apps/mobile" ] && command -v flutter &> /dev/null; then
    echo "📱 Setting up Mobile (Flutter)..."
    cd "${ROOT_DIR}/apps/mobile"
    flutter pub get
fi

# Desktop
if [ -d "${ROOT_DIR}/apps/desktop" ] && command -v dotnet &> /dev/null; then
    echo "💻 Setting up Desktop (.NET)..."
    cd "${ROOT_DIR}/apps/desktop"
    dotnet restore FlowOS.sln
fi

echo ""
echo "✅ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "  1. Start infrastructure: ./scripts/dev.sh up"
echo "  2. Run migrations:       ./scripts/dev.sh db-migrate"
echo "  3. Run web:              cd apps/web && npm run dev"
echo "  4. Run API:              cd apps/api && php artisan serve"
echo "  5. Run mobile:           cd apps/mobile && flutter run"
echo "  6. Run desktop:          cd apps/desktop && dotnet run --project src/FlowOS.Desktop"
echo ""
echo "📚 Documentation: docs/"
echo "🐛 Issues: .github/ISSUE_TEMPLATE/"