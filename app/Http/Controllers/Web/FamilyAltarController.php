<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Models\FamilyAltar;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FamilyAltarController extends Controller
{
    public function __invoke(Request $request): View
    {
        $items = FamilyAltar::query()->active()->when($request->filled('day'), fn ($query) => $query->where('day_of_week', $request->day))->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$request->search.'%')->orWhere('city', 'like', '%'.$request->search.'%')->orWhere('pic_name', 'like', '%'.$request->search.'%')))->orderBy('sort_order')->orderBy('name')->paginate(12)->withQueryString();

        return view('web.family-altars', ['items' => $items, 'days' => DayOfWeek::options()]);
    }
}
