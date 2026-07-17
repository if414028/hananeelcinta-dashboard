<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementStatus;
use App\Enums\PastorMessageStatus;
use App\Enums\PrayerRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Congregation;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Models\PrayerRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthExpression = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
        $since = now()->subMonths(11)->startOfMonth();

        return view('admin.dashboard', [
            'summary' => [
                'congregations' => Congregation::query()->count(),
                'active_congregations' => Congregation::query()->where('is_active', true)->count(),
                'new_congregations' => Congregation::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'announcements' => Announcement::query()->count(),
                'active_announcements' => Announcement::query()->published()->count(),
                'prayer_requests' => PrayerRequest::query()->count(),
                'new_prayer_requests' => PrayerRequest::query()->where('status', PrayerRequestStatus::New)->count(),
                'in_prayer_requests' => PrayerRequest::query()->where('status', PrayerRequestStatus::InPrayer)->count(),
                'family_altars' => FamilyAltar::query()->count(),
                'pastor_messages' => PastorMessage::query()->count(),
                'published_pastor_messages' => PastorMessage::query()->published()->count(),
            ],
            'recentPrayerRequests' => PrayerRequest::query()->latest()->limit(5)->get(),
            'recentCongregations' => Congregation::query()->latest()->limit(5)->get(),
            'recentAnnouncements' => Announcement::query()->latest()->limit(5)->get(),
            'recentPastorMessages' => PastorMessage::query()->latest()->limit(5)->get(),
            'charts' => [
                'prayer_status' => PrayerRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
                'announcement_status' => Announcement::query()->selectRaw('status, count(*) as total')->whereIn('status', [AnnouncementStatus::Published, AnnouncementStatus::Draft])->groupBy('status')->pluck('total', 'status'),
                'pastor_status' => PastorMessage::query()->selectRaw('status, count(*) as total')->whereIn('status', [PastorMessageStatus::Published, PastorMessageStatus::Draft])->groupBy('status')->pluck('total', 'status'),
                'pertumbuhan_jemaat' => Congregation::query()->where('created_at', '>=', $since)->selectRaw("$monthExpression as month, count(*) as total")->groupBy('month')->orderBy('month')->pluck('total', 'month'),
                'prayer_per_bulan' => PrayerRequest::query()->where('created_at', '>=', $since)->selectRaw("$monthExpression as month, count(*) as total")->groupBy('month')->orderBy('month')->pluck('total', 'month'),
            ],
        ]);
    }
}
