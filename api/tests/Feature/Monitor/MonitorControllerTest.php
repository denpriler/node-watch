<?php

namespace Tests\Feature\Monitor;

use App\Enum\Monitor\MonitorStatus;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'My Website',
            'url' => 'https://example.com',
            'method' => 'HEAD',
            'check_interval' => 60,
            'timeout' => 30,
            'expected_status' => 200,
            'regions' => ['eu-west'],
            'is_active' => true,
        ], $overrides);
    }

    // region Index

    public function test_index_returns_paginated_monitors(): void
    {
        $user = User::factory()->create();
        Monitor::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/monitor')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_index_returns_only_own_monitors(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Monitor::factory()->count(2)->create(['user_id' => $user->id]);
        Monitor::factory()->count(3)->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->getJson('/api/monitor')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/monitor')->assertUnauthorized();
    }

    // endregion

    // region Store

    public function test_store_creates_monitor(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/monitor', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('name', 'My Website')
            ->assertJsonPath('url', 'https://example.com')
            ->assertJsonPath('last_status', MonitorStatus::PENDING->value);

        $this->assertDatabaseHas('monitors', [
            'user_id' => $user->id,
            'name' => 'My Website',
            'url' => 'https://example.com',
        ]);
    }

    public function test_store_assigns_monitor_to_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/monitor', $this->validPayload());

        $response->assertCreated();

        $this->assertDatabaseHas('monitors', [
            'id' => $response->json('id'),
            'user_id' => $user->id,
        ]);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/monitor', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'url', 'method', 'check_interval', 'timeout', 'expected_status', 'regions', 'is_active']);
    }

    public function test_store_fails_with_invalid_method(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/monitor', $this->validPayload(['method' => 'PATCH']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['method']);
    }

    public function test_store_fails_with_invalid_region(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/monitor', $this->validPayload(['regions' => ['mars-1']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['regions.0']);
    }

    public function test_store_fails_with_invalid_check_interval(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/monitor', $this->validPayload(['check_interval' => 45]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_interval']);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/monitor', $this->validPayload())->assertUnauthorized();
    }

    // endregion

    // region Show

    public function test_show_returns_monitor(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/monitor/{$monitor->id}")
            ->assertOk()
            ->assertJsonPath('id', $monitor->id)
            ->assertJsonPath('name', $monitor->name);
    }

    public function test_show_returns_403_for_another_users_monitor(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->getJson("/api/monitor/{$monitor->id}")
            ->assertForbidden();
    }

    public function test_show_returns_404_for_nonexistent_monitor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/monitor/999')
            ->assertNotFound();
    }

    public function test_show_requires_authentication(): void
    {
        $monitor = Monitor::factory()->create();

        $this->getJson("/api/monitor/{$monitor->id}")->assertUnauthorized();
    }

    // endregion

    // region Update

    public function test_update_modifies_monitor(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson("/api/monitor/{$monitor->id}", ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('name', 'Updated Name');

        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_allows_partial_payload(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create([
            'user_id' => $user->id,
            'check_interval' => 30,
        ]);

        $this->actingAs($user)
            ->putJson("/api/monitor/{$monitor->id}", ['check_interval' => 120])
            ->assertOk()
            ->assertJsonPath('check_interval', 120);
    }

    public function test_update_does_not_change_url(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
        ]);

        $this->actingAs($user)
            ->putJson("/api/monitor/{$monitor->id}", [
                'name' => 'New Name',
                'url' => 'https://evil.com',
            ])
            ->assertOk()
            ->assertJsonPath('url', 'https://example.com');
    }

    public function test_update_fails_with_invalid_check_interval(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson("/api/monitor/{$monitor->id}", ['check_interval' => 45])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_interval']);
    }

    public function test_update_returns_403_for_another_users_monitor(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->putJson("/api/monitor/{$monitor->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_update_requires_authentication(): void
    {
        $monitor = Monitor::factory()->create();

        $this->putJson("/api/monitor/{$monitor->id}", ['name' => 'x'])->assertUnauthorized();
    }

    // endregion

    // region Destroy

    public function test_destroy_deletes_monitor(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/monitor/{$monitor->id}")
            ->assertOk()
            ->assertJsonPath('id', $monitor->id);

        $this->assertDatabaseMissing('monitors', ['id' => $monitor->id]);
    }

    public function test_destroy_returns_403_for_another_users_monitor(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->deleteJson("/api/monitor/{$monitor->id}")
            ->assertForbidden();
    }

    public function test_destroy_requires_authentication(): void
    {
        $monitor = Monitor::factory()->create();

        $this->deleteJson("/api/monitor/{$monitor->id}")->assertUnauthorized();
    }

    // endregion
}
