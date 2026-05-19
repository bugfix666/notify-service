<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property Notification $resource
 */
class NotificationResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'channel' => $this->resource->channel,
            'text' => $this->resource->text,
            'priority' => $this->resource->priority,
            'status' => $this->resource->status,
            'idempotency_key' => $this->resource->idempotency_key,
            'gateway_response' => $this->resource->gateway_response,
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
