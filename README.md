# NodeWatch

Self-hosted uptime monitoring service with distributed probes. Checks website and API availability from multiple regions (EU, US, Asia) and alerts on incidents.

> Portfolio project demonstrating high-load architecture for Senior PHP/Go Backend positions.

## Tech Stack

| Layer | Technology |
|-------|------------|
| API | Laravel 13, PHP 8.4 |
| Internal Queue | Kafka (Redpanda) |
| CF Bridge | Go (Redpanda → Cloudflare Queues) |
| Probe Workers | Cloudflare Workers (TypeScript) |
| Primary DB | MySQL 8.4 |
| Metrics DB | ClickHouse 25.3 |
| Cache / Sessions | Redis 7.4 |
| Observability | Prometheus + Grafana |
| Frontend | Nuxt 4, Tailwind CSS |
| Infra | Docker Compose → Kubernetes |

## Architecture

```
Nuxt / Swagger UI
       │
       ▼
Laravel 13 API
  • Sanctum auth    • Monitor CRUD
  • Kafka producer  • POST /api/internal/probe-result
       │
  ┌────┴────┐  ┌──────────┐  ┌───────┐  ┌───────────┐
  │  MySQL  │  │  MySQL   │  │ Redis │  │ClickHouse │
  │ (write) │  │ (replica)│  │       │  │  metrics  │
  └─────────┘  └──────────┘  └───────┘  └───────────┘

              Kafka (Redpanda)
                    │
              Go CF Bridge
          ┌─────────┼─────────┐
          ▼         ▼         ▼
     CF Queue   CF Queue   CF Queue
      eu-west   us-east   ap-south
          │         │         │
          ▼         ▼         ▼
     CF Worker  CF Worker  CF Worker
          └─────────┼─────────┘
                    │ POST /api/internal/probe-result
                    ▼
               Laravel API
```

## Probe Cycle

End-to-end flow from scheduler to result:

```
1. Laravel Scheduler → php artisan monitor:push-active
         │
         │  JSON: {monitor_id, url, method, timeout, expected_status}
         ▼
2. Kafka topics: monitor.eu-west / monitor.us-east / monitor.ap-south
         │
         │  kafka-go consumer (SASL/TLS)
         ▼
3. Go CF Bridge — reads all 3 topics, pushes to CF Queue HTTP API
         │
         │  POST api.cloudflare.com/.../queues/{id}/messages/batch
         ▼
4. Cloudflare Queue — stores messages, triggers worker automatically (push)
         │
         │  push-based, no polling
         ▼
5. CF Worker — fetches URL, measures response time + TTFB
         │
         │  POST /api/internal/probe-result  (X-Internal-Token)
         ▼
6. Laravel ProbeResultController — updates monitor.last_status in MySQL
```

> **Note on regions:** Cloudflare Queues and Workers are globally distributed — there is no free-tier option to pin a worker to a specific geographic region. Guaranteed regional execution requires Cloudflare's Enterprise **Regional Dispatch** feature. The three queues (eu-west, us-east, ap-south) partition probe workload and demonstrate the intent of regional monitoring; actual execution location is determined by Cloudflare's edge routing.

## Repository Structure

```
node-watch/
├── api/          # Laravel 13 API
├── bridge/       # Go CF Bridge (Redpanda → Cloudflare Queues)
├── probes/       # Cloudflare Workers (TypeScript, Wrangler)
├── web/          # Nuxt 4 frontend
├── monitoring/   # Prometheus + Grafana
├── docker/
├── k8s/
└── docker-compose.yml
```

## Getting Started

### Prerequisites

- Docker + Docker Compose
- PHP 8.4 + Composer
- Go 1.23+
- Node.js 20+ + npm
- librdkafka (for Kafka PHP extension)
- Wrangler CLI (`npm install -g wrangler`)
- A [Redpanda Cloud](https://redpanda.com/cloud) account (free tier)
- A [Cloudflare](https://cloudflare.com) account (free tier)

### 1. Infrastructure

```bash
cp .env.example .env
docker-compose up -d   # MySQL + ClickHouse + Redis
```

### 2. Laravel API

```bash
cp api/.env.example api/.env
# Fill in: DB_*, REDIS_*, KAFKA_BROKERS, KAFKA_SASL_*, INTERNAL_TOKEN

cd api
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

API: `http://localhost:8000`  
Swagger UI: `http://localhost:8000/api/documentation`

#### Installing rdkafka (WSL / Linux)

```bash
sudo apt update && sudo apt install -y librdkafka-dev
sudo pecl install rdkafka
echo "extension=rdkafka.so" | sudo tee /etc/php/8.4/cli/conf.d/20-rdkafka.ini
```

### 3. Cloudflare Setup

Create three queues in [Cloudflare Dashboard](https://dash.cloudflare.com) → **Workers & Pages → Queues**:

| Queue name | Region label |
|---|---|
| `node-watch-eu-west` | EU West |
| `node-watch-us-east` | US East |
| `node-watch-ap-south` | Asia South |

Create an API token: **My Profile → API Tokens → Create Token → Edit Cloudflare Workers** template.

### 4. CF Workers — deploy

```bash
cp probes/.env.example probes/.env
# Fill in: CLOUDFLARE_API_TOKEN

cd probes
npm install

# Set secrets (run once, update anytime with the same command)
echo "<your-api-url>" | npx wrangler secret put API_URL --env eu
echo "<your-api-url>" | npx wrangler secret put API_URL --env us
echo "<your-api-url>" | npx wrangler secret put API_URL --env asia
echo "<your-internal-token>" | npx wrangler secret put INTERNAL_TOKEN --env eu
echo "<your-internal-token>" | npx wrangler secret put INTERNAL_TOKEN --env us
echo "<your-internal-token>" | npx wrangler secret put INTERNAL_TOKEN --env asia

# Deploy all 3 workers
npm run deploy:all
```

Workers will automatically bind to their respective queues as consumers.

### 5. Go CF Bridge — run locally

```bash
cp bridge/.env.example bridge/.env
# Fill in: KAFKA_BROKERS, KAFKA_SASL_*, CF_ACCOUNT_ID, CF_API_TOKEN, CF_QUEUE_ID_*

cd bridge
go run ./cmd/bridge
```

#### Deploy to production (Docker)

```bash
cd bridge
docker build -t node-watch-bridge .
docker run --env-file .env node-watch-bridge
```

Or add to `docker-compose.yml` alongside Redpanda.

### 6. Running the probe cycle

With API, Bridge, and Workers all running:

```bash
# Push active monitors to Kafka → triggers full probe cycle
cd api && php artisan monitor:push-active

# Or let the scheduler handle it automatically:
php artisan schedule:run
```

## API Reference

Full interactive docs at `http://localhost:8000/api/documentation` (Swagger UI).

### Auth

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register |
| POST | `/api/auth/login` | Login → Bearer token |
| GET | `/api/auth/me` | Current user |
| POST | `/api/auth/logout` | Revoke token |

### Monitors

All endpoints require `Authorization: Bearer <token>`.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/monitor` | List monitors (paginated) |
| POST | `/api/monitor` | Create monitor |
| GET | `/api/monitor/{id}` | Get monitor |
| PUT | `/api/monitor/{id}` | Update monitor |
| DELETE | `/api/monitor/{id}` | Delete monitor |

### Internal (probe workers only)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/internal/probe-result` | `X-Internal-Token` | Submit probe result |

## Development

```bash
# Tests
cd api && php artisan test

# Static analysis (PHPStan level 6)
cd api && ./vendor/bin/phpstan analyse

# Code style (Pint / PSR-12)
cd api && ./vendor/bin/pint

# Nuxt frontend
cd web && npm install && npm run dev
```

## Roadmap

- [x] **Phase 1** — Laravel API: auth, monitor CRUD, Swagger, tests
- [x] **Phase 1** — Kafka producer: scheduler dispatches probe tasks per region
- [x] **Phase 1** — POST /api/internal/probe-result with internal token auth
- [x] **Phase 1** — Go CF Bridge (Redpanda → Cloudflare Queues, all 3 regions)
- [x] **Phase 1** — CF Workers: probe execution + result delivery (all 3 regions)
- [ ] **Phase 1** — ClickHouse write on probe result
- [ ] **Phase 1** — Basic Nuxt dashboard
- [ ] **Phase 2** — Incidents, alerts (Email/Telegram), Prometheus, Grafana
- [ ] **Phase 2** — MySQL read replica + Redis cache
- [ ] **Phase 3** — ClickHouse Distributed (2 shards), region comparison UI
- [ ] **Phase 4** — Kubernetes, rate limiting, load testing, CI/CD
