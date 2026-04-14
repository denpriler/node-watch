<?php

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MonitorSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = User::all();

        $users->each(function (User $user) {
            Monitor::factory(3)->for($user)->create();
            Monitor::factory(2)->for($user)->up()->create();
            Monitor::factory(1)->for($user)->down()->create();
            Monitor::factory(1)->for($user)->inactive()->create();
        });
    }
}
