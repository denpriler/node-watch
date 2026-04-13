<?php

namespace App\Http\Controllers;

use App\Http\Requests\Monitor\CreateMonitorRequest;
use App\Http\Requests\Monitor\UpdateMonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\User;
use App\Services\MonitorService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class MonitorController extends Controller
{
    public function __construct(
        private readonly MonitorService $monitorService,
    ) {}

    #[OA\Get(
        path: '/api/monitor',
        summary: 'List monitors (paginated)',
        security: [['bearerAuth' => []]],
        tags: ['Monitors'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of monitors',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/MonitorResource'),
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Monitor::class);

        /** @var User $user */
        $user = $request->user();

        return MonitorResource::collection(
            $user->monitors()->paginate()
        );
    }

    #[OA\Post(
        path: '/api/monitor',
        summary: 'Create a monitor',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'url', 'method', 'check_interval', 'timeout', 'expected_status', 'regions', 'is_active'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 3, example: 'My Website'),
                    new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://example.com'),
                    new OA\Property(property: 'method', type: 'string', enum: ['GET', 'POST', 'HEAD'], example: 'HEAD'),
                    new OA\Property(property: 'check_interval', type: 'integer', enum: [30, 60, 120, 180, 240, 360], example: 60),
                    new OA\Property(property: 'timeout', type: 'integer', minimum: 5, maximum: 60, example: 30),
                    new OA\Property(property: 'expected_status', type: 'integer', minimum: 200, example: 200),
                    new OA\Property(property: 'regions', type: 'array', items: new OA\Items(type: 'string', enum: ['eu-west', 'us-east', 'ap-south']), example: ['eu-west']),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ],
            ),
        ),
        tags: ['Monitors'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Monitor created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/MonitorResource'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(CreateMonitorRequest $request): MonitorResource
    {
        $this->authorize('create', Monitor::class);

        $monitor = $this->monitorService->create($request);

        return MonitorResource::make($monitor);
    }

    #[OA\Get(
        path: '/api/monitor/{id}',
        summary: 'Get a monitor',
        security: [['bearerAuth' => []]],
        tags: ['Monitors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Monitor details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/MonitorResource'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(Monitor $monitor): MonitorResource
    {
        $this->authorize('view', $monitor);

        return MonitorResource::make($monitor);
    }

    #[OA\Put(
        path: '/api/monitor/{id}',
        summary: 'Update a monitor (partial)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 3, example: 'My Website'),
                    new OA\Property(property: 'method', type: 'string', enum: ['GET', 'POST', 'HEAD'], example: 'HEAD'),
                    new OA\Property(property: 'check_interval', type: 'integer', enum: [30, 60, 120, 180, 240, 360], example: 60),
                    new OA\Property(property: 'timeout', type: 'integer', minimum: 5, maximum: 60, example: 30),
                    new OA\Property(property: 'expected_status', type: 'integer', minimum: 200, example: 200),
                    new OA\Property(property: 'regions', type: 'array', items: new OA\Items(type: 'string', enum: ['eu-west', 'us-east', 'ap-south'])),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ],
            ),
        ),
        tags: ['Monitors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Monitor updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/MonitorResource'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(UpdateMonitorRequest $request, Monitor $monitor): MonitorResource
    {
        $this->authorize('update', $monitor);

        return MonitorResource::make(
            $this->monitorService->update($request, $monitor)
        );
    }

    #[OA\Delete(
        path: '/api/monitor/{id}',
        summary: 'Delete a monitor',
        security: [['bearerAuth' => []]],
        tags: ['Monitors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Monitor deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/MonitorResource'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(Monitor $monitor): MonitorResource
    {
        $this->authorize('delete', $monitor);

        $monitor->delete();

        return MonitorResource::make($monitor);
    }
}
