# NodeWatch

Self-hosted uptime monitoring service with distributed probes. Checks website and API availability from multiple regions (EU, US, Asia) and alerts on incidents.

> Portfolio project demonstrating high-load architecture for Senior PHP/Go Backend positions.

## Tech Stack

| Layer | Technology |
|-------|------------|
| API | Laravel 13, PHP 8.4 |
| Probe Workers | Cloudflare Workers (all regions) |
| Message Queue | Kafka (Redpanda) |
| Primary DB | MySQL 8.4 |
| Metrics DB | ClickHouse 25.3 |
| Cache / Sessions | Redis 7.4 |
| Distributed Probes | Cloudflare Workers |
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

## Getting Started

### Prerequisites

- Docker + Docker Compose
- PHP 8.4 + Composer (for local API dev)
- Go 1.24 (for local worker dev)
- librdkafka (for Kafka PHP extension)

### Setup

```bash
# 1. Clone and configure
git clone https://github.com/your-username/node-watch.git
cd node-watch
cp .env.example .env
cp api/.env.example api/.env

# 2. Start infrastructure
docker-compose up -d

# 3. Setup API
cd api
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

API available at `http://localhost:8000`
Swagger UI at `http://localhost:8000/api/documentation`

### Installing rdkafka (WSL / Linux)

```bash
sudo apt update && sudo apt install -y librdkafka-dev
sudo pecl install rdkafka
echo "extension=rdkafka.so" | sudo tee /etc/php/8.4/cli/conf.d/20-rdkafka.ini
```

## API

Full interactive documentation available via Swagger UI at `/api/documentation`.

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
| PUT | `/api/monitor/{id}` | Update monitor (partial) |
| DELETE | `/api/monitor/{id}` | Delete monitor |

## Development

```bash
# Run tests
cd api && php artisan test

# Static analysis (PHPStan level 6)
cd api && ./vendor/bin/phpstan analyse

# Code style (Pint / PSR-12)
cd api && ./vendor/bin/pint

# Go worker
cd worker && go run cmd/worker/main.go
```

## Roadmap

- [x] **Phase 1** — Laravel API: auth, monitor CRUD, Swagger, tests
- [x] **Phase 1** — Kafka producer: scheduler dispatches probe tasks per region
- [x] **Phase 1** — POST /api/internal/probe-result endpoint with internal token auth
- [ ] **Phase 1** — Go CF Bridge + CF Workers (all 3 regions)
- [ ] **Phase 1** — Laravel consumer: ClickHouse write + MySQL status update
- [ ] **Phase 1** — Basic Nuxt dashboard
- [ ] **Phase 2** — Incidents, alerts (Email/Telegram), Prometheus, Grafana
- [ ] **Phase 3** — Cloudflare Workers (US, Asia), region comparison UI
- [ ] **Phase 4** — Kubernetes, rate limiting, load testing, CI/CD
