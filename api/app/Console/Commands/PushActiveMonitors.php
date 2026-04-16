<?php

declare(strict_types=1);

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
use RdKafka\Conf;
use RdKafka\Producer;
use RdKafka\ProducerTopic;

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
        $conf = new Conf;
        $conf->set('metadata.broker.list', config('kafka.brokers'));
        $conf->set('security.protocol', config('kafka.securityProtocol'));
        $conf->set('sasl.mechanisms', config('kafka.sasl.mechanisms'));
        $conf->set('sasl.username', config('kafka.sasl.username'));
        $conf->set('sasl.password', config('kafka.sasl.password'));

        $producer = new Producer($conf);

        /** @var array<string, ProducerTopic> $topics */
        $topics = collect(self::TOPIC_MAP)
            ->map(fn (string $topic) => $producer->newTopic($topic))
            ->all();

        $startTime = CarbonImmutable::now();

        $query = Monitor::query()
            ->where(
                fn (Builder $query) => $query
                    ->whereNull('next_check_at')
                    ->orWhere('next_check_at', '<=', $startTime)
            )
            ->where('is_active', true);

        if ($count = $query->count()) {
            $this->newLine();
            $bar = $this->output->createProgressBar($count);
            $bar->start();
            $this->newLine();

            $query->chunkById(100, function (Collection $monitors) use ($startTime, $bar, $topics, $producer) {
                /** @var Collection<int, Monitor> $monitors */
                $monitors->each(function (Monitor $monitor) use ($startTime, $bar, $topics, $producer) {
                    $probe = MonitorProbe::fromMonitor($monitor);

                    foreach ($monitor->regions as $region) {
                        if (! isset($topics[$region])) {
                            continue;
                        }

                        $this->info(sprintf('Pushing monitor #%d probe to %s', $monitor->id, $region));
                        $topics[$region]->produce(
                            RD_KAFKA_PARTITION_UA,
                            0,
                            json_encode($probe->toArray()),
                            sprintf('probe_%d_%d', $probe->monitorId, $startTime->timestamp),
                        );

                        $producer->poll(0);
                    }

                    $bar->advance();
                });

                $monitors->groupBy('check_interval')
                    ->each(function (Collection $intervalGroup, int $interval) use ($startTime) {
                        Monitor::whereIn('id', $intervalGroup->pluck('id'))
                            ->update(['next_check_at' => $startTime->addSeconds($interval)]);
                    });
            });

            $remaining = $producer->flush(10000);

            $bar->finish();
            $this->newLine();

            if ($remaining > 0) {
                $this->warn("Flush timed out: {$remaining} message(s) may not have been delivered.");
            }

            $this->info(sprintf('%d of %d monitors were processed.', $bar->getProgress(), $bar->getMaxSteps()));
        }
    }
}
