<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Notification;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Notification;

    public function updateStatus(Notification $notification, string $status): void;

    public function findByUser(int $userId): Collection;

    public function find(int $id): Notification;
}
