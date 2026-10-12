<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveDiscipleshipClassRequest;
use App\Models\Congregation;
use App\Models\DiscipleshipClass;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class DiscipleshipClassController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:active,completed']]);
        $classes = DiscipleshipClass::query()->withCount('participants')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('teacher_name', 'like', '%'.$request->string('search').'%')))
            ->when($request->status === 'active', fn ($query) => $query->whereNull('completed_at'))
            ->when($request->status === 'completed', fn ($query) => $query->whereNotNull('completed_at'))
            ->latest('starts_at')->paginate(12)->withQueryString();

        return view('admin.discipleship.index', compact('classes'));
    }

    public function create(Request $request): View
    {
        return $this->form($request, new DiscipleshipClass, 'Buat Kelas');
    }

    public function store(SaveDiscipleshipClassRequest $request, ImageUploadService $uploads): RedirectResponse
    {
        $class = $this->save($request, new DiscipleshipClass, $uploads);

        return redirect()->route('admin.discipleship.show', $class)->with('success', 'Kelas pemuridan berhasil dibuat.');
    }

    public function show(Request $request, DiscipleshipClass $discipleshipClass): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $discipleshipClass->loadCount('participants');
        $participants = $discipleshipClass->participants()
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->where('full_name', 'like', '%'.$request->string('search').'%')
                ->orWhere('member_number', 'like', '%'.$request->string('search').'%')))
            ->orderBy('full_name')->paginate(20)->withQueryString();

        return view('admin.discipleship.show', ['class' => $discipleshipClass, 'participants' => $participants]);
    }

    public function edit(Request $request, DiscipleshipClass $discipleshipClass): View|RedirectResponse
    {
        if ($discipleshipClass->completed_at) {
            return redirect()->route('admin.discipleship.show', $discipleshipClass)->with('success', 'Kelas sudah selesai dan riwayat kelulusan telah dikunci.');
        }

        return $this->form($request, $discipleshipClass, 'Edit Kelas');
    }

    public function update(SaveDiscipleshipClassRequest $request, DiscipleshipClass $discipleshipClass, ImageUploadService $uploads): RedirectResponse
    {
        $this->save($request, $discipleshipClass, $uploads);

        return redirect()->route('admin.discipleship.show', $discipleshipClass)->with('success', 'Kelas dan daftar peserta berhasil diperbarui.');
    }

    public function complete(Request $request, DiscipleshipClass $discipleshipClass): RedirectResponse
    {
        $completed = DB::transaction(function () use ($request, $discipleshipClass): bool {
            $class = DiscipleshipClass::query()->lockForUpdate()->findOrFail($discipleshipClass->id);
            if ($class->completed_at) {
                return false;
            }

            $enrollments = DB::table('discipleship_enrollments')->where('discipleship_class_id', $class->id);
            if (! $enrollments->exists()) {
                throw ValidationException::withMessages(['participants' => 'Tambahkan peserta sebelum menyelesaikan kelas.']);
            }
            $completedAt = now();
            $class->update(['completed_at' => $completedAt, 'completed_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $enrollments->update(['completed_at' => $completedAt, 'updated_at' => $completedAt]);

            return true;
        });

        return redirect()->route('admin.discipleship.show', $discipleshipClass)->with('success', $completed
            ? 'Kelas berhasil diselesaikan. Seluruh peserta telah mendapatkan badge kelulusan.'
            : 'Kelas ini sudah selesai. Badge kelulusan sudah diberikan sebelumnya.');
    }

    public function members(Request $request): JsonResponse
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'page' => ['nullable', 'integer', 'min:1']]);
        $members = Congregation::query()->select(['id', 'full_name', 'member_number', 'profile_photo', 'legacy_profile_photo_url', 'legacy_firebase_uid'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->where('full_name', 'like', '%'.$request->string('search').'%')
                ->orWhere('member_number', 'like', '%'.$request->string('search').'%')))
            ->orderBy('full_name')->orderBy('id')->paginate(20);

        return response()->json(['data' => $members->getCollection()->map(fn (Congregation $member): array => $this->participantOption($member)), 'next_page' => $members->hasMorePages() ? $members->currentPage() + 1 : null]);
    }

    private function form(Request $request, DiscipleshipClass $class, string $title): View
    {
        $ids = $request->old('participant_ids', $class->exists ? $class->participants()->pluck('congregations.id')->all() : []);
        // Old input may be malformed after validation fails; only resolve integer IDs.
        $ids = collect(is_array($ids) ? $ids : [])->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)->all();
        $selectedParticipants = Congregation::query()->whereIn('id', $ids)->orderBy('full_name')
            ->get(['id', 'full_name', 'member_number', 'profile_photo', 'legacy_profile_photo_url', 'legacy_firebase_uid'])
            ->map(fn (Congregation $member): array => $this->participantOption($member));

        return view('admin.discipleship.form', compact('class', 'title', 'selectedParticipants'));
    }

    private function participantOption(Congregation $member): array
    {
        return ['id' => $member->id, 'full_name' => $member->full_name, 'member_number' => $member->member_number, 'profile_photo_url' => $member->profilePhotoUrl()];
    }

    private function save(SaveDiscipleshipClassRequest $request, DiscipleshipClass $class, ImageUploadService $uploads): DiscipleshipClass
    {
        $newPaths = [];
        $oldPaths = [];
        try {
            $saved = DB::transaction(function () use ($request, $class, $uploads, &$newPaths, &$oldPaths): DiscipleshipClass {
                if ($class->exists) {
                    // Serialize edits and completion so a late edit cannot change awarded badges.
                    $class = DiscipleshipClass::query()->lockForUpdate()->findOrFail($class->id);
                    if ($class->completed_at) {
                        throw ValidationException::withMessages(['class' => 'Kelas sudah selesai. Data dan peserta kelas tidak dapat diubah.']);
                    }
                }
                $data = $request->safe()->except(['banner', 'badge', 'participant_ids']);
                $data['updated_by'] = $request->user()->id;
                if (! $class->exists) {
                    $data['created_by'] = $request->user()->id;
                }
                foreach (['banner', 'badge'] as $field) {
                    if ($request->hasFile($field)) {
                        $data[$field] = $uploads->store($request->file($field), 'discipleship/'.$field);
                        $newPaths[] = $data[$field];
                        $oldPaths[] = $class->{$field};
                    }
                }
                $class->fill($data)->save();
                $changes = $class->participants()->sync($request->validated('participant_ids'));
                if ($changes['attached'] || $changes['detached']) {
                    activity()->performedOn($class)->causedBy($request->user())->withProperties($changes)->log('Peserta kelas pemuridan diperbarui');
                }

                return $class;
            });
        } catch (Throwable $exception) {
            foreach ($newPaths as $path) {
                $uploads->delete($path);
            }
            throw $exception;
        }
        foreach ($oldPaths as $path) {
            $uploads->delete($path);
        }

        return $saved;
    }
}
