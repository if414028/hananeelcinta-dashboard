<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateMemberNumber;
use App\Enums\BaptismStatus;
use App\Enums\CongregationGender;
use App\Enums\CongregationMaritalStatus;
use App\Enums\CongregationMembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCongregationRequest;
use App\Http\Requests\Admin\UpdateCongregationRequest;
use App\Models\Congregation;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CongregationController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->sort, ['full_name', 'member_number', 'joined_at', 'created_at'], true) ? $request->sort : 'created_at';
        $direction = $request->direction === 'asc' ? 'asc' : 'desc';
        $items = Congregation::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('full_name', 'like', '%'.$request->search.'%')->orWhere('member_number', 'like', '%'.$request->search.'%')->orWhere('phone_number', 'like', '%'.$request->search.'%')->orWhere('whatsapp_number', 'like', '%'.$request->search.'%')->orWhere('email', 'like', '%'.$request->search.'%')))->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->gender))->when($request->filled('membership_status'), fn ($q) => $q->where('membership_status', $request->membership_status))->orderBy($sort, $direction)->paginate(15)->withQueryString();

        return view('admin.resources.index', ['title' => 'Data Jemaat', 'routeBase' => 'admin.congregations', 'createPermission' => 'congregations.create', 'exportPermission' => 'congregations.export', 'items' => $items, 'columns' => ['member_number' => 'No. Anggota', 'name' => 'Nama', 'gender' => 'Gender', 'membership' => 'Status', 'active' => 'Aktif'], 'rows' => $items->through(fn ($i) => ['id' => $i->id, 'member_number' => $i->member_number, 'name' => $i->full_name, 'gender' => $i->gender->label(), 'membership' => $i->membership_status->label(), 'active' => $i->is_active ? 'Ya' : 'Tidak']), 'filters' => [['name' => 'gender', 'label' => 'Gender', 'options' => CongregationGender::options()], ['name' => 'membership_status', 'label' => 'Status', 'options' => CongregationMembershipStatus::options()]]]);
    }

    public function create(): View
    {
        return $this->form(new Congregation, 'Tambah Jemaat');
    }

    public function store(StoreCongregationRequest $request, GenerateMemberNumber $generator, ImageUploadService $uploads): RedirectResponse
    {
        $item = DB::transaction(function () use ($request, $generator, $uploads) {
            $data = array_merge($request->safe()->except('profile_photo'), ['member_number' => $generator->handle(), 'is_active' => $request->boolean('is_active'), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $uploads->store($request->file('profile_photo'), 'congregations');
            }

            return Congregation::query()->create($data);
        });

        return redirect()->route('admin.congregations.show', $item)->with('success', 'Data jemaat berhasil ditambahkan.');
    }

    public function show(Congregation $congregation): View
    {
        return view('admin.resources.show', ['title' => 'Detail Jemaat', 'routeBase' => 'admin.congregations', 'item' => $congregation, 'details' => ['Nomor anggota' => $congregation->member_number, 'Nama lengkap' => $congregation->full_name, 'Nama panggilan' => $congregation->nickname, 'Gender' => $congregation->gender->label(), 'Email' => $congregation->email, 'Telepon' => $congregation->phone_number, 'WhatsApp' => $congregation->whatsapp_number, 'Status keanggotaan' => $congregation->membership_status->label(), 'Tanggal bergabung' => $congregation->joined_at?->format('d M Y'), 'Alamat' => $congregation->address]]);
    }

    public function edit(Congregation $congregation): View
    {
        return $this->form($congregation, 'Edit Jemaat');
    }

    public function update(UpdateCongregationRequest $request, Congregation $congregation, ImageUploadService $uploads): RedirectResponse
    {
        $data = array_merge($request->safe()->except('profile_photo'), ['is_active' => $request->boolean('is_active'), 'updated_by' => $request->user()->id]);
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $uploads->store($request->file('profile_photo'), 'congregations', $congregation->profile_photo);
        } $congregation->update($data);

        return redirect()->route('admin.congregations.show', $congregation)->with('success', 'Data jemaat berhasil diperbarui.');
    }

    public function destroy(Congregation $congregation): RedirectResponse
    {
        $congregation->delete();

        return redirect()->route('admin.congregations.index')->with('success', 'Data jemaat berhasil dihapus.');
    }

    public function export(): StreamedResponse
    {
        abort_unless(request()->user()->can('congregations.export'), 403);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nomor Anggota', 'Nama', 'Gender', 'Telepon', 'WhatsApp', 'Email', 'Status']);
            Congregation::query()->orderBy('id')->chunk(500, fn ($rows) => $rows->each(fn ($i) => fputcsv($out, [$i->member_number, $i->full_name, $i->gender->label(), $i->phone_number, $i->whatsapp_number, $i->email, $i->membership_status->label()])));
            fclose($out);
        }, 'jemaat-'.now()->format('Ymd').'.csv');
    }

    private function form(Congregation $item, string $title): View
    {
        return view('admin.resources.form', ['title' => $title, 'routeBase' => 'admin.congregations', 'item' => $item, 'fields' => [['name' => 'full_name', 'label' => 'Nama lengkap', 'required' => true], ['name' => 'nickname', 'label' => 'Nama panggilan'], ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => CongregationGender::options()], ['name' => 'place_of_birth', 'label' => 'Tempat lahir'], ['name' => 'date_of_birth', 'label' => 'Tanggal lahir', 'type' => 'date'], ['name' => 'marital_status', 'label' => 'Status pernikahan', 'type' => 'select', 'options' => CongregationMaritalStatus::options()], ['name' => 'phone_number', 'label' => 'Telepon'], ['name' => 'whatsapp_number', 'label' => 'WhatsApp'], ['name' => 'email', 'label' => 'Email', 'type' => 'email'], ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'], ['name' => 'city', 'label' => 'Kota'], ['name' => 'province', 'label' => 'Provinsi'], ['name' => 'postal_code', 'label' => 'Kode pos'], ['name' => 'occupation', 'label' => 'Pekerjaan'], ['name' => 'baptism_status', 'label' => 'Status baptis', 'type' => 'select', 'options' => BaptismStatus::options()], ['name' => 'baptism_date', 'label' => 'Tanggal baptis', 'type' => 'date'], ['name' => 'membership_status', 'label' => 'Status keanggotaan', 'type' => 'select', 'options' => CongregationMembershipStatus::options()], ['name' => 'joined_at', 'label' => 'Tanggal bergabung', 'type' => 'date'], ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'], ['name' => 'profile_photo', 'label' => 'Foto profil', 'type' => 'file'], ['name' => 'is_active', 'label' => 'Jemaat aktif', 'type' => 'checkbox', 'value' => $item->exists ? $item->is_active : true]]]);
    }
}
