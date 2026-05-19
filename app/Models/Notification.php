<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Database\Factories\NotificationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property NotificationChannel $channel
 * @property NotificationPriority $priority
 * @property NotificationStatus $status
 * @method static Builder<static>|Notification newModelQuery()
 * @method static Builder<static>|Notification newQuery()
 * @method static Builder<static>|Notification query()
 * @property-read User|null $user
 * @template TFactory of NotificationFactory
 * @property int $id
 * @property int $user_id
 * @property string $text
 * @property string $idempotency_key
 * @property string|null $gateway_response
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static NotificationFactory factory($count = null, $state = [])
 * @method static Builder<static>|Notification whereChannel($value)
 * @method static Builder<static>|Notification whereCreatedAt($value)
 * @method static Builder<static>|Notification whereGatewayResponse($value)
 * @method static Builder<static>|Notification whereId($value)
 * @method static Builder<static>|Notification whereIdempotencyKey($value)
 * @method static Builder<static>|Notification wherePriority($value)
 * @method static Builder<static>|Notification whereStatus($value)
 * @method static Builder<static>|Notification whereText($value)
 * @method static Builder<static>|Notification whereUpdatedAt($value)
 * @method static Builder<static>|Notification whereUserId($value)
 * @mixin Eloquent
 */
class Notification extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel',
        'text',
        'priority',
        'status',
        'idempotency_key',
        'gateway_response',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
        ];
    }
}
