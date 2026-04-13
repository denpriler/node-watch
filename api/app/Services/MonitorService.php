<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\Monitor\CreateMonitorRequest;
use App\Http\Requests\Monitor\UpdateMonitorRequest;
use App\Models\Monitor;
use App\Models\User;

class MonitorService
{
    public function create(CreateMonitorRequest $request): Monitor
    {
        /** @var User $user */
        $user = $request->user();

        return $user->monitors()->create($request->validated());
    }

    public function update(UpdateMonitorRequest $request, Monitor $monitor): Monitor
    {
        $monitor->update($request->validated());

        return $monitor;
    }
}
