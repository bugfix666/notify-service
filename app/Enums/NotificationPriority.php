<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationPriority: string
{
    case TRANSACTIONAL = 'transactional';
    case MARKETING = 'marketing';

    public static function queueName(NotificationPriority $priority): string
    {
        return match ($priority) {
            NotificationPriority::TRANSACTIONAL => 'high',
            NotificationPriority::MARKETING => 'default',
        };
    }
}
