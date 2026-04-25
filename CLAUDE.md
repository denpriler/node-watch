# CLAUDE.md - NodeWatch

## Project Overview

**NodeWatch** — self-hosted uptime monitoring service with distributed probes for checking website/API availability from multiple regions.

**Purpose**: Portfolio project demonstrating high-load architecture skills for Senior PHP Backend positions with relocation.

## Tech Stack

| Component | Technology |
|-----------|------------|
| **API** | Laravel 13, PHP 8.4 |
| **Probe Workers (all regions)** | Cloudflare Workers |
| **CF Task Queues** | Cloudflare Queues (eu-west, us-east, ap-south) |
| **Primary DB** | MySQL 8.4 + read replica |
| **Metrics DB** | ClickHouse 25.3 (Distributed sharding) |
| **Cache** | Redis 7.4 |
| **Observability** | Prometheus + Grafana |
| **Frontend** | Nuxt 4, Tailwind CSS |
| **Infra** | Docker Compose → Kubernetes |

## Architecture

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                               CLIENTS                                        │
│                          Nuxt UI / Swagger                                   │
└──────────────────────────────────┬───────────────────────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                            LARAVEL 13 API                                    │
│  • Auth (Sanctum)          • POST /api/internal/probe-result (CF results)    │
│  • Monitor CRUD            • CloudflareQueueService → CF Queues (3 regions)  │
│  • Scheduler               • Prometheus /metrics                             │
└───┬─────────────┬──────────────────┬────────────────────┬────────────────────┘
    │             │                  │                    │
    ▼             ▼                  ▼                    ▼
┌───────┐  ┌──────────┐  ┌─────────────────┐     ┌──────────────┐
│ MySQL │  │  MySQL   │  │     Redis       │     │  ClickHouse  │
│(write)│  │(replica) │  │ • cache         │     │  Distributed │
└───────┘  └──────────┘  │ • sessions      │     │  (sharded by │
                         │ • rate limit    │     │  monitor_id) │
                         └─────────────────┘     └──────────────┘

          ┌────────────────┬────────────────┐
          ▼                ▼                ▼
    CF Queue EU      CF Queue US      CF Queue Asia
    (eu-west)        (us-east)        (ap-south)
          │                │                │
          ▼                ▼                ▼
    CF Worker EU     CF Worker US     CF Worker Asia
                           │
                           │ POST /api/internal/probe-result
                           ▼
                      Laravel API
```

### Cloudflare Workers Flow (push-based, no polling)

```
1. Laravel Scheduler → CloudflareQueueService → CF Queues (eu-west / us-east / ap-south)
2. CF Queue triggers CF Worker automatically (push, not cron poll)
3. CF Worker probes URL, measures TTFB + response time
4. CF Worker POST /api/internal/probe-result → Laravel API
5. Laravel writes ClickHouse + updates monitor status
```

## Repository Structure

```
node-watch/
├── api/                        # Laravel 13, PHP 8.4
│   ├── app/
│   │   ├── Http/Controllers/   # AuthController, MonitorController, ProbeResultController
│   │   ├── Http/Requests/      # Auth/, Monitor/
│   │   ├── Http/Resources/     # Auth/UserResource, MonitorResource
│   │   ├── Models/             # User, Monitor
│   │   ├── Services/           # AuthService, MonitorService, CloudflareQueueService, ClickHouseService
│   │   ├── Policies/           # MonitorPolicy
│   │   ├── Enums/Monitor/      # MonitorMethod, MonitorRegion, MonitorStatus
│   │   └── OpenApi/            # OpenApiSpec.php (Swagger base)
│   ├── database/
│   │   ├── migrations/
│   │   └── factories/          # UserFactory, MonitorFactory
│   ├── tests/Feature/
│   │   ├── Auth/               # AuthControllerTest (14 tests)
│   │   └── Monitor/            # MonitorControllerTest (23 tests)
│   ├── routes/
│   │   ├── api.php
│   │   └── api/auth.php
│   └── storage/api-docs/       # Generated Swagger JSON
├── probes/                     # Cloudflare Workers (all 3 regions)
│   ├── worker-eu/              # eu-west probe
│   ├── worker-us/              # us-east probe
│   ├── worker-asia/            # ap-south probe
│   └── wrangler.toml
├── web/                        # Nuxt 4
├── monitoring/                 # Prometheus + Grafana
├── docker/
├── k8s/
├── docker-compose.yml
└── .env.example
```

## Database Design

### MySQL (operational data)

```sql
users         — id, email, password, email_verified_at, telegram_chat_id
monitors      — id, user_id, name, url, method, check_interval, timeout,
                expected_status, regions (JSON), is_active,
                last_checked_at, last_status (0=pending,1=up,2=down)
notification_channels — id, user_id, type, config (JSON), is_active
incidents     — id, monitor_id, started_at, ended_at, cause
alert_logs    — id, incident_id, channel_id, sent_at, status, error
```

Read replica: all SELECT queries route to replica via Laravel DB connection config.
Redis: caches user monitor lists, rate limiting counters.

### ClickHouse (time-series metrics, Distributed)

```sql
-- Local table on each shard node
CREATE TABLE monitor_logs_local (
    monitor_id UInt64,
    checked_at DateTime,
    region LowCardinality(String),
    status_code UInt16,
    response_time_ms UInt32,
    ttfb_ms UInt32,
    is_up UInt8,
    error Nullable(String),
    cert_expires_at Nullable(DateTime)
) ENGINE = MergeTree
PARTITION BY toYYYYMM(checked_at)
ORDER BY (monitor_id, checked_at)
TTL checked_at + INTERVAL 90 DAY;

-- Distributed proxy (queries all shards transparently)
CREATE TABLE monitor_logs AS monitor_logs_local
ENGINE = Distributed('nodewatch_cluster', default, monitor_logs_local, monitor_id);
```

Sharding key: `monitor_id` — evenly distributes write load, range queries by monitor stay local to one shard.

## Development Phases

### Phase 1: MVP
- [x] Project setup: Laravel 13 + Docker
- [x] Models & migrations (users, monitors)
- [x] Auth API (Sanctum) — register, login, me, logout
- [x] Monitor CRUD API with ownership policy
- [x] Swagger UI (l5-swagger, PHP 8 attributes)
- [x] Feature tests: AuthController (14), MonitorController (23)
- [x] Laravel Scheduler → CloudflareQueueService → CF Queues (3 regions, batch push)
- [x] POST /api/internal/probe-result — ProbeResultController + VerifyInternalToken middleware
- [x] CF Worker eu-west + us-east + ap-south (probe → POST /api/internal/probe-result)
- [ ] ClickHouse write on probe result (ProbeResultController → monitor_logs)
- [ ] Basic Nuxt dashboard

### Phase 2: Alerts & Observability
- [ ] Incident detection (down/up transitions)
- [ ] Alert notifications (Email, Telegram)
- [ ] MySQL read replica + Laravel DB routing
- [ ] Redis cache for monitor lists
- [ ] Prometheus metrics endpoint
- [ ] Grafana dashboard

### Phase 3: Distributed Probes
- [ ] ClickHouse Distributed (2 shards in docker-compose)
- [ ] Region comparison UI in Nuxt

### Phase 4: Production Ready
- [ ] Kubernetes manifests
- [ ] Rate limiting (Redis)
- [ ] Load testing (k6)
- [ ] Public status pages
- [ ] CI/CD GitHub Actions

## Key Packages

### Laravel API
- `laravel/sanctum` — cookie SPA auth (session-based, `statefulApi()`)
- `darkaonline/l5-swagger` — Swagger UI via PHP 8 attributes
- `spatie/laravel-data` — DTOs with serialization + TypeScript generation
- `spatie/laravel-typescript-transformer` — generates `web/app/types/api.d.ts` via `composer ts-types`
- `laravel/pint` — code style (PSR-12)
- `phpstan/phpstan` + `larastan/larastan` — static analysis level 6
- `phpunit/phpunit` — testing

### Cloudflare Workers
- Wrangler CLI
- Cloudflare Queues binding

## Development Commands

```bash
# Infrastructure
docker-compose up -d

# Laravel API
cd api && composer install && php artisan migrate && php artisan serve
# Swagger UI: http://localhost:8000/api/documentation

# Tests
cd api && php artisan test

# Static analysis + code style
cd api && ./vendor/bin/phpstan analyse && ./vendor/bin/pint

# Nuxt frontend
cd web && npm install && npm run dev
```

## Coding Standards

### PHP / Laravel
- PSR-12 via Pint
- `declare(strict_types=1)` in services and commands
- Form Requests for all validation
- API Resources delegate to `Data` DTOs via `->toArray()` (for TypeScript generation)
- `JsonResource::withoutWrapping()` active — single resources return flat JSON, paginated collections still have `data`+`meta` from paginator
- Auth: cookie/session mode — `Auth::attempt()` sets session, logout invalidates session (no API tokens)
- Enums: always use enum types in DTOs/models; no `toStringValue()` — use `->value` directly
- DTO naming: use model name without suffix (e.g. `Monitor`, not `MonitorData`) in `app/DTO/`
- Services for business logic (no business logic in controllers)
- Policies for authorization
- PHPStan level 6, zero errors
- URL changes in monitors intentionally blocked (planned premium feature)

### TypeScript / Nuxt
- Composition API only
- TypeScript strict mode

## Notes for Claude

- Developer knows Laravel/PHP and Vue well
- New to: Kubernetes — explain concepts when relevant
- Production-ready patterns — portfolio project, code quality matters
- `*Request` suffix = HTTP form request (validation), `DTO` = response/transfer object (named after model, e.g. `Monitor`)
- Pagination handled in controller via `->paginate()`, not in service
- CF Workers receive tasks via CF Queues (push-based), not by polling Laravel API
- MySQL sharding NOT planned — read replica + Redis cache is the right approach for monitors table
- ClickHouse sharding (Distributed engine) IS planned for monitor_logs (Phase 3)
- TypeScript types: `composer ts-types` → generates `web/app/types/api.d.ts` (enums + DTOs); paginated types via `Illuminate.LengthAwarePaginator<never, T>`
