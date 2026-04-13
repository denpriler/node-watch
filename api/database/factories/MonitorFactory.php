<?php

namespace Database\Factories;

use App\Enum\Monitor\MonitorMethod;
use App\Enum\Monitor\MonitorRegion;
use App\Enum\Monitor\MonitorStatus;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'url' => $this->faker->url(),
            'method' => $this->faker->randomElement(MonitorMethod::cases())->value,
            'check_interval' => $this->faker->randomElement([30, 60, 120, 180, 240, 360]),
            'timeout' => $this->faker->numberBetween(5, 60),
            'expected_status' => 200,
            'regions' => $this->faker->randomElements(
                array_column(MonitorRegion::cases(), 'value'),
                $this->faker->numberBetween(1, count(MonitorRegion::cases()))
            ),
            'is_active' => true,
            'last_checked_at' => null,
            'last_status' => MonitorStatus::PENDING,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function up(): static
    {
        return $this->state([
            'last_status' => MonitorStatus::UP,
            'last_checked_at' => now(),
        ]);
    }

    public function down(): static
    {
        return $this->state([
            'last_status' => MonitorStatus::DOWN,
            'last_checked_at' => now(),
        ]);
    }
}
