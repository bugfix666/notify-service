<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\NotificationChannel;

interface GatewayInterface
{
    public function send(string $recipient, string $text, NotificationChannel $channel): bool;
}
