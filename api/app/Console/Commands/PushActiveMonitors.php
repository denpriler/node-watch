<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DTO\Monitor\MonitorProbe;
use App\Enum\Monitor\MonitorRegion;
use App\Models\Monitor;
use App\Services\CloudflareQueueService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

#[Signature('monitor:push-active')]
#[Description('Push active monitors to workers by region.')]
class PushActiveMonitors extends Command
{
    public function handle(): void
    {
        $startTime = CarbonImmutable::now();

        $query = Monitor::query()
            ->where(
                fn (Builder $query) => $query
                    ->whereNull('next_check_at')
                    ->orWhere('next_check_at', '<=', $startTime)
            )
            ->where('is_active', true);

        if ($count = $query->count()) {
            /** @var CloudflareQueueService $cfQueueService */
            $cfQueueService = resolve(CloudflareQueueService::class);

            $this->newLine();
            $bar = $this->output->createProgressBar($count);
            $bar->start();
            $this->newLine();

            $query->chunkById(100, function (Collection $monitors) use ($startTime, $bar, $cfQueueService) {
                /** @var Collection<int, Monitor> $monitors */
                $regionProbes = [];

                $monitors->each(function (Monitor $monitor) use (&$regionProbes) {
                    $probe = MonitorProbe::fromMonitor($monitor);

                    foreach ($monitor->regions as $region) {
                        if (! MonitorRegion::tryFrom($region)) {
                            continue;
                        }
                        $regionProbes[$region][] = $probe;
                    }
                });

                try {
                    foreach ($regionProbes as $region => $probes) {
                        $this->newLine();
                        $this->info(sprintf('Pushing %s probes to %s region...', count($probes), $region));
                        $cfQueueService->pushMonitorProbes(MonitorRegion::from($region), $probes);
                    }
                    $this->newLine();
                    $bar->advance($monitors->count());
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }

                $monitors->groupBy('check_interval')
                    ->each(function (Collection $intervalGroup, int $interval) use ($startTime) {
                        Monitor::whereIn('id', $intervalGroup->pluck('id'))
                            ->update(['next_check_at' => $startTime->addSeconds($interval)]);
                    });
            });

            $bar->finish();
            $this->newLine();
            $this->newLine();

            $this->info(sprintf('%d of %d monitors were processed.', $bar->getProgress(), $bar->getMaxSteps()));
        }
    }
}
