<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\PastorMessage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $items = Cache::remember('public.sitemap.v1', now()->addHour(), fn (): array => [
            'announcements' => Announcement::query()->published()->get(['slug', 'updated_at'])->map(fn (Announcement $item): array => $item->getAttributes())->all(),
            'pastorMessages' => PastorMessage::query()->published()->get(['slug', 'updated_at'])->map(fn (PastorMessage $item): array => $item->getAttributes())->all(),
        ]);

        return response()->view('web.sitemap', [
            'announcements' => Announcement::hydrate($items['announcements'] ?? []),
            'pastorMessages' => PastorMessage::hydrate($items['pastorMessages'] ?? []),
        ])->header('Content-Type', 'application/xml')->header('Cache-Control', 'public, max-age=3600');
    }
}
