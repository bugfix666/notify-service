<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationPriority;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Notification $notification
    ) {
        $this->queue = NotificationPriority::queueName($notification->priority);
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(NotificationService $service): void
    {
        $service->processSend($this->notification);
    }
}
