<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\Monitor\MonitorLogEntry;
use App\Enum\Monitor\MonitorRegion;
use App\Services\ClickHouseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

readonly class MonitorLogRepository
{
    public function __construct(
        protected ClickHouseService $clickHouseService,
    ) {}

    /**
     * @return Collection<int, MonitorLogEntry>
     */
    public function getMonitorLogsHistory(int $monitorId, MonitorRegion $region, int $limit = 10): Collection
    {
        try {
            $logs = $this->clickHouseService->select(
                sprintf(
                    'SELECT * FROM monitor_logs WHERE monitor_id = %d AND region = \'%s\' ORDER BY checked_at DESC LIMIT %d',
                    $monitorId,
                    $region->toStringValue(),
                    $limit
                )
            );

            return collect($logs)->map(fn (array $log) => MonitorLogEntry::from($log));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            return collect();
        }
    }

    public function insertMonitorLogEntry(MonitorLogEntry $monitorLogEntry): bool
    {
        try {
            $this->clickHouseService->insert('monitor_logs', [$monitorLogEntry->toArray()]);

            return true;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            return false;
        }
    }
}
