<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

final class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $items = Activity::query()->with('causer')->when($request->filled('causer_id'), fn ($q) => $q->where('causer_id', $request->causer_id))->when($request->filled('event'), fn ($q) => $q->where('event', $request->event))->when($request->filled('module'), fn ($q) => $q->where('log_name', $request->module))->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))->latest()->paginate(25)->withQueryString();

        return view('admin.audit-logs.index', ['items' => $items, 'admins' => User::query()->pluck('name', 'id')]);
    }
}
