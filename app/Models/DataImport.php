<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DataImportStatus;
use Database\Factories\DataImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DataImport extends Model
{
    /** @use HasFactory<DataImportFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => DataImportStatus::class, 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
