<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

final class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private const string ENDPOINT_SEND = '/api/v1/notifications/send';

    private const string ENDPOINT_HISTORY = '/api/v1/users/%d/notifications';

    public static function validationDataProvider(): array
    {
        return [
            'empty request' => [
                [],
                ['channel', 'text', 'user_ids', 'priority'],
            ],
            'invalid channel' => [
                [
                    'channel' => 'push',
                    'text' => 'test',
                    'user_ids' => [1],
                    'priority' => NotificationPriority::TRANSACTIONAL,
                ],
                ['channel'],
            ],
            'non-existent user' => [
                [
                    'channel' => NotificationChannel::SMS,
                    'text' => 'test',
                    'user_ids' => [999],
                    'priority' => NotificationPriority::TRANSACTIONAL,
                ],
                ['user_ids.0'],
            ],
        ];
    }

    public function testCreatesNotificationsAndDispatchesJobsForValidRequest(): void
    {
        Queue::fake();

        [$user1, $user2] = $this->createUsers(2);
        $response = $this->sendNotificationRequest([
            'channel' => NotificationChannel::SMS->value,
            'text' => 'Test message',
            'user_ids' => [$user1->id, $user2->id],
            'priority' => NotificationPriority::TRANSACTIONAL->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'idempotency_key',
                'notifications' => [
                    '*' => $this->notificationJsonStructure(),
                ],
            ]);

        $this->assertDatabaseCount('notifications', 2);
        Queue::assertPushed(SendNotificationJob::class, 2);
    }

    /** @return User[] */
    private function createUsers(int $count = 2): array
    {
        return User::factory($count)->create()->all();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function sendNotificationRequest(array $data): TestResponse
    {
        return $this->postJson(self::ENDPOINT_SEND, $data);
    }

    private function notificationJsonStructure(): array
    {
        return [
            'id',
            'user_id',
            'channel',
            'text',
            'priority',
            'status',
            'idempotency_key',
            'gateway_response',
            'created_at',
            'updated_at',
        ];
    }

    public function testTransactionalNotificationsArePushedToHighPriorityQueue(): void
    {
        Queue::fake();

        $user = $this->createUser();
        $this->sendNotificationRequest([
            'channel' => NotificationChannel::SMS->value,
            'text' => 'Ваш код: 1234',
            'user_ids' => [$user->id],
            'priority' => NotificationPriority::TRANSACTIONAL->value,
        ])->assertStatus(201);

        Queue::assertPushedOn('high', SendNotificationJob::class);
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    public function testMarketingNotificationsArePushedToDefaultQueue(): void
    {
        Queue::fake();

        $user = $this->createUser();
        $this->sendNotificationRequest([
            'channel' => NotificationChannel::EMAIL->value,
            'text' => 'Рассылка',
            'user_ids' => [$user->id],
            'priority' => NotificationPriority::MARKETING->value,
        ])->assertStatus(201);

        Queue::assertPushedOn('default', SendNotificationJob::class);
    }

    /**
     * @throws Throwable
     */
    public function testProcessesNotificationAndUpdatesStatusToDelivered(): void
    {
        $notification = $this->createNotification([
            'status' => NotificationStatus::QUEUED,
        ]);

        $job = new SendNotificationJob($notification);
        $job->handle(app(NotificationService::class));

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => NotificationStatus::DELIVERED->value,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createNotification(array $attributes = []): Notification
    {
        return Notification::factory()->create($attributes);
    }

    public function testPreventsDuplicateProcessingViaIdempotencyKey(): void
    {
        $user = $this->createUser();
        $payload = [
            'channel' => NotificationChannel::EMAIL->value,
            'text' => 'Duplicate',
            'user_ids' => [$user->id],
            'priority' => NotificationPriority::MARKETING->value,
        ];

        $firstResponse = $this->sendNotificationRequest($payload);
        $firstResponse->assertStatus(201);

        $idempotencyKey = $firstResponse->json('idempotency_key');

        $payload['idempotency_key'] = $idempotencyKey;
        $this->sendNotificationRequest($payload)->assertStatus(201);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function testReturnsNotificationHistoryForAUser(): void
    {
        $user = $this->createUser();
        $this->createNotification(['user_id' => $user->id]);
        $this->createNotification(['user_id' => $user->id]);

        $otherUser = $this->createUser();
        $this->createNotification(['user_id' => $otherUser->id]);

        $response = $this->getUserNotifications($user->id);
        $response->assertOk()
            ->assertJsonCount(2);
    }

    private function getUserNotifications(int $userId): TestResponse
    {
        return $this->getJson(sprintf(self::ENDPOINT_HISTORY, $userId));
    }

    public function testRetriesFailedNotificationJob(): void
    {
        $notification = $this->createNotification([
            'status' => NotificationStatus::QUEUED,
        ]);

        $job = new SendNotificationJob($notification);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([10, 30, 60], $job->backoff);
    }

    #[DataProvider('validationDataProvider')]
    /**
     * @param array<string, mixed> $payload
     * @param string[] $expectedErrors
     */
    public function testValidatesRequestData(array $payload, array $expectedErrors): void
    {
        $this->sendNotificationRequest($payload)->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('set')->andReturn(true);
    }
}
