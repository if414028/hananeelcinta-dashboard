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
use Illuminate\Support\Str;
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
        $initials = Str::of($congregation->full_name)->explode(' ')->filter()->take(2)->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))->implode('');

        return view('admin.resources.show', [
            'title' => 'Detail Jemaat',
            'routeBase' => 'admin.congregations',
            'item' => $congregation,
            'profile' => [
                'name' => $congregation->full_name,
                'meta' => $congregation->member_number.' · '.$congregation->membership_status->label(),
                'status' => $congregation->is_active ? 'Aktif' : 'Tidak aktif',
                'initials' => $initials,
                'photo_url' => $congregation->profilePhotoUrl(),
            ],
            'detailSections' => [
                'Identitas' => [
                    ['label' => 'Nomor anggota', 'value' => $congregation->member_number],
                    ['label' => 'Nama lengkap', 'value' => $congregation->full_name],
                    ['label' => 'Nama panggilan', 'value' => $congregation->nickname],
                    ['label' => 'Gender', 'value' => $congregation->gender->label()],
                    ['label' => 'Tempat lahir', 'value' => $congregation->place_of_birth],
                    ['label' => 'Tanggal lahir', 'value' => $congregation->date_of_birth?->format('d M Y')],
                    ['label' => 'Status pernikahan', 'value' => $congregation->marital_status?->label()],
                    ['label' => 'Firebase UID', 'value' => $congregation->legacy_firebase_uid],
                ],
                'Kontak & Domisili' => [
                    ['label' => 'Email', 'value' => $congregation->email],
                    ['label' => 'Telepon', 'value' => $congregation->phone_number],
                    ['label' => 'WhatsApp', 'value' => $congregation->whatsapp_number],
                    ['label' => 'Alamat', 'value' => $congregation->address, 'wide' => true],
                    ['label' => 'Kota', 'value' => $congregation->city],
                    ['label' => 'Provinsi', 'value' => $congregation->province],
                    ['label' => 'Kode pos', 'value' => $congregation->postal_code],
                ],
                'Keanggotaan & Pelayanan' => [
                    ['label' => 'Pekerjaan', 'value' => $congregation->occupation],
                    ['label' => 'Status baptis', 'value' => $congregation->baptism_status->label()],
                    ['label' => 'Tanggal baptis', 'value' => $congregation->baptism_date?->format('d M Y')],
                    ['label' => 'Status keanggotaan', 'value' => $congregation->membership_status->label()],
                    ['label' => 'Tanggal bergabung', 'value' => $congregation->joined_at?->format('d M Y')],
                    ['label' => 'Status data', 'value' => $congregation->is_active ? 'Aktif' : 'Tidak aktif'],
                ],
                'Data Firebase' => $this->firebaseNoteDetails($congregation->notes),
            ],
        ]);
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
        return view('admin.resources.form', ['title' => $title, 'routeBase' => 'admin.congregations', 'item' => $item, 'fields' => [['name' => 'full_name', 'label' => 'Nama lengkap', 'required' => true], ['name' => 'nickname', 'label' => 'Nama panggilan'], ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => CongregationGender::options()], ['name' => 'place_of_birth', 'label' => 'Tempat lahir'], ['name' => 'date_of_birth', 'label' => 'Tanggal lahir', 'type' => 'date'], ['name' => 'marital_status', 'label' => 'Status pernikahan', 'type' => 'select', 'options' => CongregationMaritalStatus::options()], ['name' => 'phone_number', 'label' => 'Telepon'], ['name' => 'whatsapp_number', 'label' => 'WhatsApp'], ['name' => 'email', 'label' => 'Email', 'type' => 'email'], ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'], ['name' => 'city', 'label' => 'Kota'], ['name' => 'province', 'label' => 'Provinsi'], ['name' => 'postal_code', 'label' => 'Kode pos'], ['name' => 'occupation', 'label' => 'Pekerjaan'], ['name' => 'baptism_status', 'label' => 'Status baptis', 'type' => 'select', 'options' => BaptismStatus::options()], ['name' => 'baptism_date', 'label' => 'Tanggal baptis', 'type' => 'date'], ['name' => 'membership_status', 'label' => 'Status keanggotaan', 'type' => 'select', 'options' => CongregationMembershipStatus::options()], ['name' => 'joined_at', 'label' => 'Tanggal bergabung', 'type' => 'date'], ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'], ['name' => 'profile_photo', 'label' => 'Foto profil', 'type' => 'file', 'preview_url' => $item->exists ? $item->profilePhotoUrl() : null], ['name' => 'is_active', 'label' => 'Jemaat aktif', 'type' => 'checkbox', 'value' => $item->exists ? $item->is_active : true]]]);
    }

    /** @return list<array{label:string,value:?string,wide?:bool}> */
    private function firebaseNoteDetails(?string $notes): array
    {
        if (! $notes) {
            return [['label' => 'Catatan', 'value' => null, 'wide' => true]];
        }

        return collect(preg_split('/\R/u', $notes) ?: [])
            ->filter()
            ->map(function (string $line): array {
                [$label, $value] = array_pad(explode(':', $line, 2), 2, null);

                return ['label' => trim($label), 'value' => $value === null ? null : trim($value)];
            })
            ->values()
            ->all();
    }
}
