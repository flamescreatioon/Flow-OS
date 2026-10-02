# Local Development Setup Guide

## Quick Start (Zero to Running)

Follow these steps to set up the flowOS development environment on your local machine.

### Prerequisites

| Tool | Minimum Version | Installation Link |
|------|-----------------|-------------------|
| **Git** | 2.40+ | [git-scm.com](https://git-scm.com/) |
| **Docker Desktop** | 4.25+ (with Compose v2) | [docker.com](https://www.docker.com/products/docker-desktop/) |
| **PHP** | 8.2+ | [php.net](https://www.php.net/) |
| **Composer** | 2.6+ | [getcomposer.org](https://getcomposer.org/) |
| **Node.js** | 20 LTS | [nodejs.org](https://nodejs.org/) |
| **Flutter** (optional for mobile) | 3.16+ | [flutter.dev](https://flutter.dev/) |
| **.NET SDK** (optional for desktop) | 8.0+ | [dotnet.microsoft.com](https://dotnet.microsoft.com/) |

---

## Step-by-Step Setup

### Step 1: Clone the Repository
```bash
git clone https://github.com/your-org/flowos.git
cd flowos
```

### Step 2: Configure Root Infrastructure Environment
Copy the example environment file for Docker Compose:
```bash
cp .env.example .env
```
The default values in `.env` configure:
- PostgreSQL database: `flowos`, user: `flowos`, password: `flowos_local`, port: `5432`
- Redis port: `6379`
- Mailpit ports: `1025` (SMTP), `8025` (Web UI)

### Step 3: Start Docker Infrastructure
Launch the database and cache services:
```bash
docker compose up -d postgres redis
```

Verify that services are running and healthy:
```bash
docker compose ps
```
Both `postgres` and `redis` should report `Up (healthy)`.

### Step 4: Configure the Laravel API
Navigate to the API application and initialize its configuration:
```bash
cd apps/api
cp .env.example .env
```

Ensure `apps/api/.env` database credentials match your root `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flowos
DB_USERNAME=flowos
DB_PASSWORD=flowos_local

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Step 5: Install API Dependencies and Generate Key
```bash
composer install
php artisan key:generate
```

### Step 6: Run Database Migrations
Run the Laravel schema migrations:
```bash
php artisan migrate
```
Check migration status:
```bash
php artisan migrate:status
```
All migrations should display `Ran: Yes`.

### Step 7: Boot the API Server
Start the local development API server:
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Verify the API health endpoint:
```bash
curl http://127.0.0.1:8000/api/v1/health
```
Expected response:
```json
{"status":"ok","service":"flowos-api"}
```

### Step 8: Start the Web Frontend (Optional)
In a separate terminal:
```bash
cd apps/web
npm install
npm run dev
```
Open `http://localhost:3000` in your browser.

---

## Summary of Default URLs

| Application / Service | URL / Host | Description |
|-----------------------|------------|-------------|
| **Next.js Web** | `http://localhost:3000` | Browser frontend |
| **Laravel API** | `http://127.0.0.1:8000` | REST API |
| **API Healthcheck** | `http://127.0.0.1:8000/api/v1/health` | Process health |
| **PostgreSQL** | `127.0.0.1:5432` | Relational database |
| **Redis** | `127.0.0.1:6379` | Cache and queues |
| **Mailpit Dashboard** | `http://127.0.0.1:8025` | Local email preview |
| **Mailpit SMTP** | `127.0.0.1:1025` | Local SMTP catcher |
