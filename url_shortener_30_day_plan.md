# 30-Day Development Plan: Enterprise-Grade URL Shortener (Improved)

This document outlines a rapid 30-day development schedule to build a full-featured URL shortening platform comparable to bit.ly. The architecture leverages **Laravel** for the backend and server-rendered pages, **Inertia.js** for seamless SPA-like navigation without a separate REST API, **Vue.js 3** for the frontend UI, **MySQL** for the database, **Redis** for caching/sessions/rate limiting/queues, and **Docker** for containerized development and deployment.

---

## 🛑 Strict Documentation Guidelines
**Rule:** Every update, commit, and feature merge must be accompanied by documentation.
1. **Code-Level:** Inline docblocks for all classes, methods, and complex algorithms.
2. **API Documentation:** Update OpenAPI/Swagger specifications for every new or modified **public API** endpoint before merging to the main branch.
3. **Changelog:** Maintain a `CHANGELOG.md`. Each daily milestone completion requires a corresponding entry detailing added features, changed logic, and fixed bugs.
4. **README & System Docs:** Update architecture diagrams, environment variable requirements, and Docker configuration docs immediately upon any structural change.
5. **Definition of Done:** Each task must include unit/feature tests, documentation updates, and a peer review before merging.

---

## Phase 1: Foundation & Infrastructure (Days 1 - 5)
*Goal: Establish the development environment, database schema, and core framework scaffolding with observability and security in mind.*

*   ✅ **Day 1: Project Scaffolding & Containerization** *(Completed 2026-07-21)*
    *   ✅ Initialize the Laravel monolith with Inertia.js and Vue 3 scaffolding (`laravel new` + `php artisan breeze:install inertia` or manual Inertia setup).
    *   ✅ Set up Docker orchestration (`docker-compose`) with containers for:
        *   ✅ Laravel application (PHP-FPM)
        *   ✅ Nginx reverse proxy
        *   ✅ MySQL database
        *   ✅ Redis (caching, sessions, rate limiting, queues)
        *   ✅ Laravel Queue worker container
        *   ✅ Node/Vite build service for compiling Vue assets
        *   ⏭️ (Optional) MinIO for object storage (QR codes, exports) — *deferred to Day 12*
    *   ✅ Document the setup instructions in the repository README.
    *   **Acceptance Criteria:** `docker compose up` boots all services; health checks pass; Vite HMR works for Vue files.

*   ✅ **Day 2: Database Design, Migrations & Indexing Strategy** *(Completed 2026-07-22)*
    *   ✅ Design the MySQL schema: `users`, `links`, `clicks`, `workspaces`, `workspace_user`, `custom_domains`, `api_keys`, `webhooks`, `subscription_plans`.
    *   ✅ Write and execute database migrations (13 migrations including all core tables).
    *   ✅ Define indexes upfront: `links.short_code` (unique), `links.user_id`, `links.workspace_id`, `links.custom_domain_id`, `clicks.link_id` + `created_at`, `clicks.country`.
    *   ✅ 9 Eloquent models with docblocks, `$fillable`, casts, and relationships.
    *   ✅ 8 model factories with named states for testing.
    *   ✅ ERD diagram committed to `/docs/erd.md`.
    *   **Acceptance Criteria:** Migrations run cleanly; ERD diagram committed to `/docs`.

*   ✅ **Day 3: User Authentication & Security Setup** *(Completed 2026-07-22)*
    *   ✅ Implement user registration, login, email verification, password reset via Laravel Breeze.
    *   ✅ Set up secure password hashing (bcrypt), HTTPS-only cookies (`SESSION_SECURE_COOKIE=true`), CSRF protection, auth middleware.
    *   ✅ Add initial rate limiting (`throttle:6,1`) on auth endpoints (register, forgot-password).
    *   ✅ `MustVerifyEmail` interface added to `User` model.
    *   **Acceptance Criteria:** Users can register, verify email, log in, and access protected Inertia routes.

*   ✅ **Day 4: Core Frontend Architecture with Vue 3 + Inertia** *(Completed 2026-07-22)*
    *   ✅ Set up Inertia.js with Vue 3, Pinia for global state (`useToastStore`), and shared layout (`AppLayout.vue`).
    *   ✅ `ToastNotification.vue` — global toast with success/error/info/warning types and transition animations.
    *   ✅ `AppLayout.vue` — glassmorphism nav, dark mode, responsive hamburger, decorative blob animations.
    *   ✅ Configure Vite, Tailwind CSS with brand color palette, Inter font, custom animations.
    *   ✅ Global Inertia progress indicator configured in `app.js` (color updated to brand-500 `#6366f1`).
    *   ✅ Enhanced `PrimaryButton.vue` with gradient, shadow, hover animations.
    *   ✅ Enhanced `TextInput.vue` with brand focus rings, dark mode support.
    *   ✅ Upgraded `SecondaryButton.vue` — lift/shadow hover, dark mode, brand focus ring.
    *   ✅ Upgraded `DangerButton.vue` — `red→rose` gradient, lift animation, dark mode.
    *   ✅ Upgraded `Modal.vue` — blurred backdrop, dark-mode panel, rounded-2xl, built-in close button.
    *   ✅ `Badge.vue` — 6 variants, 3 sizes, optional pulsing dot, dark mode.
    *   ✅ `LoadingSpinner.vue` — 5 sizes, 3 variants, accessible `role="status"`.
    *   ✅ `IconButton.vue` — ghost/solid/outline, 4 sizes, required `aria-label`.
    *   ✅ `Dashboard.vue` — upgraded with welcome header, stats cards, UI component showcase, modal demo.
    *   **Acceptance Criteria:** Base Vue components render; Inertia page navigation works without full reloads. ✅

*   ✅ **Day 5: CI/CD, Baseline Documentation & Observability** *(Completed 2026-07-22)*
    *   ✅ GitHub Actions CI pipeline (`.github/workflows/ci.yml`) — PHP Pint lint, PHPUnit tests, ESLint + Prettier checks.
    *   ✅ Dependabot (`.github/dependabot.yml`) — weekly automated updates for Composer, npm, and GitHub Actions.
    *   ✅ ESLint + Prettier configured (`.eslintrc.cjs`, `.prettierrc`); `lint`, `lint:fix`, `format`, `format:check` npm scripts added.
    *   ✅ `GET /health` endpoint (`HealthController`) — checks MySQL + Redis; returns `200`/`503` structured JSON with timestamp, version, and per-dependency statuses.
    *   ✅ Structured JSON logging channel (`json_stderr`) added to `config/logging.php` for Docker/production log aggregation.
    *   ✅ OpenAPI 3.1 skeleton (`docs/openapi.yaml`) — BearerToken auth, Link CRUD + analytics paths, reusable schemas and responses.
    *   ✅ `tests/Feature/HealthCheckTest.php` — 3 assertions; all 28 project tests pass.
    *   **Acceptance Criteria:** CI pipeline passes on `main`; `/health` endpoint returns 200. ✅

---

## Phase 2: The Core Engine (Days 6 - 11)
*Goal: Build the fundamental URL shortening and redirection logic with scalability and safety guardrails.*

*   ✅ **Day 6: URL Normalization & The Shortening Algorithm** *(Completed 2026-07-22)*
    *   ✅ URL validation and normalization (strip 19 tracking params, enforce HTTPS, lowercase domain, sort query params, remove trailing slashes).
    *   ✅ SSRF prevention: blocks loopback hostnames, private/reserved IP ranges, and the app's own domain.
    *   ✅ Base62 encoding (`UrlNormalizerService` + `ShortCodeGeneratorService`) using DB auto-increment id — collision-free by design.
    *   ✅ `StoreLinkRequest` form request with server-side URL validation.
    *   ✅ `LinkController` with `index()` (shorten page) and `store()` (atomic create via `DB::transaction()` + UUID placeholder + Base62 update).
    *   ✅ Per-user deduplication: same normalized URL → reuse existing short code with info flash.
    *   ✅ `CopyButton.vue` — 3 variants, 3 sizes, Clipboard API + fallback, animated "Copied!" feedback.
    *   ✅ `Pages/Links/Shorten.vue` — premium shorten form with paste button, collapsible title, loading state, result card, recent links list, empty state.
    *   ✅ "Shorten" nav link added to `AppLayout.vue` (desktop + mobile).
    *   ✅ 43 new tests (13 feature + 20 unit normalizer + 10 unit encoder); all 71 project tests pass.
    *   **Acceptance Criteria:** Valid URLs shorten ✅; invalid URLs rejected ✅; duplicate long URLs reuse existing short codes ✅.

*   **Day 7: Redirection, Caching & 301/302 Strategy**
    *   Build the redirect controller. Look up the short hash in Redis (fallback to MySQL) and perform an HTTP redirect.
    *   Decide redirect semantics: use **302 Found** for analytics accuracy during active campaigns; **301 Moved Permanently** for permanent branded links.
    *   Implement cache invalidation on link update/delete.
    *   **Acceptance Criteria:** Redirects resolve in <50ms when cached; cache invalidation works.

*   **Day 8: Dashboard UI & Link Management**
    *   Develop the Vue dashboard page where users can view their generated links with pagination and search.
    *   Implement CRUD operations (Create, Read, Update, Delete/Archive) using Inertia form helpers and Laravel controllers.
    *   **Acceptance Criteria:** Users can create, list, edit, archive, and delete links via the Inertia UI.

*   **Day 9: Custom Aliases & Reserved Words**
    *   Update backend logic to accept and validate custom aliases (e.g., `domain.com/my-custom-name`).
    *   Ensure alias uniqueness checks and collision handling.
    *   Maintain a reserved-words list to prevent aliases like `api`, `admin`, `login`, `health`, `swagger`.
    *   **Acceptance Criteria:** Custom aliases work; reserved words and duplicates are rejected with clear errors.

*   **Day 10: Malicious Link Protection**
    *   Integrate a third-party API (Google Safe Browsing or PhishTank) to scan submitted URLs.
    *   Block known malicious links and maintain a `blocked_urls` table for manual bans.
    *   **Acceptance Criteria:** Malicious URLs are blocked; safe URLs pass; scan failures default to block or queue for review.

*   **Day 11: Buffer / Core Engine Hardening**
    *   Add feature tests for the redirect engine and shortening flow.
    *   Perform initial load test on the redirect endpoint.
    *   Fix any critical issues discovered.
    *   **Acceptance Criteria:** Redirect endpoint handles 1,000 RPS in local load test.

---

## Phase 3: Advanced Link Features (Days 12 - 16)
*Goal: Add marketing tools to elevate the platform beyond a basic shortener.*

*   **Day 12: QR Code Generation**
    *   Integrate a QR code generation library (e.g., `endroid/qr-code` for PHP).
    *   Allow users to generate, customize (colors/logos), and download QR codes for their short links.
    *   Store generated QR assets in object storage (MinIO/S3) with CDN-friendly URLs.
    *   **Acceptance Criteria:** QR codes generate and download as PNG/SVG.

*   **Day 13: UTM Parameter Builder**
    *   Build a Vue UI tool allowing users to append `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` to their long URLs before shortening.
    *   Validate UTM values and preserve them through redirects.
    *   **Acceptance Criteria:** Shortened URL redirects to the correct long URL with UTM params intact.

*   **Day 14: Link Expiration & Password Protection**
    *   Add features allowing users to set expiration dates/times for links.
    *   Implement password gateways for specific short URLs.
    *   **Acceptance Criteria:** Expired links return 410 Gone; password-protected links require correct password.

*   **Day 15: Deep Linking for Mobile Apps (MVP)**
    *   Implement User-Agent detection to redirect mobile users to app URIs where configured (e.g., `twitter://` or Universal Links).
    *   Keep fallback to the original web URL.
    *   **Acceptance Criteria:** Mobile deep links redirect to app scheme; desktop users go to web URL.

*   **Day 16: Bulk Shortening**
    *   Develop a Vue interface and backend processor for uploading CSVs to shorten multiple URLs simultaneously.
    *   Process large CSVs asynchronously via Laravel Queue and notify the user when complete.
    *   **Acceptance Criteria:** CSV upload returns a job ID; results are downloadable or emailed when done.

---

## Phase 4: Analytics & Tracking (Days 17 - 21)
*Goal: Capture, process, and display comprehensive click data while respecting privacy regulations.*

*   **Day 17: Click Tracking Ingestion Engine**
    *   Enhance the redirect controller to dispatch asynchronous tracking jobs (queue) to avoid delaying the redirect.
    *   Capture IP (hashed/anonymized for GDPR), User-Agent, Referrer, and timestamp.
    *   **Acceptance Criteria:** Redirect latency remains <50ms p95; click events are queued reliably.

*   **Day 18: Device & Location Parsing**
    *   Implement GeoIP lookups to determine country/city from IPs.
    *   Parse User-Agent strings to categorize device type (Desktop, Mobile, Tablet), OS, and browser.
    *   **Acceptance Criteria:** Sample IPs resolve to correct country/device categories.

*   **Day 19: Analytics Database Aggregation**
    *   Write scheduled tasks (Laravel Scheduler) to aggregate raw click data into daily/hourly summaries for fast querying.
    *   Consider partitioning the `clicks` table by date for long-term scalability.
    *   **Acceptance Criteria:** Aggregation job runs and produces accurate summary rows.

*   **Day 20: Analytics Dashboard (Frontend)**
    *   Integrate a charting library (Chart.js or Vue Chart.js) into the Vue/Inertia app.
    *   Build time-series line charts for click volume and summary KPI cards.
    *   **Acceptance Criteria:** Dashboard displays click volume over time with date range filtering.

*   **Day 21: Detailed Reports UI & Privacy Compliance**
    *   Create breakdown views: Clicks by Country (map view), Clicks by Referrer, and Clicks by Device/Browser.
    *   Add data retention settings and IP anonymization toggle.
    *   **Acceptance Criteria:** Reports render correctly; privacy settings are documented.

---

## Phase 5: Enterprise & Premium Features (Days 22 - 26)
*Goal: Implement features required for B2B and power users.*

*   **Day 22: Custom Domains**
    *   Develop the logic to allow users to attach their own domains (e.g., `link.theirbrand.com`).
    *   Validate domain ownership (DNS TXT record or CNAME).
    *   Document the DNS configuration (A-record/CNAME) requirements for users.
    *   **Acceptance Criteria:** Custom domain resolves and serves redirects correctly.

*   **Day 23: Link-in-Bio Pages (MVP)**
    *   Build a micro-site generator allowing users to create customizable landing pages hosting multiple links.
    *   Support themes, profile image, and basic layout options.
    *   **Acceptance Criteria:** A public link-in-bio page renders and tracks outbound clicks.

*   **Day 24: Workspaces & Teams**
    *   Implement organizational accounts (Workspaces).
    *   Allow users to invite team members via email and accept invitations.
    *   **Acceptance Criteria:** Users can create workspaces, invite members, and switch contexts.

*   **Day 25: Role-Based Access Control (RBAC) & Audit Logs**
    *   Assign roles (Admin, Editor, Viewer) within workspaces.
    *   Restrict link creation, deletion, and analytics viewing based on user roles.
    *   Add audit logs for critical actions (login, link delete, member invite, role change).
    *   **Acceptance Criteria:** Role-based gates enforced via middleware; audit log entries created.

*   **Day 26: SSO (Single Sign-On) & API Keys**
    *   Integrate enterprise authentication options: Google Workspace OAuth2 and SAML 2.0 (via Laravel Socialite / a SAML package).
    *   Expose secure endpoints for the public RESTful API.
    *   Implement API Key generation and management in the user dashboard.
    *   **Acceptance Criteria:** SSO login works; API keys authenticate requests; endpoints documented.

---

## Phase 6: API, Polish & Launch (Days 27 - 30)
*Goal: Finalize developer tools, perform QA, harden security, and deploy.*

*   **Day 27: Public RESTful API, Webhooks & API Versioning**
    *   Finalize public RESTful endpoints for creating, updating, listing, and deleting links.
    *   Implement API versioning (`/v1/...`) from day one.
    *   Allow users to subscribe to events (e.g., `link.clicked`) and push payloads to designated endpoint URLs with signature verification.
    *   **Acceptance Criteria:** Webhook deliveries include HMAC signature; API version is stable.

*   **Day 28: Rate Limiting, Abuse Prevention & Billing Integration**
    *   Configure Redis-based rate limiting per IP, per user, and per API key.
    *   Integrate Stripe for subscription tiers (Free, Pro, Enterprise) guarding advanced features.
    *   Add feature-gating middleware based on subscription plan.
    *   **Acceptance Criteria:** Rate limits enforced; Stripe checkout and subscription status sync work.

*   **Day 29: End-to-End Testing, Security Audit & QA**
    *   Execute comprehensive feature and unit tests (target >80% coverage on core paths).
    *   Perform dependency vulnerability scan and basic penetration testing (OWASP Top 10 checks).
    *   Perform load testing on the redirect engine to ensure high throughput.
    *   **Acceptance Criteria:** Test suite passes; no critical/high vulnerabilities; redirect engine handles target load.

*   **Day 30: Production Deployment, Monitoring & Launch**
    *   Finalize production Docker images (multi-stage, non-root users).
    *   Deploy database, cache, queue workers, and application to the production server architecture.
    *   Set up monitoring (e.g., Prometheus/Grafana or Laravel Pulse), error tracking (Sentry), and log aggregation.
    *   Ensure all final documentation is compiled and published.
    *   **Acceptance Criteria:** Production health checks pass; monitoring dashboards active; launch checklist complete.

---

## 🎯 Success Metrics
*   Redirect p95 latency < 100ms (cached)
*   Shortening API availability > 99.9% during launch window
*   Core test coverage > 80%
*   Zero critical security vulnerabilities at launch
*   Public API response time < 200ms p95

## ⚠️ Risk Register
| Risk | Impact | Mitigation |
|------|--------|------------|
| Collision-prone short-code generation | High | Use DB auto-increment + Base62; reserve custom aliases |
| GDPR/privacy violations from IP storage | High | Hash/anonymize IPs; add retention controls |
| Malicious links damaging domain reputation | High | Safe Browsing + manual blocklist + user reports |
| Redis failure causing redirect outage | Medium | Fallback to MySQL + Redis Sentinel/Cluster in prod |
| SSO/SAML complexity overrunning schedule | Medium | Defer SAML to post-MVP if needed; ship OAuth2 first |
| Load testing reveals bottlenecks late | Medium | Run mini load tests in Phase 2 and Phase 4 |

## 🗓️ Recommended Schedule Adjustments
*   **Buffer days** are built into Phase 2 (Day 11) and can be shifted if earlier tasks slip.
*   If the team is small, consider moving **Link-in-Bio (Day 23)** and **SAML (Day 26)** to a post-MVP roadmap.
*   Begin writing tests on Day 5 and continue daily; avoid leaving all QA for Day 29.
