<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BrandAssetController extends Controller
{
    public function logo(): BinaryFileResponse
    {
        return response()->file(resource_path('project-assets/logo.webp'), ['Cache-Control' => 'public, max-age=86400']);
    }
}
