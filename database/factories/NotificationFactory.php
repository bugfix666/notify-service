<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => $this->faker->randomElement(NotificationChannel::cases()),
            'text' => $this->faker->sentence,
            'priority' => $this->faker->randomElement(NotificationPriority::cases()),
            'status' => NotificationStatus::QUEUED,
            'idempotency_key' => Str::uuid(),
        ];
    }
}
