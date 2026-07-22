# Changelog

All notable changes to the URL Shortener project are documented here.

This project follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) conventions and [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

---

## [0.7.0] — 2026-07-22 · Day 7: Redirection, Caching & 301/302 Strategy

### Added

- **`app/Http/Controllers/RedirectController.php`:** The core redirect engine endpoint.
  - Features cache-first lookup (Redis) with MySQL fallback.
  - Uses 302 Found semantics by default to preserve click analytics.
  - Returns 410 Gone for expired links.
  - Returns 404 for inactive or soft-deleted links.
- **`app/Services/RedirectCacheService.php`:** Manages Redis caching for redirects.
  - Writes `{original_url, redirect_type, is_active, expires_at}` payloads with a 24-hour TTL (`LINK_TTL`).
  - Implements tombstone caching (`NOT_FOUND_TTL` = 5 mins) for unknown short codes to prevent MySQL hammering from bots or typos.
- **`routes/web.php`:** Added `/{shortCode}` catch-all route. Placed at the very bottom and constrained to `[0-9A-Za-z]+` so it cannot shadow named application routes.
- **Tests:** Added `RedirectControllerTest` (Feature) and `RedirectCacheServiceTest` (Unit) covering all hit/miss, expiry, inactivity, and invalidation scenarios.

### Changed

- **`app/Http/Controllers/LinkController.php`:** Injected `RedirectCacheService` to immediately warm the cache on link creation (`store()`), and added `update()` / `destroy()` stubs that successfully perform cache invalidation.

## [0.6.0] — 2026-07-22 · Day 6: URL Normalization & The Shortening Algorithm

### Added

- **`app/Exceptions/InvalidUrlException.php`:** Custom `RuntimeException` subclass for URL validation failures; carries `$httpStatus = 422` for clean API error responses.
- **`app/Services/UrlNormalizerService.php`:** Full URL normalization and validation pipeline:
  - Injects `https://` scheme if missing.
  - Lowercases the host component.
  - Strips default ports (80 for HTTP, 443 for HTTPS).
  - Removes 19 known tracking parameters: `utm_*`, `fbclid`, `gclid`, `gclsrc`, `mc_eid`, `mc_cid`, `_ga`, `ref`, `source`, `trk`, `twclid`, `igshid`, `msclkid`, `dclid`.
  - Sorts remaining query params alphabetically for canonical deduplication.
  - Removes trailing slashes from non-root paths.
  - Validates scheme (`http`/`https` only), hostname characters, loopback hostnames, and private/reserved IP ranges (SSRF prevention via `gethostbyname` + CIDR matching).
  - Blocks the app's own domain to prevent redirect loops.
- **`app/Services/ShortCodeGeneratorService.php`:** Collision-free Base62 encoder:
  - Alphabet: `0-9A-Za-z` (62 characters). ID 1 → `"1"`, ID 10 → `"A"`, ID 62 → `"10"`, 1M IDs ≤ 4 characters.
  - `encode(int $id): string` — converts DB auto-increment `id` to Base62.
  - `decode(string $code): int` — reverse operation for debugging.
  - `generateForLink(Link $link): string` — encodes the link's `id` and persists `short_code`.
- **`app/Http/Requests/StoreLinkRequest.php`:** Form request for link creation; delegates URL safety to `UrlNormalizerService` and converts `InvalidUrlException` to a user-facing field-level validation error.
- **`app/Http/Controllers/LinkController.php`:** Two actions — `index()` renders the Inertia shorten page (with the user's 5 most recent links), `store()` normalizes, deduplicates (per-user), creates atomically via `DB::transaction()` with a UUID placeholder, generates Base62 code, and redirects with a flash payload.
- **`resources/js/Components/CopyButton.vue`:** Reusable clipboard copy button — 3 variants (ghost/solid/outline), 3 sizes, animated "Copied!" tick feedback for 2s, Clipboard API with `execCommand` fallback.
- **`resources/js/Pages/Links/Shorten.vue`:** Premium URL shortening page:
  - Large URL input with inline paste-from-clipboard button.
  - Collapsible optional title field with slide animation.
  - Gradient shorten button with loading spinner.
  - Animated result card (success/info) with short URL and `CopyButton`.
  - Recent links list (up to 5) with click count, relative timestamp, and per-row copy button.
  - Empty state for first-time users.
- **Routes:** `GET /links/shorten` → `links.index` and `POST /links` → `links.store`, both guarded by `auth` + `verified` middleware.
- **`tests/Feature/LinkShorteningTest.php`:** 13 feature tests — auth gates, URL normalization side-effects, Base62 format, scheme injection, invalid/localhost/ftp URL rejection, deduplication, per-user scoping, and Inertia page render.
- **`tests/Unit/Services/UrlNormalizerServiceTest.php`:** 20 unit tests covering the full normalization pipeline and all validation edge cases.
- **`tests/Unit/Services/ShortCodeGeneratorServiceTest.php`:** 10 unit tests covering encoding correctness, decode roundtrip, code length bounds, uniqueness, and alphabet integrity.

### Changed

- **`app/Models/Link.php`:** Added `scopeForUser(int $userId)` query scope for per-user link filtering.
- **`resources/js/Layouts/AppLayout.vue`:** Added "Shorten" `NavLink` (desktop) and `ResponsiveNavLink` (mobile) pointing to `route('links.index')`.
- **`routes/web.php`:** Registered `GET /links/shorten` and `POST /links` under the `auth + verified` middleware group.

---

## [0.5.0] — 2026-07-22 · Day 5: CI/CD, Baseline Documentation & Observability

### Added

- **GitHub Actions CI Pipeline (`.github/workflows/ci.yml`):**
  - `php-lint` job: Runs `./vendor/bin/pint --test` (PSR-12 / Laravel opinionated style) on every push and PR to `main`.
  - `php-tests` job (depends on `php-lint`): Runs `php artisan test --parallel` using SQLite in-memory and an ephemeral Redis service container.
  - `js-lint` job: Installs npm dependencies and runs `npm run lint` (ESLint) and `npm run format:check` (Prettier).
  - `concurrency` group ensures only one pipeline runs per ref, cancelling stale runs automatically.
- **Dependabot (`.github/dependabot.yml`):** Weekly automated dependency updates for Composer, npm, and GitHub Actions packages. Grouped updates for related packages (e.g., `laravel/*`, `vue ecosystem`, `vite + plugins`).
- **ESLint Configuration (`.eslintrc.cjs`):** Vue 3 / ES2022 rules using `eslint:recommended` + `plugin:vue/vue3-recommended`. `vue/multi-word-component-names` disabled for Inertia page compatibility.
- **Prettier Configuration (`.prettierrc`, `.prettierignore`):** 4-space indent, single quotes, trailing commas, 120-column print width. Ignores `vendor/`, `node_modules/`, `public/build/`, and lock files.
- **npm Lint Scripts:** Added `lint`, `lint:fix`, `format`, and `format:check` scripts to `package.json`. Added `eslint@^8`, `eslint-plugin-vue@^9`, and `prettier@^3` as devDependencies.
- **`/health` Endpoint (`app/Http/Controllers/HealthController.php`):**
  - Publicly accessible `GET /health` route (no auth, no CSRF).
  - Checks: MySQL (`SELECT 1`) and Redis (cache write/read probe).
  - Returns `200 { status: "ok", timestamp, version, checks }` or `503 { status: "degraded" }` when a dependency is unreachable.
  - Emits a structured log line per invocation for observability pipelines.
- **Structured JSON Logging (`config/logging.php`):** Added `json_stderr` Monolog channel using `JsonFormatter` writing to `php://stderr`. Enable in production via `LOG_STACK=single,json_stderr`.
- **OpenAPI 3.1 Skeleton (`docs/openapi.yaml`):** Documents the public API surface including:
  - `BearerToken` security scheme.
  - `Link`, `CreateLinkRequest`, `UpdateLinkRequest`, `PaginatedLinks`, `Error` component schemas.
  - Reusable `401`, `403`, `404`, `429` response components with correct headers.
  - `GET/POST /api/v1/links`, `GET/PATCH/DELETE /api/v1/links/{id}`, and `GET /api/v1/links/{id}/analytics` (analytics placeholder for Day 17).
- **`tests/Feature/HealthCheckTest.php`:** 3 assertions — HTTP 200 shape validation, public accessibility (no auth), and `Content-Type: application/json` header.
- **`APP_VERSION` environment variable:** Added to `.env.example` (default `0.5.0`); surfaced in `/health` JSON response via `config('app.version')`.

### Changed

- `routes/web.php`: Registered `GET /health` → `HealthController` (named `health`).
- `.env.example`: Added `APP_VERSION=0.5.0` and a comment documenting `json_stderr` log channel usage.

---

## [0.4.0] — 2026-07-22 · Day 4: Core Frontend Architecture with Vue 3 + Inertia

### Added

- **Pinia State Management:** Installed `pinia` and integrated it with the Vue app setup in `resources/js/app.js`.
- **Toast Notifications:**
  - Created `useToastStore.js` to manage global notifications (success, error, info, warning).
  - Built `ToastNotification.vue` component with dynamic Tailwind styling, SVG icons for types, and enter/leave transition animations.
- **App Layout:** Created `AppLayout.vue` featuring a premium glassmorphism sticky navigation bar, dark mode support, responsive hamburger menu, and decorative blob background animations.
- **Badge Component (`Badge.vue`):** Versatile status/label badge with 6 color variants (default, primary, success, warning, danger, info), 3 sizes (sm, md, lg), optional pulsing dot indicator, and full dark mode support via `ring-inset` styling.
- **LoadingSpinner Component (`LoadingSpinner.vue`):** Animated SVG spinner with 5 sizes (xs → xl) and 3 color variants (brand, white, gray); includes `aria` `role="status"` and `sr-only` label for accessibility.
- **IconButton Component (`IconButton.vue`):** Compact icon-only button with ghost/solid/outline variants, 4 sizes (xs → lg), required `aria-label` for accessibility, and dark mode.
- **Dashboard Page (`Dashboard.vue`):** Upgraded from placeholder to a full-featured page with:
  - Welcome header with user name greeting.
  - 4-column stats overview cards (total links, clicks, active links, custom aliases) with glassmorphism hover effects.
  - Complete UI component showcase (buttons, badges, icon buttons, loading spinners, toasts, modal).
  - "Shorten URL" CTA button opening a modal preview (full functionality ships Day 6).
- **Tailwind Enhancements:**
  - Integrated `Inter` font in `tailwind.config.js` and `resources/css/app.css`.
  - Added custom `brand` color palette and keyframe animations (`blob`, `fadeIn`).

### Changed

- Enhanced `PrimaryButton.vue` with premium gradient (`brand-600 → purple-600`), shadow transitions, and hover lift animations.
- Enhanced `TextInput.vue` with `brand` focus rings, rounded-lg border, and dark mode compatibility.
- Upgraded `SecondaryButton.vue` with lift/shadow hover effect, dark mode support, and brand focus ring.
- Upgraded `DangerButton.vue` with gradient (`red-600 → rose-600`), lift animation, and dark mode consistency.
- Upgraded `Modal.vue` to glassmorphism style: blurred backdrop (`backdrop-blur-sm`), dark-mode aware panel (`dark:bg-gray-900`), rounded-2xl, and built-in close button.
- Updated Inertia progress bar color from `#4B5563` to `#6366f1` (brand-500) in `app.js`.

---

## [0.3.0] — 2026-07-22 · Day 3: User Authentication & Security Setup

### Added

- **User Authentication:** Enabled Laravel Breeze (installed in Day 1) authentication flow (login, register, forgot password, reset password).
- **Email Verification:** Implemented `MustVerifyEmail` interface on the `User` model to enforce email verification.
- **Security Enhancements:**
  - Enforced HTTPS-only cookies (`SESSION_SECURE_COOKIE=true` in `.env`).
  - Implemented rate limiting (`throttle:6,1`) on `register` and `forgot-password` endpoints to match the default login rate limit (via `LoginRequest`).

### Changed

- Updated `User` model to implement `MustVerifyEmail`.
- Updated `routes/auth.php` to include `throttle:6,1` middleware on `register` and `forgot-password` POST routes.
- Updated `.env` and `.env.example` configurations.

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
