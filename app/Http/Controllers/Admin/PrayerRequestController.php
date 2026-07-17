<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestSource;
use App\Enums\PrayerRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePrayerRequest;
use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PrayerRequestController extends Controller
{
    public function index(Request $request): View
    {
        $items = PrayerRequest::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('reference_number', 'like', '%'.$request->search.'%')->orWhere('prayer_content', 'like', '%'.$request->search.'%')))->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->when($request->filled('prayer_category'), fn ($q) => $q->where('prayer_category', $request->prayer_category))->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))->latest()->paginate(15)->withQueryString();

        return view('admin.resources.index', ['title' => 'Prayer Request', 'routeBase' => 'admin.prayer-requests', 'items' => $items, 'columns' => ['reference' => 'Referensi', 'name' => 'Nama', 'category' => 'Kategori', 'status' => 'Status', 'confidential' => 'Rahasia'], 'rows' => $items->through(fn ($i) => ['id' => $i->id, 'reference' => $i->reference_number, 'name' => $i->is_anonymous ? 'Anonim' : $i->name, 'category' => $i->prayer_category->label(), 'status' => $i->status->label(), 'confidential' => $i->is_confidential ? 'Ya' : 'Tidak']), 'filters' => [['name' => 'status', 'label' => 'Status', 'options' => PrayerRequestStatus::options()], ['name' => 'prayer_category', 'label' => 'Kategori', 'options' => PrayerRequestCategory::options()], ['name' => 'source', 'label' => 'Sumber', 'options' => PrayerRequestSource::options()]], 'bulkRoute' => 'admin.prayer-requests.bulk-status', 'bulkOptions' => PrayerRequestStatus::options()]);
    }

    public function show(Request $request, PrayerRequest $prayerRequest): View
    {
        abort_if($prayerRequest->is_confidential && ! $request->user()->can('prayer_requests.view_confidential'), 403);

        return view('admin.prayer-requests.show', ['item' => $prayerRequest, 'admins' => User::query()->where('is_active', true)->pluck('name', 'id')]);
    }

    public function update(UpdatePrayerRequest $request, PrayerRequest $prayerRequest): RedirectResponse
    {
        $prayerRequest->update($request->validated() + ['handled_at' => $request->filled('handled_by') ? now() : null]);

        return back()->with('success', 'Prayer request berhasil diperbarui.');
    }

    public function destroy(PrayerRequest $prayerRequest): RedirectResponse
    {
        $prayerRequest->delete();

        return redirect()->route('admin.prayer-requests.index')->with('success', 'Prayer request berhasil dihapus.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer', 'exists:prayer_requests,id'], 'status' => ['required', Rule::enum(PrayerRequestStatus::class)]]);
        PrayerRequest::query()->whereKey($validated['ids'])->update(['status' => $validated['status'], 'updated_at' => now()]);
        activity('prayer_requests')->causedBy($request->user())->event('bulk_status_updated')->withProperties(['ids' => $validated['ids'], 'status' => $validated['status']])->log('Status prayer request diperbarui secara massal');

        return back()->with('success', count($validated['ids']).' prayer request berhasil diperbarui.');
    }

    public function export(): StreamedResponse
    {
        abort_unless(request()->user()->can('prayer_requests.export'), 403);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Referensi', 'Nama', 'Kategori', 'Status', 'Sumber', 'Tanggal']);
            PrayerRequest::query()->orderBy('id')->chunk(500, fn ($rows) => $rows->each(fn ($i) => fputcsv($out, [$i->reference_number, $i->is_anonymous ? 'Anonim' : $i->name, $i->prayer_category->label(), $i->status->label(), $i->source->label(), $i->created_at])));
            fclose($out);
        }, 'prayer-request-'.now()->format('Ymd').'.csv');
    }
}
