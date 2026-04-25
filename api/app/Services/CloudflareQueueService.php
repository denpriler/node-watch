<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Monitor\MonitorProbe;
use App\Enum\Monitor\MonitorRegion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class CloudflareQueueService
{
    private string $baseUrl = 'https://api.cloudflare.com/client/v4/accounts';

    /**
     * @param  array<string, string>  $queueConfig
     */
    public function __construct(
        private readonly string $accountId,
        private readonly string $apiToken,
        private readonly array $queueConfig,
    ) {}

    /**
     * @param  MonitorProbe[]  $probes
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function pushMonitorProbes(MonitorRegion $region, array $probes): void
    {
        $queueId = $this->queueConfig[$region->value] ?? throw new InvalidArgumentException("No queue configured for region: {$region->value}");

        Http::withToken($this->apiToken)
            ->post(sprintf("%s/{$this->accountId}/queues/{$queueId}/messages/batch", $this->baseUrl), [
                'messages' => array_map(fn (MonitorProbe $p) => ['body' => $p->toArray()], $probes),
            ])
            ->throw();
    }
}
