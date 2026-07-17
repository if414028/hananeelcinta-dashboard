<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $items = Announcement::query()->published()->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$request->search.'%')->orWhere('description', 'like', '%'.$request->search.'%')))->orderByDesc('is_featured')->latest('published_at')->paginate(9)->withQueryString();

        return view('web.announcements.index', compact('items'));
    }

    public function show(Announcement $announcement): View
    {
        abort_unless(Announcement::query()->published()->whereKey($announcement)->exists(), 404);
        $related = Announcement::query()->published()->whereKeyNot($announcement->id)->latest('published_at')->limit(3)->get();

        return view('web.announcements.show', compact('announcement', 'related'));
    }
}
