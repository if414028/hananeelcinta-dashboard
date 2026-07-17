<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

final class UpdateAnnouncementRequest extends StoreAnnouncementRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('announcements.update') ?? false;
    }
}
