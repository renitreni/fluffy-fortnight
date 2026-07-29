# URL Shortener

An enterprise-grade URL shortening platform built with **Laravel 11**, **Inertia.js**, **Vue 3**, **MySQL**, and **Redis** — containerized with Docker for consistent local development and production deployment.

> Part of the [30-Day Development Plan](./url_shortener_30_day_plan.md).

---

## ✨ Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend SPA | Vue 3 + Inertia.js |
| Build Tool | Vite 6 |
| Styling | Tailwind CSS |
| Database | MySQL 8.0 |
| Cache / Queue / Sessions | Redis 7 |
| Web Server | Nginx 1.25 |
| Containerization | Docker + Docker Compose |

---

## 🚀 Quick Start

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (v4.x+)
- No local PHP, Node, or Composer installation required — everything runs in containers.

### 1. Clone the repository

```bash
git clone <repo-url> url-shortener
cd url-shortener
```

### 2. Set up the environment

```bash
cp .env.example .env
```

Edit `.env` and fill in any secrets (passwords, `APP_KEY` if not already set, etc.).

> **Note:** The default `.env` uses Docker service names (`db`, `redis`) as hostnames. These are correct for container-to-container communication. Do **not** change them to `127.0.0.1` unless running outside Docker.

### 3. Start the stack

```bash
docker compose up -d --build
```

This will:
- Build the PHP 8.3-FPM application image
- Start MySQL 8 and Redis 7
- Run database migrations automatically (via the entrypoint script)
- Start the Laravel queue worker
- Start the Vite HMR dev server (Node 20)
- Start Nginx on port 80

### 4. Open the app

```
http://localhost
```

You should see the Laravel + Inertia welcome page.

---

## 🔥 Vite Hot Module Replacement (HMR)

Vite runs in a dedicated `node` container and exposes port **5173** to the host.

To enable HMR during development, make sure Vite is running:

```bash
# HMR starts automatically with docker compose up
# To check:
docker compose logs -f node
```

Edit any file under `resources/js/` or `resources/css/` and the browser will hot-reload automatically.

---

## 🐳 Docker Services

| Service | Container | Exposed Port | Purpose |
|---|---|---|---|
| `app` | `urlshortener_app` | — | PHP 8.3-FPM (Laravel) |
| `nginx` | `urlshortener_nginx` | **80** | Reverse proxy |
| `db` | `urlshortener_db` | **3306** | MySQL 8.0 |
| `redis` | `urlshortener_redis` | **6379** | Cache / Sessions / Queues |
| `queue` | `urlshortener_queue` | — | Laravel queue worker |
| `node` | `urlshortener_node` | **5173** | Vite HMR dev server |

---

## 🛠️ Common Commands

```bash
# Start all services (detached)
docker compose up -d

# Build and restart (after Dockerfile changes)
docker compose up -d --build

# Stop all services
docker compose down

# Run an Artisan command
docker compose exec app php artisan <command>

# Run a Composer command
docker compose exec app composer <command>

# Run npm commands (if needed outside HMR)
docker compose exec node npm run build

# View logs
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f queue

# Access MySQL shell
docker compose exec db mysql -u urlshortener -p url_shortener

# Access Redis CLI
docker compose exec redis redis-cli

# Run PHPUnit tests
docker compose exec app php artisan test
```

---

## 📁 Project Structure

```
url-shortener/
├── app/                    # Laravel application code
├── docker/
│   ├── nginx/
│   │   └── default.conf    # Nginx site configuration
│   └── php/
│       ├── Dockerfile      # PHP 8.3-FPM multi-stage image
│       ├── entrypoint.sh   # Container bootstrap script
│       └── php.ini         # PHP runtime configuration
├── docs/                   # Architecture diagrams & documentation
├── resources/
│   └── js/                 # Vue 3 + Inertia frontend
├── docker-compose.yml      # Docker Compose orchestration
├── vite.config.js          # Vite + HMR config
├── CHANGELOG.md
└── .env.example
```

---

## 🔐 Environment Variables

See [`.env.example`](./.env.example) for the full list. Key variables:

| Variable | Description | Default |
|---|---|---|
| `APP_KEY` | Laravel encryption key (auto-generated) | — |
| `DB_HOST` | MySQL host | `db` (Docker service name) |
| `DB_DATABASE` | Database name | `url_shortener` |
| `DB_USERNAME` | Database user | `urlshortener` |
| `DB_PASSWORD` | Database password | `secret` |
| `REDIS_HOST` | Redis host | `redis` (Docker service name) |
| `QUEUE_CONNECTION` | Queue driver | `redis` |
| `CACHE_STORE` | Cache driver | `redis` |
| `SESSION_DRIVER` | Session driver | `redis` |

---

## 🏥 Health Checks

Laravel 11 ships with a built-in `/up` health-check endpoint.

```bash
curl http://localhost/up
# → HTTP 200 OK
```

Docker Compose uses this endpoint to determine Nginx container health.

---

## 📈 Monitoring & Error Tracking

- **Laravel Pulse**: The application uses Laravel Pulse for server and application health monitoring. Once the application is running, you can access the dashboard at `/pulse` (ensure you are authenticated or have authorized access).
- **Sentry**: Error tracking is configured via Sentry. Set the `SENTRY_LARAVEL_DSN` in your `.env` file to enable production error logging.

---

## 🚀 Production Deployment

For production deployments, a separate `docker-compose.prod.yml` is provided. This uses a multi-stage, non-root Docker build for the application, and doesn't map local source volumes.

```bash
# Set production environment variables
cp .env.example .env

# Build and start the production stack
docker compose -f docker-compose.prod.yml up -d --build
```

---

## 📜 Changelog

See [CHANGELOG.md](./CHANGELOG.md).
