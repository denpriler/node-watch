<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\Monitor\MonitorStatus;
use App\Http\Requests\ProbeResultRequest;
use App\Models\Monitor;
use Illuminate\Http\JsonResponse;

class ProbeResultController extends Controller
{
    public function __invoke(ProbeResultRequest $request): JsonResponse
    {
        $data = $request->validated();
        \Log::debug($data);

        Monitor::where('id', $data['monitor_id'])
            ->update([
                'last_status' => $data['is_up']
                    ? MonitorStatus::UP->value
                    : MonitorStatus::DOWN->value,
            ]);

        return response()->json(['ok' => true]);
    }
}
