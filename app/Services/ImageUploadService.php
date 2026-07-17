<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ImageUploadService
{
    public function store(UploadedFile $file, string $directory, ?string $oldPath = null): string
    {
        $path = $file->store($directory, 'public');
        if ($oldPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
