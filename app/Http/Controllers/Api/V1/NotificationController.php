<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notifications', description: 'Массовая рассылка уведомлений')]
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/notifications/send',
        summary: 'Запуск массовой рассылки уведомлений',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['channel', 'text', 'user_ids', 'priority'],
                properties: [
                    new OA\Property(property: 'channel', type: 'string', example: 'sms', enum: ['sms', 'email']),
                    new OA\Property(property: 'text', type: 'string', example: 'Код: 1234', maxLength: 1000),
                    new OA\Property(
                        property: 'user_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [1, 2, 3]
                    ),
                    new OA\Property(
                        property: 'priority',
                        type: 'string',
                        example: 'transactional',
                        enum: ['transactional', 'marketing']
                    ),
                    new OA\Property(
                        property: 'idempotency_key',
                        type: 'string',
                        format: 'uuid',
                        example: '550e8400-e29b-41d4-a716-446655440000',
                        nullable: true
                    ),
                ]
            )
        ),
        tags: ['Notifications'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Уведомления поставлены в очередь',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Notifications queued'),
                    new OA\Property(
                        property: 'idempotency_key',
                        type: 'string',
                        example: '550e8400-e29b-41d4-a716-446655440000'
                    ),
                    new OA\Property(
                        property: 'notifications',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'user_id', type: 'integer'),
                            new OA\Property(property: 'channel', type: 'string'),
                            new OA\Property(property: 'text', type: 'string'),
                            new OA\Property(property: 'priority', type: 'string'),
                            new OA\Property(property: 'status', type: 'string'),
                            new OA\Property(property: 'idempotency_key', type: 'string'),
                            new OA\Property(property: 'gateway_response', type: 'string', nullable: true),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        ])
                    ),
                ])
            ),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ]
    )]
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->sendBulk(
            channel: $request->input('channel'),
            text: $request->input('text'),
            userIds: $request->input('user_ids'),
            priority: $request->input('priority'),
            idempotencyKey: $request->input('idempotency_key')
        );

        return new JsonResponse([
            'message' => 'Notifications queued',
            'idempotency_key' => $result['idempotency_key'],
            'notifications' => NotificationResource::collection($result['notifications']),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/users/{user_id}/notifications',
        summary: 'Получить историю уведомлений пользователя',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'user_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список уведомлений',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'user_id', type: 'integer'),
                                new OA\Property(property: 'channel', type: 'string'),
                                new OA\Property(property: 'text', type: 'string'),
                                new OA\Property(property: 'priority', type: 'string'),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'idempotency_key', type: 'string'),
                                new OA\Property(property: 'gateway_response', type: 'string', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ])
                        ),
                    ]
                )
            ),
        ]
    )]
    public function history(int $userId): JsonResponse
    {
        $notifications = $this->notificationService->history($userId);

        return new JsonResponse(NotificationResource::collection($notifications));
    }
}
