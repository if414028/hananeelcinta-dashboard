<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EventRegistration extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'checked_in_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'ticket_code';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
