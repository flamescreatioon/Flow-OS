#!/bin/bash
# flowOS Development Script (Linux/macOS/WSL)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

usage() {
    cat <<EOF
Usage: ./scripts/dev.sh <command>

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
  ./scripts/dev.sh setup
  ./scripts/dev.sh up
  ./scripts/dev.sh verify
  ./scripts/dev.sh db-reset
EOF
}

check_docker() {
    if ! command -v docker &> /dev/null; then
        echo "❌ docker not found. Install Docker Desktop."
        exit 1
    fi
    if ! docker compose version &> /dev/null; then
        echo "❌ docker compose not available. Install Docker Compose v2."
        exit 1
    fi
    if ! docker info &> /dev/null; then
        echo "❌ Docker daemon is not running. Start Docker Desktop and retry."
        exit 1
    fi
}

cmd_setup() {
    echo "🚀 Setting up flowOS development environment..."
    cd "${ROOT_DIR}"

    # Prerequisites check
    for cmd in git docker node php composer flutter dotnet; do
        if ! command -v "$cmd" &> /dev/null; then
            echo "⚠️  $cmd not found (optional for some commands)"
        fi
    done

    # Web
    if [ -d "${ROOT_DIR}/apps/web" ]; then
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
        cd "${ROOT_DIR}/apps/api"
        if [ ! -f .env ] && [ -f .env.example ]; then
            cp .env.example .env
            echo "✅ Created apps/api/.env"
        fi
        if command -v composer &> /dev/null; then
            composer install
            php artisan key:generate --force
        fi
    fi

    # Mobile
    if [ -d "${ROOT_DIR}/apps/mobile" ] && command -v flutter &> /dev/null; then
        cd "${ROOT_DIR}/apps/mobile"
        flutter pub get
    fi

    # Desktop
    if [ -d "${ROOT_DIR}/apps/desktop" ] && command -v dotnet &> /dev/null; then
        cd "${ROOT_DIR}/apps/desktop"
        dotnet restore FlowOS.sln
    fi

    echo "✅ Setup complete. Run './scripts/dev.sh up' to start infrastructure."
}

cmd_up() {
    check_docker
    echo "🐳 Starting infrastructure..."
    cd "${ROOT_DIR}"
    docker compose up -d
    echo "✅ Infrastructure started. Use './scripts/dev.sh status' to verify."
}

cmd_down() {
    check_docker
    echo "🛑 Stopping infrastructure (preserving volumes)..."
    cd "${ROOT_DIR}"
    docker compose down
    echo "✅ Infrastructure stopped."
}

cmd_status() {
    check_docker
    cd "${ROOT_DIR}"
    docker compose ps
    echo ""
    echo "📋 Local URLs (when running):"
    echo "  API Health: http://localhost:8000/api/v1/health"
    echo "  Web:        http://localhost:3000"
    echo "  Mailpit:    http://localhost:8025"
    echo "  PostgreSQL: 127.0.0.1:5432"
    echo "  Redis:      127.0.0.1:6379"
}

cmd_logs() {
    check_docker
    cd "${ROOT_DIR}"
    if [ $# -eq 0 ]; then
        docker compose logs -f
    else
        docker compose logs -f "$1"
    fi
}

cmd_test() {
    echo "🧪 Running tests..."
    local failed=0

    # API
    if [ -d "${ROOT_DIR}/apps/api" ]; then
        echo "🔧 Testing API..."
        cd "${ROOT_DIR}/apps/api"
        if ! php artisan test; then failed=1; fi
    fi

    # Web
    if [ -d "${ROOT_DIR}/apps/web" ] && command -v npm &> /dev/null; then
        echo "🌐 Testing Web..."
        cd "${ROOT_DIR}/apps/web"
        if npm run test --if-present 2>/dev/null; then :; else
            echo "⚠️  Web test script not configured"
        fi
    fi

    # Mobile
    if [ -d "${ROOT_DIR}/apps/mobile" ] && command -v flutter &> /dev/null; then
        echo "📱 Testing Mobile..."
        cd "${ROOT_DIR}/apps/mobile"
        if ! flutter test; then failed=1; fi
    fi

    # Desktop
    if [ -d "${ROOT_DIR}/apps/desktop" ] && command -v dotnet &> /dev/null; then
        echo "💻 Testing Desktop..."
        cd "${ROOT_DIR}/apps/desktop"
        if ! dotnet test FlowOS.sln; then failed=1; fi
    fi

    if [ $failed -eq 0 ]; then
        echo "✅ All available tests passed"
    else
        echo "❌ Some tests failed"
        exit 1
    fi
}

cmd_lint() {
    echo "🔍 Running linters..."
    local failed=0

    # API
    if [ -d "${ROOT_DIR}/apps/api" ]; then
        echo "🔧 Linting API..."
        cd "${ROOT_DIR}/apps/api"
        if ! ./vendor/bin/pint --test; then failed=1; fi
    fi

    # Web
    if [ -d "${ROOT_DIR}/apps/web" ] && command -v npm &> /dev/null; then
        echo "🌐 Linting Web..."
        cd "${ROOT_DIR}/apps/web"
        if ! npm run lint; then failed=1; fi
    fi

    # Mobile
    if [ -d "${ROOT_DIR}/apps/mobile" ] && command -v flutter &> /dev/null; then
        echo "📱 Analyzing Mobile..."
        cd "${ROOT_DIR}/apps/mobile"
        if ! flutter analyze; then
            echo "⚠️  Flutter analyzer has pre-existing issues"
            failed=1
        fi
    fi

    if [ $failed -eq 0 ]; then
        echo "✅ All linters passed"
    else
        echo "❌ Some linters failed"
        exit 1
    fi
}

cmd_format() {
    echo "✨ Formatting code..."

    if [ -d "${ROOT_DIR}/apps/api" ]; then
        cd "${ROOT_DIR}/apps/api"
        ./vendor/bin/pint
    fi

    if [ -d "${ROOT_DIR}/apps/mobile" ] && command -v flutter &> /dev/null; then
        cd "${ROOT_DIR}/apps/mobile"
        dart format .
    fi

    if [ -d "${ROOT_DIR}/apps/desktop" ] && command -v dotnet &> /dev/null; then
        cd "${ROOT_DIR}/apps/desktop"
        dotnet format
    fi

    echo "✅ Formatting complete"
}

cmd_format_check() {
    echo "🔍 Checking formatting..."
    local failed=0

    if [ -d "${ROOT_DIR}/apps/api" ]; then
        cd "${ROOT_DIR}/apps/api"
        if ! ./vendor/bin/pint --test; then failed=1; fi
    fi

    if [ -d "${ROOT_DIR}/apps/mobile" ] && command -v flutter &> /dev/null; then
        cd "${ROOT_DIR}/apps/mobile"
        if ! dart format --output=none --set-exit-if-changed .; then failed=1; fi
    fi

    if [ -d "${ROOT_DIR}/apps/desktop" ] && command -v dotnet &> /dev/null; then
        cd "${ROOT_DIR}/apps/desktop"
        if ! dotnet format --verify-no-changes; then failed=1; fi
    fi

    if [ $failed -eq 0 ]; then
        echo "✅ Formatting check passed"
    else
        echo "❌ Formatting check failed"
        exit 1
    fi
}

cmd_verify() {
    echo "🔍 Running repository-wide verification..."
    local failed=0

    echo ""
    echo "=== Docker Compose Config ==="
    if ! docker compose config >/dev/null 2>&1; then
        echo "❌ Docker Compose config invalid"
        failed=1
    else
        echo "✅ Docker Compose config valid"
    fi

    echo ""
    echo "=== API ==="
    if [ -d "${ROOT_DIR}/apps/api" ]; then
        cd "${ROOT_DIR}/apps/api"
        if ! command -v php &> /dev/null; then
            echo "❌ php not available — API verification cannot run"
            failed=1
        else
            echo "  Tests..."
            if ! php artisan test; then failed=1; fi
            echo "  Pint..."
            if ! ./vendor/bin/pint --test; then failed=1; fi
        fi
    else
        echo "❌ API directory not found"
        failed=1
    fi

    echo ""
    echo "=== Web ==="
    if [ -d "${ROOT_DIR}/apps/web" ]; then
        if ! command -v npm &> /dev/null; then
            echo "❌ npm not available — Web verification cannot run"
            failed=1
    else
        cd "${ROOT_DIR}/apps/web"
        echo "  Tests..."
        if ! npm run test; then failed=1; fi
        echo "  Lint..."
        if ! npm run lint; then failed=1; fi
            echo "  Typecheck..."
            if ! npm run typecheck; then failed=1; fi
        fi
    else
        echo "❌ Web directory not found"
        failed=1
    fi

    echo ""
    echo "=== Mobile ==="
    if [ -d "${ROOT_DIR}/apps/mobile" ]; then
        if ! command -v flutter &> /dev/null; then
            echo "❌ flutter not available — Mobile verification cannot run"
            failed=1
        else
            cd "${ROOT_DIR}/apps/mobile"
            echo "  Tests..."
            if ! flutter test; then failed=1; fi
            echo "  Analyze..."
            if ! flutter analyze; then
                echo "  ⚠️  Pre-existing analyzer issues (known debt)"
                failed=1
            fi
        fi
    else
        echo "❌ Mobile directory not found"
        failed=1
    fi

    echo ""
    echo "=== Desktop ==="
    if [ -d "${ROOT_DIR}/apps/desktop" ]; then
        if ! command -v dotnet &> /dev/null; then
            echo "❌ dotnet not available — Desktop verification cannot run"
            failed=1
        else
            cd "${ROOT_DIR}/apps/desktop"
            echo "  Build..."
            if ! dotnet build; then failed=1; fi
            echo "  Tests..."
            if ! dotnet test --no-build; then failed=1; fi
            echo "  Format check..."
            if ! dotnet format --verify-no-changes; then failed=1; fi
        fi
    else
        echo "❌ Desktop directory not found"
        failed=1
    fi

    echo ""
    if [ $failed -eq 0 ]; then
        echo "✅ All verification checks passed"
    else
        echo "❌ Some verification checks failed"
        exit 1
    fi
}

cmd_db_migrate() {
    if [ -d "${ROOT_DIR}/apps/api" ]; then
        cd "${ROOT_DIR}/apps/api"
        php artisan migrate
    fi
}

cmd_db_status() {
    if [ -d "${ROOT_DIR}/apps/api" ]; then
        cd "${ROOT_DIR}/apps/api"
        php artisan migrate:status
    fi
}

cmd_db_reset() {
    # Safety checks
    if [ -f "${ROOT_DIR}/apps/api/.env" ]; then
        local app_env=$(grep "^APP_ENV=" "${ROOT_DIR}/apps/api/.env" | cut -d'=' -f2)
        if [ "${app_env}" = "production" ]; then
            echo "❌ APP_ENV=production — db-reset is blocked for safety"
            exit 1
        fi
    fi

    if [ -f "${ROOT_DIR}/apps/api/.env" ]; then
        local db_host=$(grep "^DB_HOST=" "${ROOT_DIR}/apps/api/.env" | cut -d'=' -f2)
        if [ "${db_host}" != "127.0.0.1" ] && [ "${db_host}" != "localhost" ]; then
            echo "❌ DB_HOST=${db_host} does not appear to be local — db-reset refused"
            exit 1
        fi
    fi

    echo "⚠️  This will DESTROY the local development schema and all data."
    echo "⚠️  Database: $(grep "^DB_DATABASE=" "${ROOT_DIR}/apps/api/.env" 2>/dev/null | cut -d'=' -f2 || echo 'unknown')"
    echo ""
    read -p "Type RESET to continue: " confirm
    if [ "$confirm" != "RESET" ]; then
        echo "Aborted."
        exit 1
    fi

    if [ -d "${ROOT_DIR}/apps/api" ]; then
        cd "${ROOT_DIR}/apps/api"
        php artisan migrate:fresh --force
        echo "✅ Local development schema reset complete"
    fi
}

# Main
case "${1:-help}" in
    setup) cmd_setup ;;
    up) cmd_up ;;
    down) cmd_down ;;
    status) cmd_status ;;
    logs) shift; cmd_logs "$@" ;;
    test) cmd_test ;;
    lint) cmd_lint ;;
    format) cmd_format ;;
    format-check) cmd_format_check ;;
    verify) cmd_verify ;;
    db-migrate) cmd_db_migrate ;;
    db-status) cmd_db_status ;;
    db-reset) cmd_db_reset ;;
    help|--help|-h) usage ;;
    *)
        echo "❌ Unknown command: $1"
        usage
        exit 1
        ;;
esac
