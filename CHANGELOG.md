# Changelog

All notable changes to the URL Shortener project are documented here.

This project follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) conventions and [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

---

## [0.1.0] — 2026-07-21 · Day 1: Project Scaffolding & Containerization

### Added

- **Laravel 11** monolith scaffolded (PHP 8.3).
- **Laravel Breeze** with Inertia.js + Vue 3 adapter installed.
  - Includes auth pages: Login, Register, Email Verification, Password Reset, Dashboard.
  - Tailwind CSS configured via Vite.
- **Docker Compose** orchestration (`docker-compose.yml`) with:
  - `app` — PHP 8.3-FPM application container.
  - `nginx` — Nginx 1.25 reverse proxy (port 80).
  - `db` — MySQL 8.0 (port 3306, persistent volume).
  - `redis` — Redis 7 (port 6379, persistent volume, AOF enabled).
  - `queue` — Laravel queue worker (Redis-backed).
  - `node` — Node 20 / Vite dev server with HMR (port 5173).
- **Docker support files**:
  - `docker/php/Dockerfile` — Multi-stage PHP 8.3-FPM image with Composer, Redis extension, GD, and all required extensions.
  - `docker/php/entrypoint.sh` — Bootstrap script: waits for DB, runs migrations, clears caches.
  - `docker/php/php.ini` — Custom PHP runtime configuration.
  - `docker/nginx/default.conf` — Nginx site config with PHP-FPM proxy, Vite HMR proxy, and security headers.
- **Environment configuration**:
  - `.env` updated with Docker service hostnames (`db`, `redis`), MySQL credentials, Redis-backed sessions/cache/queues.
  - `.env.example` committed with secrets redacted.
- **Vite HMR** configured for Docker (`host: 0.0.0.0`, `hmr.host: localhost`).
- **`README.md`** with full setup instructions, Docker service table, common commands, and health check docs.
- **`docs/`** directory created for ERD diagrams and architecture documentation.

### Notes

- MinIO (object storage) deferred to Day 12 (QR code generation).
- Local domain: `http://localhost`.
- Health check endpoint: `GET /up` (Laravel 11 built-in).
