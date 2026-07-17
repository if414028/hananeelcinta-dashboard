<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

final class UpdateFamilyAltarRequest extends StoreFamilyAltarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('family_altars.update') ?? false;
    }
}
