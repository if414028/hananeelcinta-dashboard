<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PastorMessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePastorMessageRequest;
use App\Http\Requests\Admin\UpdatePastorMessageRequest;
use App\Models\PastorMessage;
use App\Services\HtmlSanitizer;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class PastorMessageController extends Controller
{
    public function index(Request $request): View
    {
        $items = PastorMessage::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$request->search.'%')->orWhere('writer', 'like', '%'.$request->search.'%')->orWhere('content', 'like', '%'.$request->search.'%')))->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->latest()->paginate(15)->withQueryString();

        return view('admin.resources.index', ['title' => 'Pastor Message', 'routeBase' => 'admin.pastor-messages', 'createPermission' => 'pastor_messages.create', 'items' => $items, 'columns' => ['title' => 'Judul', 'writer' => 'Penulis', 'status' => 'Status', 'published' => 'Publikasi', 'views' => 'Views'], 'rows' => $items->through(fn ($i) => ['id' => $i->id, 'title' => $i->title, 'writer' => $i->writer, 'status' => $i->status->label(), 'published' => $i->published_at?->format('d M Y') ?? '-', 'views' => number_format($i->view_count)]), 'filters' => [['name' => 'status', 'label' => 'Status', 'options' => PastorMessageStatus::options()]]]);
    }

    public function create(): View
    {
        return $this->form(new PastorMessage, 'Tambah Pastor Message');
    }

    public function store(StorePastorMessageRequest $request, ImageUploadService $uploads, HtmlSanitizer $sanitizer): RedirectResponse
    {
        $data = $this->data($request, new PastorMessage, $uploads, $sanitizer) + ['created_by' => $request->user()->id];
        $item = PastorMessage::query()->create($data);

        return redirect()->route('admin.pastor-messages.show', $item)->with('success', 'Pastor Message berhasil ditambahkan.');
    }

    public function show(PastorMessage $pastorMessage): View
    {
        return view('admin.resources.show', ['title' => 'Detail Pastor Message', 'routeBase' => 'admin.pastor-messages', 'item' => $pastorMessage, 'details' => ['Judul' => $pastorMessage->title, 'Penulis' => $pastorMessage->writer, 'Status' => $pastorMessage->status->label(), 'Publikasi' => $pastorMessage->published_at?->format('d M Y H:i'), 'Featured' => $pastorMessage->is_featured ? 'Ya' : 'Tidak', 'Ringkasan' => $pastorMessage->excerpt, 'Konten' => strip_tags($pastorMessage->content)], 'publishRoute' => 'admin.pastor-messages.publish', 'publishPermission' => 'pastor_messages.publish', 'publishLabel' => $pastorMessage->status === PastorMessageStatus::Published ? 'Batalkan publikasi' : 'Publikasikan']);
    }

    public function edit(PastorMessage $pastorMessage): View
    {
        return $this->form($pastorMessage, 'Edit Pastor Message');
    }

    public function update(UpdatePastorMessageRequest $request, PastorMessage $pastorMessage, ImageUploadService $uploads, HtmlSanitizer $sanitizer): RedirectResponse
    {
        $pastorMessage->update($this->data($request, $pastorMessage, $uploads, $sanitizer));

        return redirect()->route('admin.pastor-messages.show', $pastorMessage)->with('success', 'Pastor Message berhasil diperbarui.');
    }

    public function destroy(PastorMessage $pastorMessage): RedirectResponse
    {
        $pastorMessage->delete();

        return redirect()->route('admin.pastor-messages.index')->with('success', 'Pastor Message berhasil dihapus.');
    }

    public function publish(Request $request, PastorMessage $pastorMessage): RedirectResponse
    {
        abort_unless($request->user()->can('pastor_messages.publish'), 403);
        $publishing = $pastorMessage->status !== PastorMessageStatus::Published;
        $pastorMessage->update(['status' => $publishing ? PastorMessageStatus::Published : PastorMessageStatus::Draft, 'published_at' => $publishing ? ($pastorMessage->published_at ?? now()) : $pastorMessage->published_at, 'updated_by' => $request->user()->id]);

        return back()->with('success', $publishing ? 'Pastor Message berhasil dipublikasikan.' : 'Publikasi Pastor Message berhasil dibatalkan.');
    }

    private function data(Request $request, PastorMessage $item, ImageUploadService $uploads, HtmlSanitizer $sanitizer): array
    {
        $data = array_merge($request->safe()->except('featured_image'), ['slug' => $this->slug($request->string('title')->toString(), $item->id), 'content' => $sanitizer->sanitize($request->string('content')->toString()), 'excerpt' => $request->filled('excerpt') ? $request->excerpt : Str::limit(strip_tags($request->content), 240), 'is_featured' => $request->boolean('is_featured'), 'updated_by' => $request->user()->id]);
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $uploads->store($request->file('featured_image'), 'pastor-messages', $item->featured_image);
        }

        return $data;
    }

    private function slug(string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;
        while (PastorMessage::withTrashed()->where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function form(PastorMessage $item, string $title): View
    {
        return view('admin.resources.form', ['title' => $title, 'routeBase' => 'admin.pastor-messages', 'item' => $item, 'fields' => [['name' => 'title', 'label' => 'Judul', 'required' => true], ['name' => 'writer', 'label' => 'Penulis', 'required' => true], ['name' => 'content', 'label' => 'Konten', 'type' => 'textarea', 'required' => true], ['name' => 'excerpt', 'label' => 'Ringkasan', 'type' => 'textarea'], ['name' => 'featured_image', 'label' => 'Featured image', 'type' => 'file'], ['name' => 'published_at', 'label' => 'Tanggal publikasi', 'type' => 'datetime-local'], ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => PastorMessageStatus::options()], ['name' => 'is_featured', 'label' => 'Featured', 'type' => 'checkbox']]]);
    }
}
