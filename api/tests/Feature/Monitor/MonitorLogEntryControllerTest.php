<?php

namespace Tests\Feature\Monitor;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitorLogEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function fakeClickHouseRows(array $rows): void
    {
        Http::fake([
            config('clickhouse.host').'*' => Http::response(['data' => $rows], 200),
        ]);
    }

    // region Auth / Authorization

    public function test_logs_require_authentication(): void
    {
        $monitor = Monitor::factory()->create();

        $this->getJson("/api/monitor/{$monitor->id}/logs")->assertUnauthorized();
    }

    public function test_logs_forbidden_for_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->getJson("/api/monitor/{$monitor->id}/logs")
            ->assertForbidden();
    }

    // endregion

    // region Validation

    public function test_logs_rejects_invalid_date_format(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/monitor/{$monitor->id}/logs?from=not-a-date")
            ->assertUnprocessable();
    }

    public function test_logs_rejects_to_before_from(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/monitor/{$monitor->id}/logs?from=2026-05-10&to=2026-05-01")
            ->assertUnprocessable();
    }

    // endregion

    // region Happy path

    public function test_logs_return_buckets_grouped_by_region(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->fakeClickHouseRows([
            [
                'bucket' => '2026-05-13 09:00:00',
                'region' => 'eu-west',
                'avg_response_time_ms' => '142.5',
                'min_response_time_ms' => '98',
                'max_response_time_ms' => '210',
                'avg_ttfb_ms' => '87.3',
                'down_count' => '0',
                'sample_count' => '12',
            ],
            [
                'bucket' => '2026-05-13 09:00:00',
                'region' => 'us-east',
                'avg_response_time_ms' => '200.0',
                'min_response_time_ms' => '150',
                'max_response_time_ms' => '300',
                'avg_ttfb_ms' => '100.0',
                'down_count' => '1',
                'sample_count' => '12',
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/monitor/{$monitor->id}/logs")
            ->assertOk();

        $response->assertJsonStructure([
            'eu-west' => [['bucket', 'region', 'avg_response_time_ms', 'min_response_time_ms', 'max_response_time_ms', 'avg_ttfb_ms', 'down_count', 'sample_count']],
            'us-east' => [['bucket', 'region', 'avg_response_time_ms', 'min_response_time_ms', 'max_response_time_ms', 'avg_ttfb_ms', 'down_count', 'sample_count']],
        ]);

        $response->assertJsonPath('eu-west.0.down_count', 0);
        $response->assertJsonPath('us-east.0.down_count', 1);
    }

    public function test_logs_return_empty_when_no_data(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->fakeClickHouseRows([]);

        $this->actingAs($user)
            ->getJson("/api/monitor/{$monitor->id}/logs")
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_logs_accept_from_to_query_params(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->fakeClickHouseRows([]);

        $this->actingAs($user)
            ->getJson("/api/monitor/{$monitor->id}/logs?from=2026-05-01&to=2026-05-13")
            ->assertOk();
    }

    // endregion
}
