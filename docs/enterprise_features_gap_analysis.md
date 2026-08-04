# Enterprise Features Gap Analysis

> **Date:** 2026-08-03  
> **Project:** Elido URL Shortener  
> **Scope:** Audit of Enterprise plan features vs. actual codebase implementation

---

## Executive Summary

The **Enterprise plan** advertises **13 features**, but **1 is completely missing** and **3 are only partially implemented**. This document identifies the gaps and provides a **7-day remediation plan** to bring the Enterprise tier to full feature parity.

---

## Feature Matrix

| # | Feature | Status | Database | Models | Controllers | Jobs | UI | Notes |
|---|---------|--------|----------|--------|-------------|------|-----|-------|
| 1 | Analytics Dashboard | ✅ Complete | ✅ | ✅ | ✅ | ✅ | ✅ | GeoIP, device parsing, charts |
| 2 | Custom Domains | ✅ Complete | ✅ | ✅ | ✅ | — | ✅ | DNS TXT/CNAME validation |
| 3 | API Access | ✅ Complete | ✅ | ✅ | ✅ | — | ✅ | Keys, REST v1, rate limiting |
| 4 | Bulk Shortening | ✅ Complete | ✅ | ✅ | ✅ | ✅ | ✅ | CSV upload, queue, notifications |
| 5 | Password Protection | ✅ Complete | ✅ | ✅ | ✅ | — | ✅ | bcrypt gate pages |
| 6 | Link Expiration | ✅ Complete | ✅ | ✅ | ✅ | — | ✅ | 410 Gone for expired |
| 7 | UTM Builder | ✅ Complete | ✅ | ✅ | — | — | ✅ | 5 UTM params on Link model |
| 8 | SSO | ⚠️ Partial | ✅ | ✅ | ✅ | — | ✅ | Only Google OAuth; **SAML 2.0 missing** |
| 9 | Audit Logs | ⚠️ Partial | ✅ | ✅ | ❌ | ❌ | ❌ | Model exists, **not wired up** |
| 10 | RBAC | ✅ Complete | ✅ | ✅ | ✅ | — | ✅ | Admin/Editor/Viewer roles |
| 11 | Webhooks | ⚠️ Partial | ✅ | ✅ | ❌ | ❌ | ❌ | Model exists, **no dispatch job or UI** |
| 12 | Link-in-Bio Pages | ✅ Complete | ✅ | ✅ | ✅ | — | ✅ | Public pages, themes |
| 13 | QR Code Generation | ❌ Missing | ❌ | ❌ | ❌ | ❌ | ❌ | **Zero implementation** |

---

## Partially Implemented Features

### 1. Webhooks

**What's Done:**
- `webhooks` migration (`2026_07_22_000009_create_webhooks_table.php`)
- `Webhook` Eloquent model with casts, fillable, relationships
- Database columns: `url`, `events` (JSON), `secret`, `is_active`, `failure_count`, `last_triggered_at`
- Mentioned in the 30-day plan (Day 27) as completed

**What's Missing:**
- `WebhookController` — no CRUD endpoints for users to create/manage webhook subscriptions
- `WebhookDispatchJob` — referenced in model docblock but **does not exist** in `app/Jobs/`
- Event firing mechanism — no code dispatches webhooks when `link.clicked` events occur
- Vue frontend pages for webhook management
- Routes — no web or API routes for webhooks
- HMAC-SHA256 signature verification logic (mentioned in docblock, not implemented)
- Automatic disabling on failure threshold (mentioned in docblock, not implemented)

**Impact:** Enterprise users cannot subscribe to events or receive webhook notifications.

---

### 2. Audit Logs

**What's Done:**
- `audit_logs` migration (`2026_07_29_010457_create_audit_logs_table.php`)
- `AuditLog` Eloquent model with `user()`, `workspace()` relationships
- Database columns: `user_id`, `workspace_id`, `action`, `description`, `ip_address`, `user_agent`
- Mentioned in the 30-day plan (Day 25) as completed

**What's Missing:**
- No service/class that writes audit log entries
- No `AuditLogController` or API endpoints to retrieve logs
- No middleware or observers hooking into critical actions (login, link delete, member invite, role change)
- No Vue frontend for viewing audit trails
- No routes defined

**Impact:** Enterprise compliance and security teams cannot review who did what and when.

---

### 3. SSO (Single Sign-On)

**What's Done:**
- `SocialAuthController` with Google OAuth2 via Laravel Socialite
- `google_id` column on `users` table
- Users can log in with Google; new users auto-created

**What's Missing:**
- **SAML 2.0** support (mentioned in 30-day plan Day 26)
- No SAML package installed (e.g., `lightsaml/lightsaml`, `aacotroneo/laravel-saml2`)
- No SAML metadata endpoints, ACS (Assertion Consumer Service), or SLO (Single Logout)
- No enterprise IdP configuration UI (Azure AD, Okta, Auth0, etc.)
- No SAML attribute mapping (email, name, groups/roles)

**Impact:** Enterprises using Azure AD, Okta, or other SAML IdPs cannot integrate.

---

## Missing Implementation

### 4. QR Code Generation

**What's Done:**
- `qr_codes => true` feature flag in `SubscriptionPlanSeeder` (Enterprise plan)
- Label defined in `Welcome.vue`: `qr_codes: 'QR Code Generation'`

**What's Missing (Everything):**
- No QR code library in `composer.json` (e.g., `endroid/qr-code`, `simplesoftwareio/simple-qrcode`)
- No `QrCodeController` or service class
- No database column or model association for storing QR assets
- No Vue components for QR generation/download
- No API endpoints
- No MinIO/S3 integration for storing generated QR assets (mentioned in 30-day plan Day 12)
- The 30-day plan shows **Day 12 as incomplete** (no ✅ checkmark)

**Impact:** Enterprise users cannot generate QR codes for their short links — a core marketing feature.

---

## 7-Day Implementation Plan

> **Goal:** Close all Enterprise feature gaps within one sprint.

---

### Day 1 — QR Code Generation (Backend Foundation)

**Tasks:**
1. Install `endroid/qr-code` package via Composer.
2. Create `app/Services/QrCodeGeneratorService.php`:
   - Accept a `Link` model
   - Generate QR code with configurable size, margin, and logo overlay
   - Return SVG/PNG binary or S3 URL
3. Add `qr_code_path` nullable column to `links` table (migration).
4. Add `generateQrCode()` method to `Link` model.

**Acceptance Criteria:**
- Service can generate a QR code for any given URL.
- Unit tests for QR generation pass.

---

### Day 2 — QR Code Generation (API + Frontend)

**Tasks:**
1. Create `app/Http/Controllers/QrCodeController.php`:
   - `generate(Link $link)` — generates and stores QR code
   - `show(Link $link)` — returns QR code image or redirect to S3
   - `download(Link $link, string $format)` — PNG/SVG download
2. Add routes to `routes/web.php` (under `CheckSubscriptionPlan:qr_codes` middleware).
3. Create Vue component: `resources/js/Components/QrCodeModal.vue`
   - Display generated QR code
   - Download buttons (PNG/SVG)
   - Copy-to-clipboard for data URI
4. Integrate QR code button into link management UI (`Links/Index.vue`).

**Acceptance Criteria:**
- Enterprise users can click "Generate QR" on any link.
- QR codes are downloadable in PNG and SVG formats.

---

### Day 3 — Audit Logs (Wiring + Service)

**Tasks:**
1. Create `app/Services/AuditLogService.php`:
   - `log(string $action, ?int $userId, ?int $workspaceId, string $description)`
   - Auto-captures `ip_address` and `user_agent` from request
2. Add audit log calls to critical actions:
   - `Login` / `Logout` — `AuthenticatedSessionController`, `SocialAuthController`
   - `Link created` / `updated` / `deleted` — `LinkController`
   - `Workspace member invited` / `role changed` — `WorkspaceMemberController`
   - `Custom domain added` — `CustomDomainController`
   - `API key created` / `revoked` — `ApiKeyController`
3. Create `app/Http/Controllers/AuditLogController.php`:
   - `index()` — paginated list scoped to user's workspaces
   - Filters: action type, date range, user, workspace

**Acceptance Criteria:**
- Every critical action writes an `AuditLog` row.
- Users can view paginated audit trails in the dashboard.

---

### Day 4 — Audit Logs (Frontend + Polish)

**Tasks:**
1. Create `resources/js/Pages/AuditLogs/Index.vue`:
   - Table with columns: Timestamp, User, Action, Description, IP, Workspace
   - Filters: date picker, action dropdown, workspace dropdown
   - Export to CSV button
2. Add "Audit Logs" nav link to `AppLayout.vue` (Enterprise-only, gated by `CheckSubscriptionPlan:audit_logs`).
3. Add feature tests for audit log recording and retrieval.
4. Update `CHANGELOG.md`.

**Acceptance Criteria:**
- Enterprise users see Audit Logs in navigation.
- Filtering and CSV export work.

---

### Day 5 — Webhooks (Backend Infrastructure)

**Tasks:**
1. Create `app/Http/Controllers/WebhookController.php`:
   - `index()`, `store()`, `update()`, `destroy()` — CRUD for webhook subscriptions
   - Validation: `url` (required, URL), `events` (array, enum), `secret` (auto-generated)
2. Create `app/Jobs/DispatchWebhookJob.php`:
   - Accept `Webhook` and event payload
   - POST JSON payload to webhook URL
   - Include `X-Signature-256` header with HMAC-SHA256 signature
   - Handle timeouts, retries (3x with exponential backoff), and failure tracking
   - Disable webhook if `failure_count` exceeds threshold (configurable, default 10)
3. Fire webhooks from existing event sources:
   - `link.clicked` — inside `ProcessClickTracking` job or `RedirectController`
   - `link.created`, `link.deleted` — `LinkController`
   - `bulk.completed` — `ProcessBulkShortening` job
4. Add routes to `routes/web.php` (gated by `CheckSubscriptionPlan:webhooks`).

**Acceptance Criteria:**
- Webhooks deliver signed payloads.
- Failed deliveries retry 3x, then disable the webhook.
- Feature tests for dispatch, signature verification, and failure handling pass.

---

### Day 6 — Webhooks (Frontend + Management UI)

**Tasks:**
1. Create `resources/js/Pages/Webhooks/Index.vue`:
   - List of webhook subscriptions with status (active/inactive)
   - Create webhook modal: URL input, event multi-select, auto-generated secret display
   - Edit webhook modal
   - Test webhook button (sends a ping event)
   - Delivery history / failure count display
2. Create `resources/js/Pages/Webhooks/Form.vue` (shared create/edit form).
3. Add "Webhooks" nav link to `AppLayout.vue` (Enterprise-only).
4. Add API resource routes for webhooks.

**Acceptance Criteria:**
- Users can create, edit, test, and delete webhooks.
- Event selection is restricted to supported types.
- UI shows webhook health status.

---

### Day 7 — SAML 2.0 SSO (Enterprise Identity Providers)

**Tasks:**
1. Install SAML package (e.g., `aacotroneo/laravel-saml2` or `lightsaml/sp`).
2. Create `app/Http/Controllers/Auth/SamlAuthController.php`:
   - `metadata()` — returns SP metadata XML
   - `acs()` — Assertion Consumer Service endpoint
   - `sls()` — Single Logout Service endpoint
   - `login()` — initiates SAML authentication
3. Create `app/Models/SamlIdentityProvider.php` (optional, for multi-tenant IdP support):
   - `entity_id`, `sso_url`, `slo_url`, `x509_cert`, `workspace_id`
4. Add database migration for `saml_identity_providers` table.
5. Map SAML attributes to User fields (email, name).
6. Add SAML configuration UI for workspace admins (Enterprise-only):
   - Upload IdP metadata XML or manual entry
   - Copy SP metadata URL/ACS URL
7. Add routes: `/saml/{provider}/login`, `/saml/{provider}/acs`, `/saml/{provider}/metadata`.

**Acceptance Criteria:**
- SAML login flow works with a test IdP (e.g., OneLogin, Okta test instance).
- SP metadata is valid and consumable by enterprise IdPs.
- Users created via SAML have `password = null` (same pattern as Google OAuth).

---

## Post-Implementation Checklist

- [ ] All 4 gaps closed (QR Codes, Audit Logs, Webhooks, SAML)
- [ ] Feature flags in `SubscriptionPlanSeeder` verified against actual gates
- [ ] `CheckSubscriptionPlan` middleware applied to all new Enterprise routes
- [ ] Unit + feature tests written for all new code (>80% coverage target)
- [ ] `CHANGELOG.md` updated with Day 1–7 entries
- [ ] `docs/openapi.yaml` updated with new API endpoints
- [ ] `README.md` updated with SAML configuration instructions
- [ ] End-to-end testing on staging environment
- [ ] Load test webhook dispatch at scale (1,000 concurrent events)

---

## Appendix: Files to Create/Modify

### New Files
```
app/Services/QrCodeGeneratorService.php
app/Services/AuditLogService.php
app/Http/Controllers/QrCodeController.php
app/Http/Controllers/AuditLogController.php
app/Http/Controllers/WebhookController.php
app/Http/Controllers/Auth/SamlAuthController.php
app/Jobs/DispatchWebhookJob.php
app/Models/SamlIdentityProvider.php
resources/js/Pages/AuditLogs/Index.vue
resources/js/Pages/Webhooks/Index.vue
resources/js/Pages/Webhooks/Form.vue
resources/js/Components/QrCodeModal.vue
database/migrations/2026_08_03_000001_add_qr_code_path_to_links.php
database/migrations/2026_08_03_000002_create_saml_identity_providers_table.php
```

### Modified Files
```
composer.json
routes/web.php
routes/api.php
app/Models/Link.php
app/Models/User.php
resources/js/Layouts/AppLayout.vue
resources/js/Pages/Links/Index.vue
database/seeders/SubscriptionPlanSeeder.php
app/Http/Controllers/LinkController.php
app/Http/Controllers/WorkspaceMemberController.php
app/Http/Controllers/ApiKeyController.php
app/Http/Controllers/CustomDomainController.php
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Auth/SocialAuthController.php
docs/openapi.yaml
CHANGELOG.md
README.md
```
