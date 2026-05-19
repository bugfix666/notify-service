<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'in:sms,email'],
            'text' => ['required', 'string', 'max:1000'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'priority' => ['required', 'string', 'in:transactional,marketing'],
            'idempotency_key' => ['sometimes', 'string', 'uuid'],
        ];
    }
}
