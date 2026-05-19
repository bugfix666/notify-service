<?php

declare(strict_types=1);

namespace App\Gateways;

use App\Contracts\GatewayInterface;
use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Log;

class SmsGateway implements GatewayInterface
{
    public function send(string $recipient, string $text, NotificationChannel $channel): bool
    {
        Log::info(sprintf('SMS sent to %s: %s', $recipient, $text));

        return true;
    }
}
