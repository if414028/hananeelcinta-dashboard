<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Models\Event;
use App\Services\ImageUploadService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class EventController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::query()->withCount('registrations')
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest('starts_at')->paginate(15)->withQueryString();

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new Event, 'title' => 'Buat Event']);
    }

    public function store(StoreEventRequest $request, ImageUploadService $uploads): RedirectResponse
    {
        $event = Event::query()->create($this->data($request, new Event, $uploads) + ['created_by' => $request->user()->id]);

        return redirect()->route('admin.events.show', $event)->with('success', 'Event berhasil dibuat. Link pendaftaran siap dibagikan.');
    }

    public function show(Event $event, QrCodeService $qr): View
    {
        $event->loadCount('registrations')->loadCount(['registrations as checked_in_count' => fn ($query) => $query->whereNotNull('checked_in_at')]);
        $registrationUrl = route('events.register', $event->slug);

        return view('admin.events.show', ['event' => $event, 'registrationUrl' => $registrationUrl, 'registrationQr' => $qr->dataUri($registrationUrl)]);
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', ['event' => $event, 'title' => 'Edit Event']);
    }

    public function update(StoreEventRequest $request, Event $event, ImageUploadService $uploads): RedirectResponse
    {
        $event->update($this->data($request, $event, $uploads));

        return redirect()->route('admin.events.show', $event)->with('success', 'Event berhasil diperbarui.');
    }

    public function destroy(Event $event, ImageUploadService $uploads): RedirectResponse
    {
        $uploads->delete($event->image);
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Event berhasil dihapus.');
    }

    private function data(StoreEventRequest $request, Event $event, ImageUploadService $uploads): array
    {
        $data = [
            'title' => $request->string('title')->toString(),
            'slug' => $this->slug($request->string('title')->toString(), $event->id),
            'description' => $request->string('description')->toString(),
            'starts_at' => $request->date('starts_at'),
            'ends_at' => $request->filled('ends_at') ? $request->date('ends_at') : null,
            'is_published' => $request->boolean('is_published'),
            'registration_fields' => collect($request->validated('registration_fields'))->map(fn (array $field): array => [
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'required' => $field['key'] === 'name' || (bool) ($field['required'] ?? false),
                'options' => array_values(array_filter($field['options'] ?? [])),
            ])->values()->all(),
            'updated_by' => $request->user()->id,
        ];
        if ($request->hasFile('image')) {
            $data['image'] = $uploads->store($request->file('image'), 'events', $event->image);
        }

        return $data;
    }

    private function slug(string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $counter = 2;
        while (Event::withTrashed()->where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
