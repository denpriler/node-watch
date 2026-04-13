# CLAUDE.md - NodeWatch

## Project Overview

**NodeWatch** — self-hosted uptime monitoring service with distributed probes for checking website/API availability from multiple regions.

**Purpose**: Portfolio project demonstrating high-load architecture skills for Senior PHP/Go Backend positions with relocation.

## Tech Stack

| Component | Technology |
|-----------|------------|
| **API** | Laravel 13, PHP 8.4 |
| **Probe Worker (eu-west)** | Go 1.24 (local/VPS) |
| **Probe Workers (us-east, ap-south)** | Cloudflare Workers |
| **CF Task Queue** | Cloudflare Queues |
| **Internal Queue** | Kafka (Redpanda) |
| **CF Bridge** | Go service (Redpanda ↔ CF Queues) |
| **Primary DB** | MySQL 8.4 + read replica |
| **Metrics DB** | ClickHouse 25.3 (Distributed sharding) |
| **Cache** | Redis 7.4 |
| **Observability** | Prometheus + Grafana |
| **Frontend** | Nuxt 3, Tailwind CSS |
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
│  • Auth (Sanctum)          • Kafka consumer → ClickHouse + incidents         │
│  • Monitor CRUD            • POST /api/internal/probe-result (CF results)    │
│  • Kafka producer          • Prometheus /metrics                             │
└───┬─────────────┬──────────────────┬────────────────────┬────────────────────┘
    │             │                  │                    │
    ▼             ▼                  ▼                    ▼
┌───────┐  ┌──────────┐  ┌─────────────────┐     ┌──────────────┐
│ MySQL │  │  MySQL   │  │     Redis       │     │  ClickHouse  │
│(write)│  │(replica) │  │ • cache         │     │  Distributed │
└───────┘  └──────────┘  │ • sessions      │     │  (sharded by │
                         │ • rate limit    │     │  monitor_id) │
                         └─────────────────┘     └──────────────┘

                    Kafka (Redpanda) — internal only
                           │
          ┌────────────────┴──────────────────┐
          │                                   │
          ▼                                   ▼
   Go Worker                          Go CF Bridge
   region: eu-west                    (Redpanda ↔ CF Queues)
   (probe + result)                          │
                                    ┌────────┴────────┐
                                    ▼                 ▼
                            CF Queue US         CF Queue Asia
                            (us-east)           (ap-south)
                                    │                 │
                                    ▼                 ▼
                            CF Worker US        CF Worker Asia
                            (Cron/Push)         (Cron/Push)
                                    │                 │
                                    └────────┬────────┘
                                             │ POST /api/internal/probe-result
                                             ▼
                                        Laravel API
```

### Cloudflare Workers Flow (push-based, no polling)

```
1. Laravel Scheduler → Kafka monitor.check.us-east
2. Go CF Bridge reads Kafka → pushes tasks to CF Queue US via CF API
3. CF Queue triggers CF Worker US automatically (push, not cron poll)
4. CF Worker probes URL, measures response time
5. CF Worker POST /api/internal/probe-result → Laravel API
6. Laravel writes ClickHouse + updates monitor status
```

Go CF Bridge runs locally/VPS alongside Redpanda. It's a simple Kafka consumer + CF Queues producer.

## Repository Structure

```
node-watch/
├── api/                        # Laravel 13, PHP 8.4
│   ├── app/
│   │   ├── Http/Controllers/   # AuthController, MonitorController
│   │   ├── Http/Requests/      # Auth/, Monitor/
│   │   ├── Http/Resources/     # Auth/UserResource, MonitorResource
│   │   ├── Models/             # User, Monitor
│   │   ├── Services/           # AuthService, MonitorService
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
├── worker/                     # Go probe worker (eu-west)
│   ├── cmd/worker/
│   └── internal/
├── bridge/                     # Go CF Bridge (Redpanda ↔ CF Queues)
│   ├── cmd/bridge/
│   └── internal/
├── probes/                     # Cloudflare Workers
│   ├── worker-us/              # us-east probe
│   ├── worker-asia/            # ap-south probe
│   └── wrangler.toml
├── web/                        # Nuxt 3
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

## Kafka Topics

| Topic | Producer | Consumer |
|-------|----------|----------|
| `monitor.check.eu-west` | Laravel Scheduler | Go Worker |
| `monitor.check.us-east` | Laravel Scheduler | Go CF Bridge → CF Queue US |
| `monitor.check.ap-south` | Laravel Scheduler | Go CF Bridge → CF Queue Asia |
| `monitor.result` | Go Worker | Laravel Consumer |

CF Workers post results directly to Laravel API (not via Kafka).

## Development Phases

### Phase 1: MVP
- [x] Project setup: Laravel 13 + Docker
- [x] Models & migrations (users, monitors)
- [x] Auth API (Sanctum) — register, login, me, logout
- [x] Monitor CRUD API with ownership policy
- [x] Swagger UI (l5-swagger, PHP 8 attributes)
- [x] Feature tests: AuthController (14), MonitorController (23)
- [ ] Kafka producer: Laravel Scheduler → monitor.check.*
- [ ] Go Worker (eu-west): consume → probe → monitor.result
- [ ] Laravel consumer: monitor.result → ClickHouse + MySQL update
- [ ] Basic Nuxt dashboard

### Phase 2: Alerts & Observability
- [ ] Incident detection in consumer (down/up transitions)
- [ ] Alert notifications (Email, Telegram)
- [ ] MySQL read replica + Laravel DB routing
- [ ] Redis cache for monitor lists
- [ ] Prometheus metrics endpoint
- [ ] Grafana dashboard

### Phase 3: Distributed Probes
- [ ] Go CF Bridge (Redpanda ↔ CF Queues)
- [ ] CF Worker us-east (Cloudflare Workers + Queues)
- [ ] CF Worker ap-south
- [ ] POST /api/internal/probe-result (auth TBD)
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
- `laravel/sanctum` — token + cookie SPA auth
- `darkaonline/l5-swagger` — Swagger UI via PHP 8 attributes
- `junges/laravel-kafka` — Kafka producer/consumer (requires rdkafka ext)
- `laravel/pint` — code style (PSR-12)
- `phpstan/phpstan` + `larastan/larastan` — static analysis level 6
- `phpunit/phpunit` — testing

### Go (worker + bridge)
- `github.com/segmentio/kafka-go` — Kafka client
- `github.com/go-resty/resty/v2` — HTTP probe client

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

# Go worker (eu-west)
cd worker && go run cmd/worker/main.go

# Go CF Bridge
cd bridge && go run cmd/bridge/main.go

# Nuxt frontend
cd web && npm install && npm run dev
```

## Installing rdkafka (WSL/Linux)

```bash
sudo apt update && sudo apt install -y librdkafka-dev
sudo pecl install rdkafka
echo "extension=rdkafka.so" | sudo tee /etc/php/8.4/cli/conf.d/20-rdkafka.ini
php -m | grep rdkafka
```

## Coding Standards

### PHP / Laravel
- PSR-12 via Pint
- `declare(strict_types=1)` in services and commands
- Form Requests for all validation
- API Resources for all responses
- Services for business logic (no business logic in controllers)
- Policies for authorization
- PHPStan level 6, zero errors
- URL changes in monitors intentionally blocked (planned premium feature)

### Go
- Standard project layout (`cmd/`, `internal/`)
- `internal/` for private packages
- Table-driven tests

### TypeScript / Nuxt
- Composition API only
- TypeScript strict mode

## Notes for Claude

- Developer knows Laravel/PHP and Vue well
- New to: Go, Kafka, Kubernetes — explain concepts when relevant
- Use rdkafka PHP extension (not HTTP Kafka proxy)
- Production-ready patterns — portfolio project, code quality matters
- `*Request` suffix = HTTP form request (validation), `DTO` = inter-service transfer object
- Pagination handled in controller via `->paginate()`, not in service
- CF Workers receive tasks via CF Queues (push-based), not by polling Laravel API
- MySQL sharding NOT planned — read replica + Redis cache is the right approach for monitors table
- ClickHouse sharding (Distributed engine) IS planned for monitor_logs (Phase 3)
