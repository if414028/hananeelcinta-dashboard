<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\GeneratePrayerReference;
use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestSource;
use App\Enums\PrayerRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StorePrayerRequest;
use App\Models\PrayerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PrayerRequestController extends Controller
{
    public function create(): View
    {
        return view('web.prayer-request.create', ['categories' => PrayerRequestCategory::options()]);
    }

    public function store(StorePrayerRequest $request, GeneratePrayerReference $generator): RedirectResponse
    {
        $reference = DB::transaction(function () use ($request, $generator): string {
            $reference = $generator->handle();
            PrayerRequest::query()->create(array_merge($request->safe()->except(['privacy_accepted', 'website']), ['reference_number' => $reference, 'is_anonymous' => $request->boolean('is_anonymous'), 'is_confidential' => $request->boolean('is_confidential'), 'status' => PrayerRequestStatus::New, 'source' => PrayerRequestSource::Website, 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000)]));

            return $reference;
        });

        return redirect()->route('prayer-request.success', $reference);
    }

    public function success(string $reference): View
    {
        abort_unless(preg_match('/^PR-\d{8}-\d{4,}$/', $reference) === 1, 404);

        return view('web.prayer-request.success', compact('reference'));
    }
}
