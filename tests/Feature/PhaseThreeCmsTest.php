<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BaptismStatus;
use App\Enums\CongregationGender;
use App\Enums\CongregationMembershipStatus;
use App\Enums\PastorMessageStatus;
use App\Enums\PrayerRequestStatus;
use App\Models\Congregation;
use App\Models\PastorMessage;
use App\Models\PrayerRequest;
use App\Models\User;
use App\Services\WebsiteSettings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\WebsiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PhaseThreeCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, WebsiteSettingSeeder::class]);
    }

    public function test_super_admin_can_open_all_cms_module_indexes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        foreach (['admin.dashboard', 'admin.admin-users.index', 'admin.congregations.index', 'admin.announcements.index', 'admin.prayer-requests.index', 'admin.family-altars.index', 'admin.pastor-messages.index', 'admin.settings.index', 'admin.audit-logs.index', 'admin.roles.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_view_permission_does_not_allow_deleting_congregation(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('congregations.view');
        $congregation = Congregation::factory()->create();

        $this->actingAs($admin)->get(route('admin.congregations.index'))->assertOk();
        $this->actingAs($admin)->delete(route('admin.congregations.destroy', $congregation))->assertForbidden();
        $this->assertDatabaseHas('congregations', ['id' => $congregation->id, 'deleted_at' => null]);
    }

    public function test_congregation_creation_generates_sequential_member_number(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['congregations.create', 'congregations.view']);
        $payload = ['full_name' => 'Jemaat Satu', 'gender' => CongregationGender::Male->value, 'baptism_status' => BaptismStatus::Unknown->value, 'membership_status' => CongregationMembershipStatus::Member->value, 'is_active' => '1'];

        $this->actingAs($admin)->post(route('admin.congregations.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('admin.congregations.store'), $payload + ['full_name' => 'Jemaat Dua'])->assertRedirect();

        $this->assertDatabaseHas('congregations', ['member_number' => 'HC-'.now()->format('Y').'-00001']);
        $this->assertDatabaseHas('congregations', ['member_number' => 'HC-'.now()->format('Y').'-00002']);
    }

    public function test_congregation_detail_shows_firebase_photo_and_all_legacy_notes(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['congregations.view', 'congregations.update']);
        $congregation = Congregation::factory()->create([
            'legacy_firebase_uid' => 'firebase-profile-uid',
            'notes' => "Username lama: jemaat.satu\nGolongan darah: O\nNama ibu: Maria\nNama anak: Hana",
        ]);

        $response = $this->actingAs($admin)->get(route('admin.congregations.show', $congregation));

        $response->assertOk()
            ->assertSee('Data Jemaat')
            ->assertSeeInOrder(['Identitas', 'Username', 'jemaat.satu', 'Data Jemaat'])
            ->assertDontSee('Username lama')
            ->assertDontSee('Role Firebase')
            ->assertDontSee('Firebase UID')
            ->assertSee('Golongan darah')
            ->assertSee('Nama ibu')
            ->assertSee('Nama anak')
            ->assertSee('firebase-profile-uid%2Fprofile-pictures?alt=media', false);

        $this->actingAs($admin)
            ->get(route('admin.congregations.edit', $congregation))
            ->assertOk()
            ->assertSee('Foto profil saat ini')
            ->assertSee('firebase-profile-uid%2Fprofile-pictures?alt=media', false);
    }

    public function test_last_super_admin_cannot_delete_or_demote_themselves(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->delete(route('admin.admin-users.destroy', $admin))->assertStatus(422);
        $this->actingAs($admin)->put(route('admin.admin-users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'Admin', 'is_active' => '1'])->assertStatus(422);
        $this->assertTrue($admin->fresh()->hasRole('Super Admin'));
    }

    public function test_confidential_prayer_requires_specific_permission(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('prayer_requests.view');
        $prayer = PrayerRequest::factory()->create(['is_confidential' => true, 'handled_by' => $admin->id, 'status' => PrayerRequestStatus::InPrayer]);

        $this->actingAs($admin)->get(route('admin.prayer-requests.show', $prayer))->assertForbidden();
        $admin->givePermissionTo('prayer_requests.view_confidential');
        $this->actingAs($admin)->getJson(route('admin.prayer-requests.show', $prayer))->assertOk();
    }

    public function test_pastor_message_content_is_sanitized(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['pastor_messages.create', 'pastor_messages.view']);
        $payload = ['title' => 'Pesan Gembala', 'writer' => 'Gembala', 'content' => '<p onclick="evil()">Aman</p><script>alert(1)</script>', 'status' => PastorMessageStatus::Draft->value];

        $this->actingAs($admin)->post(route('admin.pastor-messages.store'), $payload)->assertRedirect();
        $content = PastorMessage::query()->sole()->content;
        $this->assertSame('<p>Aman</p>', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('<script', $content);
    }

    public function test_pastor_message_publishes_immediately_without_optional_fields(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['pastor_messages.create', 'pastor_messages.update', 'pastor_messages.view']);
        $this->freezeTime();
        $this->actingAs($admin)->get(route('admin.pastor-messages.create'))->assertOk()->assertSee('Format konten')->assertDontSee('name="status"', false)->assertDontSee('name="published_at"', false)->assertDontSee('name="is_featured"', false);
        $content = '<p style="text-align: center; color: red" onclick="evil()"><b>Bold</b> <i>Italic</i> <u>Underline</u></p>';
        $this->actingAs($admin)->post(route('admin.pastor-messages.store'), ['title' => 'Pesan Baru', 'content' => $content, 'status' => 'draft', 'published_at' => '2030-01-01', 'is_featured' => true])->assertSessionHasNoErrors()->assertRedirect();
        $message = PastorMessage::query()->sole();
        $this->assertSame('', $message->writer);
        $this->assertSame(PastorMessageStatus::Published, $message->status);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $message->published_at->format('Y-m-d H:i:s'));
        $this->assertFalse($message->is_featured);
        $this->assertNull($message->featured_image);
        $this->assertSame('<p style="text-align: center"><b>Bold</b> <i>Italic</i> <u>Underline</u></p>', $message->content);
        $this->get(route('pastor-messages.show', $message))->assertOk()->assertSee($message->content, false);
        $publishedAt = $message->published_at->copy();
        $this->travel(1)->days();
        $this->put(route('admin.pastor-messages.update', $message), ['title' => 'Pesan Diperbarui', 'content' => '<p>Isi baru</p>'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue($message->fresh()->published_at->equalTo($publishedAt));
    }

    public function test_pastor_message_rejects_empty_formatted_content(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('pastor_messages.create');
        $this->actingAs($admin)->post(route('admin.pastor-messages.store'), ['title' => 'Kosong', 'content' => '<p><br>&nbsp;</p>'])->assertSessionHasErrors('content');
        $this->assertDatabaseCount('pastor_messages', 0);
    }

    public function test_settings_can_be_updated(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('settings.update');
        $settings = app(WebsiteSettings::class);
        $this->assertSame('JKI Hananeel Cinta', $settings->get('church_name'));

        $this->actingAs($admin)->put(route('admin.settings.update'), ['settings' => ['church_name' => 'Nama Gereja Baru']])->assertRedirect();
        $this->assertDatabaseHas('website_settings', ['key' => 'church_name', 'value' => 'Nama Gereja Baru']);
        $this->assertSame('Nama Gereja Baru', $settings->get('church_name'));
    }

    public function test_prayer_bulk_status_route_is_removed(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('prayer_requests.update');
        $requests = PrayerRequest::factory(2)->create();

        $this->actingAs($admin)->patch('/admin/prayer-requests/bulk-status', ['ids' => $requests->pluck('id')->all(), 'status' => 'in_prayer'])->assertNotFound();
        $this->assertSame(2, PrayerRequest::query()->where('status', 'open')->count());
    }
}
