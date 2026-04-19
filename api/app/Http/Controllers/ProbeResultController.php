<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Monitor\MonitorLogEntry;
use App\Enum\Monitor\MonitorStatus;
use App\Http\Requests\ProbeResultRequest;
use App\Models\Monitor;
use App\Repositories\MonitorLogRepository;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProbeResultController extends Controller
{
    #[OA\Post(
        path: '/api/internal/probe-result',
        description: 'Called by CF Workers after executing a probe. Authenticated via X-Internal-Token header.',
        summary: 'Submit probe result',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['monitor_id', 'region', 'status_code', 'response_time_ms', 'ttfb_ms', 'is_up', 'checked_at'],
                properties: [
                    new OA\Property(property: 'monitor_id', type: 'integer', example: 1),
                    new OA\Property(property: 'region', type: 'string', example: 'eu-west'),
                    new OA\Property(property: 'status_code', type: 'integer', example: 200),
                    new OA\Property(property: 'response_time_ms', type: 'integer', example: 142),
                    new OA\Property(property: 'ttfb_ms', type: 'integer', example: 98),
                    new OA\Property(property: 'is_up', type: 'boolean', example: true),
                    new OA\Property(property: 'error', type: 'string', example: null, nullable: true),
                    new OA\Property(property: 'checked_at', type: 'string', format: 'date-time', example: '2026-04-18T14:00:00.000Z'),
                ],
            ),
        ),
        tags: ['Internal'],
        parameters: [
            new OA\Parameter(name: 'X-Internal-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Result accepted', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'ok', type: 'boolean', example: true)],
            )),
            new OA\Response(response: 401, description: 'Invalid or missing internal token'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(ProbeResultRequest $request, MonitorLogRepository $monitorLogRepository): JsonResponse
    {
        $data = MonitorLogEntry::fromProbeResultRequest($request);

        Monitor::where('id', $data->monitorId)
            ->update([
                'last_status' => $data->isUp
                    ? MonitorStatus::UP->value
                    : MonitorStatus::DOWN->value,
            ]);

        return response()->json(['ok' => $monitorLogRepository->insertMonitorLogEntry($data)]);
    }
}
