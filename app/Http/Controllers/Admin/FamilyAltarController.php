<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFamilyAltarRequest;
use App\Http\Requests\Admin\UpdateFamilyAltarRequest;
use App\Models\FamilyAltar;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FamilyAltarController extends Controller
{
    public function index(Request $request): View
    {
        $items = FamilyAltar::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('pic_name', 'like', '%'.$request->search.'%')->orWhere('description', 'like', '%'.$request->search.'%')))->when($request->filled('day_of_week'), fn ($q) => $q->where('day_of_week', $request->day_of_week))->orderBy('sort_order')->paginate(15)->withQueryString();

        return view('admin.resources.index', ['title' => 'Mezbah Keluarga', 'routeBase' => 'admin.family-altars', 'createPermission' => 'family_altars.create', 'items' => $items, 'columns' => ['name' => 'Lokasi', 'day' => 'Hari', 'time' => 'Waktu', 'pic' => 'PIC', 'active' => 'Aktif'], 'rows' => $items->through(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'day' => $i->day_of_week->label(), 'time' => $i->start_time ?? '-', 'pic' => $i->pic_name ?? '-', 'active' => $i->is_active ? 'Ya' : 'Tidak']), 'filters' => [['name' => 'day_of_week', 'label' => 'Hari', 'options' => DayOfWeek::options()]]]);
    }

    public function create(): View
    {
        return $this->form(new FamilyAltar, 'Tambah Mezbah Keluarga');
    }

    public function store(StoreFamilyAltarRequest $request, ImageUploadService $uploads): RedirectResponse
    {
        $data = $this->data($request, new FamilyAltar, $uploads) + ['created_by' => $request->user()->id];
        $item = FamilyAltar::query()->create($data);

        return redirect()->route('admin.family-altars.show', $item)->with('success', 'Mezbah Keluarga berhasil ditambahkan.');
    }

    public function show(FamilyAltar $familyAltar): View
    {
        return view('admin.resources.show', ['title' => 'Detail Mezbah Keluarga', 'routeBase' => 'admin.family-altars', 'item' => $familyAltar, 'details' => ['Nama' => $familyAltar->name, 'Hari' => $familyAltar->day_of_week->label(), 'Waktu' => trim(($familyAltar->start_time ?? '').' - '.($familyAltar->end_time ?? ''), ' -'), 'Lokasi' => $familyAltar->location_name, 'Alamat' => $familyAltar->address, 'Kota' => $familyAltar->city, 'PIC' => $familyAltar->pic_name, 'Kontak' => $familyAltar->contact_phone, 'Deskripsi' => $familyAltar->description]]);
    }

    public function edit(FamilyAltar $familyAltar): View
    {
        return $this->form($familyAltar, 'Edit Mezbah Keluarga');
    }

    public function update(UpdateFamilyAltarRequest $request, FamilyAltar $familyAltar, ImageUploadService $uploads): RedirectResponse
    {
        $familyAltar->update($this->data($request, $familyAltar, $uploads));

        return redirect()->route('admin.family-altars.show', $familyAltar)->with('success', 'Mezbah Keluarga berhasil diperbarui.');
    }

    public function destroy(FamilyAltar $familyAltar): RedirectResponse
    {
        $familyAltar->delete();

        return redirect()->route('admin.family-altars.index')->with('success', 'Mezbah Keluarga berhasil dihapus.');
    }

    private function data(Request $request, FamilyAltar $item, ImageUploadService $uploads): array
    {
        $data = array_merge($request->safe()->except('image'), ['is_active' => $request->boolean('is_active'), 'sort_order' => $request->integer('sort_order'), 'updated_by' => $request->user()->id]);
        if ($request->hasFile('image')) {
            $data['image'] = $uploads->store($request->file('image'), 'family-altars', $item->image);
        }

        return $data;
    }

    private function form(FamilyAltar $item, string $title): View
    {
        return view('admin.resources.form', ['title' => $title, 'routeBase' => 'admin.family-altars', 'item' => $item, 'fields' => [['name' => 'name', 'label' => 'Nama/lokasi', 'required' => true], ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'], ['name' => 'day_of_week', 'label' => 'Hari', 'type' => 'select', 'options' => DayOfWeek::options()], ['name' => 'start_time', 'label' => 'Mulai', 'type' => 'time'], ['name' => 'end_time', 'label' => 'Selesai', 'type' => 'time'], ['name' => 'location_name', 'label' => 'Nama lokasi'], ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'], ['name' => 'city', 'label' => 'Kota'], ['name' => 'pic_name', 'label' => 'PIC'], ['name' => 'contact_phone', 'label' => 'Kontak'], ['name' => 'latitude', 'label' => 'Latitude', 'type' => 'number', 'step' => 'any'], ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'number', 'step' => 'any'], ['name' => 'map_url', 'label' => 'URL peta', 'type' => 'url'], ['name' => 'image', 'label' => 'Gambar', 'type' => 'file'], ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number'], ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'value' => $item->exists ? $item->is_active : true]]]);
    }
}
