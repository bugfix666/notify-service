<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\NotificationRepositoryInterface;
use App\Models\Notification;
use Illuminate\Support\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function updateStatus(Notification $notification, string $status): void
    {
        $notification->update(['status' => $status]);
    }

    public function findByUser(int $userId): Collection
    {
        return Notification::query()
            ->where('user_id', $userId)->latest()
            ->get();
    }

    public function find(int $id): Notification
    {
        return Notification::findOrFail($id);
    }
}
