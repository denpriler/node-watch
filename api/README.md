# NodeWatch API

Laravel 13 REST API — core of the NodeWatch uptime monitoring service.

## Stack

- **Laravel 13**, PHP 8.4
- **MySQL 8.4** — operational data
- **Redis 7.4** — sessions, cache
- **Kafka (Redpanda)** — probe task dispatch and result ingestion
- **ClickHouse 25.3** — time-series probe metrics
- **Sanctum** — token auth
- **spatie/laravel-data** — DTOs
- **l5-swagger** — OpenAPI docs

## Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed     # dev@nodewatch.local / password
php artisan serve
```

Swagger UI: `http://localhost:8000/api/documentation`

## Auth

| Method | Endpoint | Auth |
|--------|----------|------|
| POST | `/api/auth/register` | — |
| POST | `/api/auth/login` | — |
| GET | `/api/auth/me` | Bearer |
| POST | `/api/auth/logout` | Bearer |

## Monitors

All endpoints require `Authorization: Bearer <token>`.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/monitor` | List (paginated) |
| POST | `/api/monitor` | Create |
| GET | `/api/monitor/{id}` | Get |
| PUT | `/api/monitor/{id}` | Update (partial) |
| DELETE | `/api/monitor/{id}` | Delete |

Monitor fields: `name`, `url`, `method` (HEAD/GET/POST), `check_interval` (30/60/120/180/240/360s), `timeout`, `expected_status`, `regions` (eu-west/us-east/ap-south), `is_active`.

URL changes are intentionally blocked on update (planned premium feature).

## Scheduler

```bash
php artisan schedule:work   # local
```

Every 30s: `monitor:push-active` — queries monitors where `next_check_at <= now()`, pushes probe tasks to Kafka topics per region, updates `next_check_at = now() + check_interval`.

Kafka topics: `monitor.eu-west`, `monitor.us-east`, `monitor.ap-south`, `monitor.result`

## Commands

```bash
php artisan test                        # 39 tests
./vendor/bin/phpstan analyse            # PHPStan level 6
./vendor/bin/pint                       # PSR-12 (Pint)
php artisan monitor:push-active         # manual dispatch
php artisan l5-swagger:generate         # regenerate OpenAPI spec
```

## Structure

```
app/
├── Console/Commands/   PushActiveMonitors
├── DTO/Monitor/        MonitorProbe
├── Enums/Monitor/      MonitorMethod, MonitorRegion, MonitorStatus
├── Http/
│   ├── Controllers/    AuthController, MonitorController
│   ├── Requests/       Auth/, Monitor/
│   └── Resources/      Auth/UserResource, MonitorResource
├── Models/             User, Monitor
├── Policies/           MonitorPolicy
├── Services/           AuthService, MonitorService
└── OpenApi/            OpenApiSpec.php
```
