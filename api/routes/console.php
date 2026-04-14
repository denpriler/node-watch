<?php

use App\Console\Commands\PushActiveMonitors;

Schedule::command(PushActiveMonitors::class)->everyThirtySeconds();
