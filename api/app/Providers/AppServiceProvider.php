<?php

namespace App\Providers;

use App\Models\Monitor;
use App\Services\ClickHouseService;
use App\Services\CloudflareQueueService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClickHouseService::class, fn () => new ClickHouseService(
            host: config('clickhouse.host'),
            user: config('clickhouse.user'),
            password: config('clickhouse.password'),
        ));

        $this->app->singleton(CloudflareQueueService::class, fn () => new CloudflareQueueService(
            accountId: config('cloudflare.queue.account_id'),
            apiToken: config('cloudflare.queue.api_token'),
            queueConfig: config('cloudflare.queue.id'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::model('monitor', Monitor::class);
    }
}
