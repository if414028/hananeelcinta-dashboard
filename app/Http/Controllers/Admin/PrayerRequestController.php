<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePrayerRequest;
use App\Models\PrayerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PrayerRequestController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $search = trim((string) $request->query('search', ''));
        $query = PrayerRequest::query()
            ->with('handler:id,name')
            ->when(! $request->user()->can('prayer_requests.view_confidential'), fn ($query) => $query->where('is_confidential', false))
            ->when($search !== '', function ($query) use ($search, $userId): void {
                $query->where(function ($query) use ($search, $userId): void {
                    $query->where('reference_number', 'like', '%'.$search.'%')
                        ->orWhere(function ($query) use ($search, $userId): void {
                            $query->where(function ($query) use ($userId): void {
                                $query->whereNull('handled_by')->orWhere('handled_by', $userId);
                            })->where(function ($query) use ($search): void {
                                $query->where('name', 'like', '%'.$search.'%')
                                    ->orWhere('prayer_content', 'like', '%'.$search.'%');
                            });
                        });
                });
            })
            ->when($request->filled('prayer_category'), fn ($query) => $query->where('prayer_category', $request->prayer_category));

        $columns = collect(PrayerRequestStatus::cases())->mapWithKeys(
            fn (PrayerRequestStatus $status) => [
                $status->value => (clone $query)->where('status', $status)->latest()
                    ->get(),
            ]
        );

        return view('admin.prayer-requests.index', [
            'columns' => $columns,
            'statuses' => PrayerRequestStatus::cases(),
            'filters' => [
                ['name' => 'prayer_category', 'label' => 'Kategori', 'options' => PrayerRequestCategory::options()],
            ],
        ]);
    }

    public function show(Request $request, PrayerRequest $prayerRequest): JsonResponse|RedirectResponse
    {
        $this->assertCanView($request, $prayerRequest);

        if (! $request->expectsJson()) {
            return redirect()->route('admin.prayer-requests.index', ['prayer' => $prayerRequest->id]);
        }

        $prayerRequest->load('handler:id,name');

        return response()->json(['data' => [
            'reference' => $prayerRequest->reference_number,
            'name' => $prayerRequest->is_anonymous ? 'Anonim' : $prayerRequest->name,
            'category' => $prayerRequest->prayer_category->label(),
            'request_type' => $prayerRequest->request_type ?? $prayerRequest->prayer_category->label(),
            'content' => $prayerRequest->prayer_content,
            'email' => $prayerRequest->email,
            'phone' => $prayerRequest->phone_number,
            'submitted_at' => $prayerRequest->created_at->format('d M Y H:i'),
            'status' => $prayerRequest->status->label(),
            'handler' => $prayerRequest->legacy_handler_name ?: $prayerRequest->handler?->name,
            'prayer_result' => $prayerRequest->prayer_result,
            'update_url' => route('admin.prayer-requests.update', $prayerRequest),
            'can_update' => $prayerRequest->handled_by !== null
                && (int) $prayerRequest->handled_by === $request->user()->id
                && $request->user()->can('prayer_requests.update'),
        ]]);
    }

    public function move(Request $request, PrayerRequest $prayerRequest): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(PrayerRequestStatus::class)],
            'expected_status' => ['required', Rule::enum(PrayerRequestStatus::class)],
            'prayer_result' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $prayerRequest, $validated): void {
            $item = PrayerRequest::query()->lockForUpdate()->findOrFail($prayerRequest->id);
            $this->assertConfidentialAccess($request, $item);
            abort_if($item->handled_by !== null && (int) $item->handled_by !== $request->user()->id, 403, 'Prayer request ini sudah ditangani pengguna lain.');
            abort_if($item->status->value !== $validated['expected_status'], 409, 'Status sudah berubah. Muat ulang papan.');

            $nextStatus = PrayerRequestStatus::from($validated['status']);
            abort_unless(in_array($nextStatus, $item->status->nextStatuses(), true), 422, 'Perpindahan status ini tidak tersedia.');

            if ($nextStatus === PrayerRequestStatus::Closed) {
                $prayerResult = trim((string) ($validated['prayer_result'] ?? ''));
                abort_if($prayerResult === '', 422, 'Isi hasil doa sebelum memindahkan permohonan ke Selesai.');
                $item->prayer_result = $prayerResult;
            }

            if ($item->handled_by === null) {
                $item->handled_by = $request->user()->id;
                $item->handled_at = now();
            }
            $item->status = $nextStatus;
            $item->save();
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => $validated['status'] === PrayerRequestStatus::Closed->value
                ? 'Hasil doa tersimpan dan permohonan selesai.'
                : 'Status prayer request diperbarui.']);
        }

        return back()->with('success', $validated['status'] === PrayerRequestStatus::Closed->value
            ? 'Hasil doa tersimpan dan permohonan selesai.'
            : 'Status prayer request diperbarui.');
    }

    public function update(UpdatePrayerRequest $request, PrayerRequest $prayerRequest): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($request, $prayerRequest): void {
            $item = PrayerRequest::query()->lockForUpdate()->findOrFail($prayerRequest->id);
            $this->assertCanHandle($request, $item);
            $item->update($request->validated());
        });

        activity('prayer_requests')->causedBy($request->user())->performedOn($prayerRequest)->event('result_updated')->log('Hasil doa prayer request diperbarui');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Hasil doa tersimpan.']);
        }

        return redirect()->route('admin.prayer-requests.index', ['prayer' => $prayerRequest->id])->with('success', 'Hasil doa tersimpan.');
    }

    public function destroy(Request $request, PrayerRequest $prayerRequest): RedirectResponse
    {
        DB::transaction(function () use ($request, $prayerRequest): void {
            $item = PrayerRequest::query()->lockForUpdate()->findOrFail($prayerRequest->id);
            $this->assertCanHandle($request, $item);
            $item->delete();
        });

        return redirect()->route('admin.prayer-requests.index')->with('success', 'Prayer request berhasil dihapus.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('prayer_requests.view'), 403);
        $userId = $request->user()->id;
        $canViewConfidential = $request->user()->can('prayer_requests.view_confidential');

        return response()->streamDownload(function () use ($userId, $canViewConfidential): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Referensi', 'Nama', 'Kategori', 'Status', 'Sumber', 'Tanggal']);
            PrayerRequest::query()->where('handled_by', $userId)
                ->when(! $canViewConfidential, fn ($query) => $query->where('is_confidential', false))
                ->orderBy('id')->chunk(500, fn ($rows) => $rows->each(fn ($item) => fputcsv($out, [
                    $item->reference_number,
                    $item->is_anonymous ? 'Anonim' : $item->name,
                    $item->prayer_category->label(),
                    $item->status->label(),
                    $item->source->label(),
                    $item->created_at,
                ])));
            fclose($out);
        }, 'prayer-request-'.now()->format('Ymd').'.csv');
    }

    private function assertCanHandle(Request $request, PrayerRequest $item): void
    {
        $this->assertCanView($request, $item);
        abort_unless($item->handled_by !== null && (int) $item->handled_by === $request->user()->id, 403, 'Hanya yang mendoakan dapat mengubah prayer request ini.');
    }

    private function assertCanView(Request $request, PrayerRequest $item): void
    {
        $this->assertConfidentialAccess($request, $item);
        abort_if($item->handled_by !== null && (int) $item->handled_by !== $request->user()->id, 403, 'Prayer request ini sudah ditangani pengguna lain.');
    }

    private function assertConfidentialAccess(Request $request, PrayerRequest $item): void
    {
        abort_unless($request->user()->can('prayer_requests.view'), 403);
        abort_if($item->is_confidential && ! $request->user()->can('prayer_requests.view_confidential'), 403);
    }
}
