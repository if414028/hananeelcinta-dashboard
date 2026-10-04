<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ImageUploadService
{
    public function store(UploadedFile $file, string $directory, ?string $oldPath = null): string
    {
        $path = $file->store($directory, 'public');
        if ($path === false) {
            throw new RuntimeException('Unable to store uploaded image.');
        }
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
