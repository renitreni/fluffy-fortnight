# Entity Relationship Diagram

This document describes the full database schema for the URL Shortener platform.
Generated: **Day 2** — Database Design, Migrations & Indexing Strategy.

---

## ERD (Mermaid)

```mermaid
erDiagram
    subscription_plans {
        bigint id PK
        varchar name UK
        varchar display_name
        json features
        decimal price_monthly
        decimal price_yearly
        varchar stripe_monthly_price_id
        varchar stripe_yearly_price_id
        int max_links
        smallint max_workspaces
        smallint max_custom_domains
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    users {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at
        varchar password
        varchar avatar
        varchar timezone
        varchar locale
        boolean is_active
        bigint subscription_plan_id FK
        varchar remember_token
        timestamp created_at
        timestamp updated_at
    }

    workspaces {
        bigint id PK
        bigint owner_id FK
        varchar name
        varchar slug UK
        varchar logo
        smallint custom_domain_limit
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    workspace_user {
        bigint id PK
        bigint workspace_id FK
        bigint user_id FK
        enum role
        timestamp joined_at
        timestamp created_at
        timestamp updated_at
    }

    custom_domains {
        bigint id PK
        bigint workspace_id FK
        bigint user_id FK
        varchar domain UK
        boolean is_verified
        timestamp verified_at
        varchar verification_token
        enum ssl_status
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    links {
        bigint id PK
        bigint user_id FK
        bigint workspace_id FK
        bigint custom_domain_id FK
        varchar short_code UK
        text original_url
        varchar title
        text description
        boolean is_custom_alias
        varchar password
        timestamp expires_at
        varchar ios_deep_link
        varchar android_deep_link
        varchar utm_source
        varchar utm_medium
        varchar utm_campaign
        varchar utm_term
        varchar utm_content
        bigint click_count
        boolean is_active
        json tags
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    clicks {
        bigint id PK
        bigint link_id IDX
        varchar ip_hash
        char country
        varchar region
        varchar city
        decimal latitude
        decimal longitude
        enum device_type
        varchar os
        varchar browser
        varchar referer
        varchar referer_domain
        text user_agent
        timestamp clicked_at
    }

    api_keys {
        bigint id PK
        bigint user_id FK
        bigint workspace_id FK
        varchar name
        varchar key_hash UK
        varchar key_prefix
        json abilities
        timestamp last_used_at
        timestamp expires_at
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    webhooks {
        bigint id PK
        bigint user_id FK
        bigint workspace_id FK
        varchar url
        json events
        varchar secret
        boolean is_active
        timestamp last_triggered_at
        smallint failure_count
        timestamp created_at
        timestamp updated_at
    }

    blocked_urls {
        bigint id PK
        varchar url_hash UK
        text url
        enum reason
        varchar source
        bigint blocked_by FK
        timestamp created_at
        timestamp updated_at
    }

    %% Relationships
    subscription_plans ||--o{ users : "subscribed_to"
    users ||--o{ workspaces : "owns"
    users ||--o{ workspace_user : "member_of"
    workspaces ||--o{ workspace_user : "has_member"
    users ||--o{ custom_domains : "registered_by"
    workspaces ||--o{ custom_domains : "belongs_to"
    users ||--o{ links : "created_by"
    workspaces ||--o{ links : "scoped_to"
    custom_domains ||--o{ links : "hosted_on"
    links ||--o{ clicks : "recorded_for"
    users ||--o{ api_keys : "owns"
    workspaces ||--o{ api_keys : "scoped_to"
    users ||--o{ webhooks : "owns"
    workspaces ||--o{ webhooks : "scoped_to"
    users ||--o{ blocked_urls : "blocked_by"
```

---

## Index Summary

| Table | Index Name | Columns | Type |
|-------|-----------|---------|------|
| `users` | `users_email_unique` | `email` | UNIQUE |
| `subscription_plans` | `subscription_plans_name_unique` | `name` | UNIQUE |
| `workspaces` | `workspaces_slug_unique` | `slug` | UNIQUE |
| `workspace_user` | `workspace_user_workspace_id_user_id_unique` | `workspace_id, user_id` | UNIQUE |
| `custom_domains` | `custom_domains_domain_unique` | `domain` | UNIQUE |
| `links` | `links_short_code_unique` | `short_code` | UNIQUE |
| `links` | `links_user_id_foreign` | `user_id` | INDEX |
| `links` | `links_workspace_id_foreign` | `workspace_id` | INDEX |
| `links` | `links_custom_domain_id_foreign` | `custom_domain_id` | INDEX |
| `links` | `links_active_expiry_index` | `is_active, expires_at` | INDEX (composite) |
| `links` | `links_created_at_index` | `created_at` | INDEX |
| `clicks` | `clicks_link_id_index` | `link_id` | INDEX |
| `clicks` | `clicks_link_time_index` | `link_id, clicked_at` | INDEX (composite) |
| `clicks` | `clicks_country_index` | `country` | INDEX |
| `clicks` | `clicks_device_index` | `device_type` | INDEX |
| `clicks` | `clicks_referer_domain_index` | `referer_domain` | INDEX |
| `clicks` | `clicks_time_index` | `clicked_at` | INDEX |
| `api_keys` | `api_keys_key_hash_unique` | `key_hash` | UNIQUE |
| `blocked_urls` | `blocked_urls_url_hash_unique` | `url_hash` | UNIQUE |

---

## Design Decisions

### GDPR / Privacy
- IP addresses are **never stored in plain text**. The redirect controller hashes the raw IP with SHA-256 before dispatching the tracking job.
- The `ip_hash` column enables de-duplication analysis without storing PII.
- A future data-retention job will hard-delete clicks older than the configured retention window.

### Write Throughput on `clicks`
- There is **no FK constraint** on `clicks.link_id` at the database level. This avoids the shared-lock overhead on the parent `links` row during every insert, which matters at high redirect volumes.
- Referential integrity is enforced at the application layer (the tracking job only runs if `link_id` is valid).

### Denormalized `click_count`
- `links.click_count` is a cached counter kept in sync by the `IncrementLinkClickCount` job.
- It avoids a `COUNT(*)` query on the `clicks` table for every dashboard row render.
- Periodic reconciliation (Day 19 aggregation job) ensures the counter is always accurate.

### `clicks` — No `updated_at`, No `softDeletes`
- Rows are append-only and immutable, so `updated_at` is meaningless.
- Rows are permanently deleted (not soft-deleted) when the data retention policy triggers.

### Subscription on `users` Table
- `users.subscription_plan_id` stores the current plan directly for simplicity.
- A dedicated `subscriptions` table (Stripe-style with start/end dates, invoice history) will be added in **Day 28** when Stripe billing is integrated.
