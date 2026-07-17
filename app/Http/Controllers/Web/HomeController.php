<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Services\WebsiteSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __invoke(WebsiteSettings $settings): View
    {
        $cached = Cache::remember('public.home.v2', now()->addMinutes(10), fn (): array => [
            'announcements' => Announcement::query()->published()->orderByDesc('is_featured')->latest('published_at')->limit(3)->get()->map(fn (Announcement $item): array => $item->getAttributes())->all(),
            'pastorMessages' => PastorMessage::query()->published()->orderByDesc('is_featured')->latest('published_at')->limit(3)->get()->map(fn (PastorMessage $item): array => $item->getAttributes())->all(),
            'familyAltars' => FamilyAltar::query()->active()->orderBy('sort_order')->limit(4)->get()->map(fn (FamilyAltar $item): array => $item->getAttributes())->all(),
        ]);

        return view('web.home', [
            'announcements' => Announcement::hydrate($cached['announcements'] ?? []),
            'pastorMessages' => PastorMessage::hydrate($cached['pastorMessages'] ?? []),
            'familyAltars' => FamilyAltar::hydrate($cached['familyAltars'] ?? []),
            'settings' => $settings->public(),
        ]);
    }
}
