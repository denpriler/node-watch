<?php

namespace App\Console\Commands;

use App\DTO\Monitor\MonitorProbe;
use App\Enum\Monitor\MonitorRegion;
use App\Models\Monitor;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\MessageProducer;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

#[Signature('monitor:push-active')]
#[Description('Push active monitors to workers by region.')]
class PushActiveMonitors extends Command
{
    private const array TOPIC_MAP = [
        MonitorRegion::EU_WEST->value => 'monitor.eu-west',
        MonitorRegion::US_EAST->value => 'monitor.us-east',
        MonitorRegion::AP_SOUTH->value => 'monitor.ap-south',
    ];

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

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $query->chunkById(100, function (Collection $monitors) use ($startTime, $bar) {
            /** @var Collection<int, Monitor> $monitors */

            /** @var array<string, MessageProducer> $producers */
            $producers = collect(self::TOPIC_MAP)
                ->mapWithKeys(fn (string $topic, string $region) => [
                    $region => Kafka::publish()->onTopic($topic),
                ])
                ->all();

            $monitors->each(function (Monitor $monitor) use ($producers, $startTime) {
                $probe = MonitorProbe::fromMonitor($monitor);

                foreach ($monitor->regions as $region) {
                    if (! isset($producers[$region])) {
                        continue;
                    }

                    $producers[$region]->withMessage(new Message(
                        body: $probe->toArray(),
                        key: sprintf('probe_%d_%d', $probe->monitorId, $startTime->timestamp),
                    ));
                }
            });

            try {
                foreach ($producers as $producer) {
                    $producer->send();
                }
            } catch (\Throwable $throwable) {
                $this->error($throwable->getMessage());
                $this->info(sprintf('Unprocessed monitors: %s', $monitors->pluck('id')->join(', ')));

                return;
            }

            $monitors->groupBy('check_interval')
                ->each(function (Collection $intervalGroup, int $interval) use ($startTime) {
                    Monitor::whereIn('id', $intervalGroup->pluck('id'))
                        ->update(['next_check_at' => $startTime->addSeconds($interval)]);
                });

            $bar->advance($monitors->count());
        });

        $bar->finish();
        $this->newLine();
        $this->info(sprintf('%d of %d monitors were processed.', $bar->getProgress(), $bar->getMaxSteps()));
    }
}
