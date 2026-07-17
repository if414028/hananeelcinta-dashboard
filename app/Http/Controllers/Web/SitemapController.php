<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\PastorMessage;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response()->view('web.sitemap', ['announcements' => Announcement::query()->published()->get(['slug', 'updated_at']), 'pastorMessages' => PastorMessage::query()->published()->get(['slug', 'updated_at'])])->header('Content-Type', 'application/xml');
    }
}
