<?php

namespace App\Http\Controllers;

use App\Http\Requests\Monitor\GetMonitorLogsRequest;
use App\Models\Monitor;
use App\Services\MonitorLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class MonitorLogEntryController extends Controller
{
    public function __construct(
        private readonly MonitorLogService $monitorLogService
    ) {}

    #[OA\Get(
        path: '/api/monitor/{id}/logs',
        summary: 'Get probe logs for a monitor',
        security: [['bearerAuth' => []]],
        tags: ['Monitors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-04-01')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-04-30')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aggregated probe log buckets grouped by region, sorted by bucket time ascending (default: last 24 hours). Bucket size auto-selected: ≤1d→5min, ≤7d→1h, ≤30d→4h, >30d→12h.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'eu-west', type: 'array', items: new OA\Items(ref: '#/components/schemas/MonitorLogBucket')),
                        new OA\Property(property: 'us-east', type: 'array', items: new OA\Items(ref: '#/components/schemas/MonitorLogBucket')),
                        new OA\Property(property: 'ap-south', type: 'array', items: new OA\Items(ref: '#/components/schemas/MonitorLogBucket')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(GetMonitorLogsRequest $request, Monitor $monitor): JsonResponse
    {
        $this->authorize('view', $monitor);

        return response()->json(
            $this->monitorLogService->logsById(
                $monitor->id,
                $request->has('from') ? CarbonImmutable::parse($request->validated('from')) : null,
                $request->has('to') ? CarbonImmutable::parse($request->validated('to')) : null,
            )
        );
    }
}
