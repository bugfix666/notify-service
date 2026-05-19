<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GatewayInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

readonly class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepo,
        private GatewayInterface $gateway
    ) {
    }

    /**
     * @param int[] $userIds
     * @return array{notifications: Collection, idempotency_key: string}
     */
    public function sendBulk(
        string $channel,
        string $text,
        array $userIds,
        string $priority,
        ?string $idempotencyKey = null
    ): array {
        $idempotencyKey ??= (string)Str::uuid();

        $existing = Notification::where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            return [
                'notifications' => Notification::where('idempotency_key', $idempotencyKey)->get(),
                'idempotency_key' => $idempotencyKey,
            ];
        }

        $lockKey = 'idempotency:' . $idempotencyKey;

        try {
            $lock = Redis::set($lockKey, 'processing', ['NX', 'EX' => 3600]);
        } catch (Throwable) {
            $lock = false;
        }

        if ($lock === false) {
            $existing = Notification::where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return [
                    'notifications' => Notification::where('idempotency_key', $idempotencyKey)->get(),
                    'idempotency_key' => $idempotencyKey,
                ];
            }
        }

        $notifications = DB::transaction(function () use ($channel, $text, $userIds, $priority, $idempotencyKey) {
            $records = [];

            foreach ($userIds as $userId) {
                $notification = $this->notificationRepo->create([
                    'user_id' => $userId,
                    'channel' => $channel,
                    'text' => $text,
                    'priority' => $priority,
                    'idempotency_key' => $idempotencyKey,
                    'status' => NotificationStatus::QUEUED->value,
                ]);
                $records[] = $notification;
            }

            foreach ($records as $notification) {
                SendNotificationJob::dispatch($notification)
                    ->onQueue(NotificationPriority::queueName(NotificationPriority::from($priority)));
            }

            return collect($records);
        });

        return [
            'notifications' => $notifications,
            'idempotency_key' => $idempotencyKey,
        ];
    }

    public function processSend(Notification $notification): void
    {
        $recipient = $notification->user->phone ?? 'unknown';
        $success = $this->gateway->send($recipient, $notification->text, $notification->channel);

        if ($success) {
            $notification->update([
                'status' => NotificationStatus::DELIVERED->value,
                'gateway_response' => 'OK',
            ]);
        } else {
            throw new Exception('Gateway returned failure');
        }
    }

    public function history(int $userId): Collection
    {
        return $this->notificationRepo->findByUser($userId);
    }
}
