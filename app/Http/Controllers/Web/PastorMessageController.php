<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PastorMessage;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PastorMessageController extends Controller
{
    public function index(Request $request): View
    {
        $items = PastorMessage::query()->published()->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$request->search.'%')->orWhere('writer', 'like', '%'.$request->search.'%')))->orderByDesc('is_featured')->latest('published_at')->paginate(9)->withQueryString();

        return view('web.pastor-messages.index', compact('items'));
    }

    public function show(PastorMessage $pastorMessage): View
    {
        abort_unless(PastorMessage::query()->published()->whereKey($pastorMessage)->exists(), 404);
        PastorMessage::query()->whereKey($pastorMessage)->increment('view_count');
        $related = PastorMessage::query()->published()->whereKeyNot($pastorMessage->id)->where('writer', $pastorMessage->writer)->latest('published_at')->limit(3)->get();
        if ($related->isEmpty()) {
            $related = PastorMessage::query()->published()->whereKeyNot($pastorMessage->id)->latest('published_at')->limit(3)->get();
        }

        return view('web.pastor-messages.show', compact('pastorMessage', 'related'));
    }
}
