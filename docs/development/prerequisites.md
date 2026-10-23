# flowOS Developer Prerequisites

This document outlines the required and optional tools, runtimes, and operating system requirements for developing flowOS.

---

## 1. Operating System Compatibility Matrix

flowOS consists of multiple platform applications. Operating system capabilities differ across the stack:

| Component | Technology | Windows | Linux / WSL2 | macOS | Notes |
|-----------|------------|---------|--------------|-------|-------|
| **Local Infrastructure** | Docker Compose | Supported | Supported | Supported | Docker Desktop or Docker Engine v24+ |
| **API** | Laravel 12 (PHP 8.2+) | Supported | Supported | Supported | Runs natively on host |
| **Web** | Next.js 15 (Node.js 20) | Supported | Supported | Supported | Runs natively on host |
| **Mobile** | Flutter 3.16+ (Dart 3.3+) | Supported | Supported | Supported | Android emulator on all; iOS simulator requires macOS |
| **Desktop** | .NET 8 WPF | **Required** | Unsupported* | Unsupported* | WPF requires Windows runtime (`net8.0-windows`) |

> [!NOTE]
> Developers working exclusively on the Web, API, or Mobile applications can develop on Linux or macOS. Working on or building the WPF Desktop application (`apps/desktop`) requires **Windows**.
> 
> *Cross-platform compilation of WPF without Windows desktop targeting is unsupported by the .NET runtime.

---

## 2. Required Runtimes & Core Tooling

To run, test, and contribute to flowOS, install the following required runtimes:

| Tool | Version Requirement | Purpose | Verification Command |
|------|---------------------|---------|----------------------|
| **Git** | `2.40+` | Source control | `git --version` |
| **Docker Engine / Compose** | Compose `v2.20+` (Docker Desktop 4.25+) | Backing services (PostgreSQL, Redis, Mailpit) | `docker compose version` |
| **PHP** | `^8.2` (PHP 8.2 or 8.3) | Laravel backend API | `php -v` |
| **Composer** | `2.6+` | PHP dependency manager | `composer --version` |
| **Node.js** | `20 LTS` (20.12+) | Next.js web application | `node -v` |
| **npm** | `10+` | Node package manager | `npm -v` |
| **Flutter SDK** | `3.16+` (Dart `>=3.3.0 <4.0.0`) | Mobile client application | `flutter --version` |
| **.NET SDK** | `8.0+` (8.0.100+) | Desktop WPF client application | `dotnet --version` |

### Required PHP Extensions
The Laravel API requires the following standard PHP extensions enabled in `php.ini`:
- `pdo_pgsql` / `pgsql` (PostgreSQL driver)
- `pdo_sqlite` / `sqlite3` (for local unit tests in SQLite `:memory:`)
- `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `curl`, `fileinfo`, `bcmath`, `intl`

---

## 3. Optional & Recommended Tooling

The following tools enhance the development workflow but are not strictly required for running the application stack or executing repository scripts:

| Tool | Recommended For | Description |
|------|-----------------|-------------|
| **PostgreSQL Client (`psql`)** | API & Database | Native CLI access to PostgreSQL without container exec (`psql -h 127.0.0.1 -U flowos -d flowos`) |
| **Redis CLI (`redis-cli`)** | Cache & Queue inspection | Native CLI inspection of Redis (`redis-cli -h 127.0.0.1 -p 6379`) |
| **Visual Studio 2022** (v17.8+) | Desktop development | Rich XAML designer, WPF debugging, and profiling tools on Windows |
| **VS Code / Cursor / PhpStorm** | IDE | Multi-language IDE support with plugins for PHP, Flutter, TypeScript, C# |
| **Android Studio** | Mobile development | Android SDK management, virtual device manager (AVD emulator) |
| **Xcode** (macOS only) | Mobile development | iOS Simulator and toolchain for Flutter iOS development |
| **GitHub CLI (`gh`)** | PR management | Command-line pull request and issue interaction (once remote repository is attached) |

---

## 4. Workstation Installation Examples

### Windows (via winget)
```powershell
winget install --id Git.Git -e
winget install --id Docker.DockerDesktop -e
winget install --id OpenJS.NodeJS.LTS -e
winget install --id PHP.PHP.8.2 -e
winget install --id Composer.Composer -e
winget install --id Flutter.Flutter -e
winget install --id Microsoft.DotNet.SDK.8 -e
```

### Ubuntu / Debian (WSL2 / Native Linux)
```bash
# Git, Docker, Node.js, PHP, Composer
sudo apt update
sudo apt install -y git curl unzip libpq-dev
curl -fsSL https://get.docker.com | sh
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs php8.2 php8.2-cli php8.2-pgsql php8.2-sqlite3 php8.2-mbstring php8.2-xml php8.2-curl composer

# Flutter SDK (via snap or manual download)
sudo snap install flutter --classic
```

---

## 5. Verification of Prerequisites

After installing your tools, verify all commands are recognized on your path:

```bash
# Common verification
git --version
docker --version && docker compose version
php -v
composer --version
node -v
npm -v

# Mobile verification (if developing for mobile)
flutter doctor

# Desktop verification (Windows only)
dotnet --version
```

Next step: Follow the [Local Setup Guide](local-setup.md) to initialize the repository.
