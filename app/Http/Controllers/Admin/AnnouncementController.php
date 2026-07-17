<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $items = Announcement::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$request->search.'%')->orWhere('description', 'like', '%'.$request->search.'%')))->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->latest()->paginate(15)->withQueryString();

        return view('admin.resources.index', ['title' => 'Pengumuman', 'routeBase' => 'admin.announcements', 'createPermission' => 'announcements.create', 'items' => $items, 'columns' => ['title' => 'Judul', 'status' => 'Status', 'published' => 'Publikasi', 'featured' => 'Featured'], 'rows' => $items->through(fn ($i) => ['id' => $i->id, 'title' => $i->title, 'status' => $i->status->label(), 'published' => $i->published_at?->format('d M Y H:i') ?? '-', 'featured' => $i->is_featured ? 'Ya' : 'Tidak']), 'filters' => [['name' => 'status', 'label' => 'Status', 'options' => AnnouncementStatus::options()]]]);
    }

    public function create(): View
    {
        return $this->form(new Announcement, 'Tambah Pengumuman');
    }

    public function store(StoreAnnouncementRequest $request, ImageUploadService $uploads): RedirectResponse
    {
        $data = $this->data($request, new Announcement, $uploads) + ['created_by' => $request->user()->id];
        $item = Announcement::query()->create($data);

        return redirect()->route('admin.announcements.show', $item)->with('success', 'Pengumuman berhasil ditambahkan.');
    }

    public function show(Announcement $announcement): View
    {
        return view('admin.resources.show', ['title' => 'Detail Pengumuman', 'routeBase' => 'admin.announcements', 'item' => $announcement, 'details' => ['Judul' => $announcement->title, 'Status' => $announcement->status->label(), 'Tanggal publikasi' => $announcement->published_at?->format('d M Y H:i'), 'Tanggal berakhir' => $announcement->expired_at?->format('d M Y H:i'), 'PIC' => $announcement->contact_person_name, 'Kontak' => $announcement->contact_person_phone, 'Deskripsi' => $announcement->description], 'publishRoute' => 'admin.announcements.publish', 'publishPermission' => 'announcements.publish', 'publishLabel' => $announcement->status === AnnouncementStatus::Published ? 'Batalkan publikasi' : 'Publikasikan']);
    }

    public function edit(Announcement $announcement): View
    {
        return $this->form($announcement, 'Edit Pengumuman');
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement, ImageUploadService $uploads): RedirectResponse
    {
        $announcement->update($this->data($request, $announcement, $uploads));

        return redirect()->route('admin.announcements.show', $announcement)->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('success', 'Pengumuman berhasil dihapus.');
    }

    public function publish(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($request->user()->can('announcements.publish'), 403);
        $publishing = $announcement->status !== AnnouncementStatus::Published;
        $announcement->update(['status' => $publishing ? AnnouncementStatus::Published : AnnouncementStatus::Draft, 'published_at' => $publishing ? ($announcement->published_at ?? now()) : $announcement->published_at, 'updated_by' => $request->user()->id]);

        return back()->with('success', $publishing ? 'Pengumuman berhasil dipublikasikan.' : 'Publikasi pengumuman berhasil dibatalkan.');
    }

    private function data(Request $request, Announcement $item, ImageUploadService $uploads): array
    {
        $data = array_merge($request->safe()->except('image'), ['slug' => $this->slug($request->string('title')->toString(), $item->id), 'is_featured' => $request->boolean('is_featured'), 'sort_order' => $request->integer('sort_order'), 'updated_by' => $request->user()->id]);
        if ($request->hasFile('image')) {
            $data['image'] = $uploads->store($request->file('image'), 'announcements', $item->image);
        }

        return $data;
    }

    private function slug(string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;
        while (Announcement::withTrashed()->where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function form(Announcement $item, string $title): View
    {
        return view('admin.resources.form', ['title' => $title, 'routeBase' => 'admin.announcements', 'item' => $item, 'fields' => [['name' => 'title', 'label' => 'Judul', 'required' => true], ['name' => 'excerpt', 'label' => 'Ringkasan', 'type' => 'textarea'], ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'required' => true], ['name' => 'image', 'label' => 'Gambar', 'type' => 'file'], ['name' => 'contact_person_name', 'label' => 'Nama PIC'], ['name' => 'contact_person_phone', 'label' => 'Kontak PIC'], ['name' => 'information_url', 'label' => 'URL informasi', 'type' => 'url'], ['name' => 'published_at', 'label' => 'Tanggal publikasi', 'type' => 'datetime-local'], ['name' => 'expired_at', 'label' => 'Tanggal berakhir', 'type' => 'datetime-local'], ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => AnnouncementStatus::options()], ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number'], ['name' => 'is_featured', 'label' => 'Featured', 'type' => 'checkbox']]]);
    }
}
