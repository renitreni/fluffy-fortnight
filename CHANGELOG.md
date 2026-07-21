# Changelog

All notable changes to the URL Shortener project are documented here.

This project follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) conventions and [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

---

## [0.2.0] — 2026-07-22 · Day 2: Database Design, Migrations & Indexing Strategy

### Added

- **10 new database migrations** covering the complete application schema:
  - Extended `users` table: `avatar`, `timezone`, `locale`, `is_active`, `subscription_plan_id` FK.
  - `subscription_plans` — plan tiers with Stripe price IDs, feature flags JSON, and per-plan limits.
  - `workspaces` — organizational units with owner FK, slug, and custom domain limit; soft-deletable.
  - `workspace_user` — RBAC pivot table with `role` enum (`admin`, `editor`, `viewer`) and `joined_at`.
  - `custom_domains` — branded short-link hosts with DNS verification token and SSL status tracking; soft-deletable.
  - `links` — core entity with `short_code` (UNIQUE), expiry, password gate, UTM params, mobile deep links, denormalized `click_count`, and composite redirect hot-path index (`is_active`, `expires_at`); soft-deletable.
  - `clicks` — append-only event log with GDPR-safe IP hashing, GeoIP fields, device/OS/browser classification, and five analytics indexes; no `updated_at`, no soft deletes.
  - `api_keys` — hashed programmatic credentials with scoped `abilities` JSON and `key_prefix` for UI display.
  - `webhooks` — HMAC-signed event subscriptions with failure tracking and auto-disable logic.
  - `blocked_urls` — URL denylist keyed on `url_hash` (SHA-256) for O(1) lookup without index length issues.
- **9 Eloquent models** with docblocks, full `$fillable`, casts, and relationships:
  `SubscriptionPlan`, `Workspace`, `WorkspaceUser` (Pivot), `CustomDomain`, `Link`, `Click`, `ApiKey`, `Webhook`, `BlockedUrl`.
  - `Link` model includes `scopeActive()` for the redirect engine.
  - `Click` model uses `clicked_at` as the `CREATED_AT` constant; `UPDATED_AT` set to `null`.
- **8 model factories** with named states for testing edge cases:
  - `SubscriptionPlanFactory` — `free()`, `pro()`, `enterprise()` states.
  - `LinkFactory` — `expired()`, `passwordProtected()`, `inactive()`, `withUtm()` states.
  - `CustomDomainFactory` — `verified()` state.
  - `ApiKeyFactory` — `revoked()` state.
- **`SubscriptionPlanSeeder`** — upserts the three core plans idempotently; safe to re-run.
- **`DatabaseSeeder`** updated to call `SubscriptionPlanSeeder` first (FK dependency order).
- **`/docs/erd.md`** — full Mermaid ERD, index summary table, and design-decision rationale.

### Changed

- `User` model: added `avatar`, `timezone`, `locale`, `is_active`, `subscription_plan_id` to `$fillable`; added `is_active` cast; added `subscriptionPlan`, `ownedWorkspaces`, `workspaces`, `links`, `customDomains`, `apiKeys`, `webhooks` relationships.

### Design Decisions

- `clicks.link_id` has no FK constraint at the DB level to maximise write throughput on the redirect path.
- `links.click_count` is a denormalized counter synced by the queue job (Day 17); reconciled by aggregation job (Day 19).
- `users.subscription_plan_id` is a simple FK for now; a full Stripe `subscriptions` table will be added in Day 28.
- `clicks` table partitioning by date deferred to Day 19 (Analytics Aggregation) to avoid migration complexity today.

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
