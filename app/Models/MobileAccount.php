<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MobileAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

final class MobileAccount extends Authenticatable
{
    /** @use HasFactory<MobileAccountFactory> */
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['provider_ids'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'provider_ids' => 'array',
            'is_active' => 'boolean',
            'last_authenticated_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }
}
