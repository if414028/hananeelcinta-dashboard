<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

final class UpdatePastorMessageRequest extends StorePastorMessageRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pastor_messages.update') ?? false;
    }
}
