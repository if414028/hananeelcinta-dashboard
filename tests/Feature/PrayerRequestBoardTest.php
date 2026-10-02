<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PrayerRequestStatus;
use App\Models\PrayerRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PrayerRequestBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_open_request_can_be_read_without_assignment_then_dragged_to_start_praying(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $pastor = User::factory()->create();
        $pastor->assignRole('Pastor');
        $prayer = PrayerRequest::factory()->create(['name' => 'Pemohon Khusus']);

        $this->actingAs($admin)->get(route('admin.prayer-requests.index'))
            ->assertOk()->assertSee('Papan Prayer Request')->assertSee('Pemohon Khusus')
            ->assertSee('Lihat detail '.$prayer->reference_number)->assertSee('<dialog', false)
            ->assertSee('Geser papan ke kiri')->assertSee('Geser papan ke kanan')
            ->assertDontSee('Ambil &amp; doakan', false)->assertDontSee('Lihat detail <', false)
            ->assertDontSee('bulk action')->assertDontSee('>Pindah<', false);
        $this->actingAs($pastor)->get(route('admin.prayer-requests.index'))->assertOk();
        $this->actingAs($pastor)->getJson(route('admin.prayer-requests.show', $prayer))
            ->assertOk()->assertJsonPath('data.handler', null)->assertJsonPath('data.can_update', false);
        $this->assertNull($prayer->fresh()->handled_by);

        $this->actingAs($pastor)->patchJson(route('admin.prayer-requests.move', $prayer), [
            'status' => 'in_prayer', 'expected_status' => 'open',
        ])->assertOk();

        $prayer->refresh();
        $this->assertSame($pastor->id, (int) $prayer->handled_by);
        $this->assertSame(PrayerRequestStatus::InPrayer, $prayer->status);
        $this->assertNotNull($prayer->handled_at);
        $this->actingAs($pastor)->getJson(route('admin.prayer-requests.show', $prayer))
            ->assertOk()->assertJsonPath('data.reference', $prayer->reference_number)
            ->assertJsonPath('data.handler', $pastor->name)
            ->assertJsonPath('data.status', 'Sedang Didoakan');
    }

    public function test_all_open_cards_are_rendered_without_pagination(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $prayers = PrayerRequest::factory()->count(20)->create(['is_confidential' => false]);

        $response = $this->actingAs($admin)->get(route('admin.prayer-requests.index'))->assertOk();
        foreach ($prayers as $prayer) {
            $response->assertSee($prayer->reference_number);
        }
        $response->assertSee('20 permohonan')->assertDontSee('Berikutnya');
    }

    public function test_detail_exposes_import_information_without_technical_metadata(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Admin');
        $prayer = PrayerRequest::factory()->create([
            'handled_by' => $owner->id,
            'status' => PrayerRequestStatus::InPrayer,
            'request_type' => 'Kunjungan',
            'legacy_handler_name' => 'Pelayan Lama',
            'admin_notes' => "Firebase UID pemohon: requester-uid\nFirebase UID handler: handler-uid",
        ]);

        $this->actingAs($owner)->getJson(route('admin.prayer-requests.show', $prayer))
            ->assertOk()
            ->assertJsonPath('data.request_type', 'Kunjungan')
            ->assertJsonPath('data.handler', 'Pelayan Lama')
            ->assertDontSee('legacy_handler_name')
            ->assertDontSee('Firebase UID')
            ->assertDontSee('admin_notes')
            ->assertDontSee('Migrasi')
            ->assertDontSee('Rahasia');
    }

    public function test_open_imported_request_shows_the_old_handler_name_without_assigning_a_local_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $prayer = PrayerRequest::factory()->create(['legacy_handler_name' => 'Pelayan Lama']);

        $this->actingAs($admin)->getJson(route('admin.prayer-requests.show', $prayer))
            ->assertOk()->assertJsonPath('data.handler', 'Pelayan Lama')
            ->assertJsonPath('data.can_update', false);
        $this->assertNull($prayer->fresh()->handled_by);
        $this->assertSame(PrayerRequestStatus::Open, $prayer->fresh()->status);
    }

    public function test_other_users_cannot_open_move_or_edit_a_claimed_request(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Pastor');
        $other = User::factory()->create();
        $other->assignRole('Admin');
        $prayer = PrayerRequest::factory()->create([
            'name' => 'Nama Rahasia Unik',
            'prayer_content' => 'Isi doa yang tidak boleh terlihat pengguna lain.',
            'legacy_handler_name' => 'Pelayan Lama',
            'handled_by' => $owner->id,
            'status' => PrayerRequestStatus::InPrayer,
        ]);

        $this->actingAs($other)->get(route('admin.prayer-requests.index'))
            ->assertOk()->assertSee($prayer->reference_number)
            ->assertSee('Kartu terkunci')->assertSee('Yang mendoakan: Pelayan Lama')
            ->assertDontSee('Nama Rahasia Unik')
            ->assertDontSee('Isi doa yang tidak boleh terlihat pengguna lain.');
        $this->actingAs($other)->get(route('admin.prayer-requests.show', $prayer))->assertForbidden();
        $this->actingAs($other)->patch(route('admin.prayer-requests.move', $prayer), [
            'status' => 'closed', 'expected_status' => 'in_prayer',
        ])->assertForbidden();
        $this->actingAs($other)->put(route('admin.prayer-requests.update', $prayer), [
            'admin_notes' => 'Diubah orang lain', 'prayer_result' => 'Hasil palsu',
        ])->assertForbidden();
        $this->assertSame(PrayerRequestStatus::InPrayer, $prayer->fresh()->status);
        $this->assertNull($prayer->fresh()->prayer_result);
    }

    public function test_status_flow_rejects_skips_from_open_and_stale_moves(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('Pastor');
        $prayer = PrayerRequest::factory()->create();

        $this->actingAs($pastor)->patch(route('admin.prayer-requests.move', $prayer), [
            'status' => 'closed', 'expected_status' => 'open',
        ])->assertStatus(422);
        $this->assertNull($prayer->fresh()->handled_by);

        $this->actingAs($pastor)->patch(route('admin.prayer-requests.move', $prayer), [
            'status' => 'in_prayer', 'expected_status' => 'open',
        ])->assertRedirect();
        $this->assertSame($pastor->id, (int) $prayer->fresh()->handled_by);
        $this->actingAs($pastor)->patch(route('admin.prayer-requests.move', $prayer), [
            'status' => 'closed', 'expected_status' => 'open',
        ])->assertStatus(409);
        $this->actingAs($pastor)->patch(route('admin.prayer-requests.move', $prayer), [
            'status' => 'closed', 'expected_status' => 'in_prayer',
        ])->assertStatus(422);
        $this->assertSame(PrayerRequestStatus::InPrayer, $prayer->fresh()->status);
        $this->actingAs($pastor)->patch(route('admin.prayer-requests.move', $prayer), [
            'status' => 'closed', 'expected_status' => 'in_prayer', 'prayer_result' => 'Sudah didoakan bersama.',
        ])->assertRedirect();
        $this->assertSame(PrayerRequestStatus::Closed, $prayer->fresh()->status);
        $this->assertSame('Sudah didoakan bersama.', $prayer->fresh()->prayer_result);
        $this->actingAs($pastor)->patchJson(route('admin.prayer-requests.move', $prayer), [
            'status' => 'answered', 'expected_status' => 'closed',
        ])->assertUnprocessable();
    }

    public function test_closing_requires_a_nonempty_result_even_if_a_draft_result_was_saved_earlier(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('Pastor');
        $prayer = PrayerRequest::factory()->create([
            'handled_by' => $pastor->id,
            'status' => PrayerRequestStatus::InPrayer,
            'prayer_result' => 'Draf hasil sebelumnya.',
        ]);

        $this->actingAs($pastor)->patchJson(route('admin.prayer-requests.move', $prayer), [
            'status' => 'closed', 'expected_status' => 'in_prayer',
        ])->assertUnprocessable();
        $this->actingAs($pastor)->patchJson(route('admin.prayer-requests.move', $prayer), [
            'status' => 'closed', 'expected_status' => 'in_prayer', 'prayer_result' => '   ',
        ])->assertUnprocessable();
        $this->assertSame(PrayerRequestStatus::InPrayer, $prayer->fresh()->status);
        $this->assertSame('Draf hasil sebelumnya.', $prayer->fresh()->prayer_result);

        $this->actingAs($pastor)->patchJson(route('admin.prayer-requests.move', $prayer), [
            'status' => 'closed', 'expected_status' => 'in_prayer', 'prayer_result' => '  Doa telah dilakukan.  ',
        ])->assertOk()->assertJsonPath('message', 'Hasil doa tersimpan dan permohonan selesai.');
        $this->assertSame(PrayerRequestStatus::Closed, $prayer->fresh()->status);
        $this->assertSame('Doa telah dilakukan.', $prayer->fresh()->prayer_result);
    }

    public function test_only_owner_can_save_result_without_changing_assignment(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('Pastor');
        $prayer = PrayerRequest::factory()->create([
            'handled_by' => $pastor->id,
            'status' => PrayerRequestStatus::InPrayer,
        ]);

        $this->actingAs($pastor)->put(route('admin.prayer-requests.update', $prayer), [
            'prayer_result' => 'Kondisi membaik.',
            'admin_notes' => 'Seharusnya diabaikan.',
            'status' => 'closed',
            'handled_by' => User::factory()->create()->id,
        ])->assertRedirect(route('admin.prayer-requests.index', ['prayer' => $prayer->id]));

        $prayer->refresh();
        $this->assertNull($prayer->admin_notes);
        $this->assertSame('Kondisi membaik.', $prayer->prayer_result);
        $this->assertSame(PrayerRequestStatus::InPrayer, $prayer->status);
        $this->assertSame($pastor->id, (int) $prayer->handled_by);
    }

    public function test_confidential_request_requires_permission_to_view_or_move(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['prayer_requests.view', 'prayer_requests.update']);
        $prayer = PrayerRequest::factory()->create(['is_confidential' => true]);

        $this->actingAs($viewer)->get(route('admin.prayer-requests.index'))
            ->assertOk()->assertDontSee($prayer->reference_number);
        $this->actingAs($viewer)->getJson(route('admin.prayer-requests.show', $prayer))->assertForbidden();
        $this->actingAs($viewer)->patch(route('admin.prayer-requests.move', $prayer), [
            'status' => 'in_prayer', 'expected_status' => 'open',
        ])->assertForbidden();
        $this->assertNull($prayer->fresh()->handled_by);
    }

    public function test_an_unassigned_request_stays_open_until_dragged(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('Pastor');
        $prayer = PrayerRequest::factory()->create(['status' => PrayerRequestStatus::Open]);

        $this->assertSame(PrayerRequestStatus::Open, $prayer->fresh()->status);

        $this->actingAs($pastor)->patchJson(route('admin.prayer-requests.move', $prayer), [
            'status' => 'in_prayer', 'expected_status' => 'open',
        ])->assertOk();
        $this->assertSame($pastor->id, (int) $prayer->fresh()->handled_by);
        $this->assertSame(PrayerRequestStatus::InPrayer, $prayer->fresh()->status);
    }

    public function test_export_and_delete_cannot_bypass_the_owner_lock(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Super Admin');
        $other = User::factory()->create();
        $other->assignRole('Super Admin');
        $mine = PrayerRequest::factory()->create(['handled_by' => $owner->id, 'status' => PrayerRequestStatus::InPrayer, 'reference_number' => 'PR-OWNED-ONLY']);
        $theirs = PrayerRequest::factory()->create(['handled_by' => $other->id, 'status' => PrayerRequestStatus::InPrayer, 'reference_number' => 'PR-OTHER-ONLY']);

        $this->actingAs($owner)->delete(route('admin.prayer-requests.destroy', $theirs))->assertForbidden();
        $this->assertDatabaseHas('prayer_requests', ['id' => $theirs->id, 'deleted_at' => null]);

        $csv = $this->actingAs($owner)->get(route('admin.prayer-requests.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($mine->reference_number, $csv);
        $this->assertStringNotContainsString($theirs->reference_number, $csv);
    }
}
