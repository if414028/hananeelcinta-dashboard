<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\WebsiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, WebsiteSettingSeeder::class]);
    }

    public function test_admin_can_create_event_with_dynamic_registration_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->post(route('admin.events.store'), [
            'title' => 'Gathering Keluarga',
            'description' => 'Acara kebersamaan jemaat.',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addWeek()->addHours(3)->format('Y-m-d H:i:s'),
            'is_published' => '1',
            'registration_fields' => [
                ['key' => 'name', 'label' => 'Nama lengkap', 'type' => 'text', 'required' => '1'],
                ['key' => 'birth_date', 'label' => 'Tanggal lahir', 'type' => 'date', 'required' => '1'],
                ['key' => 'gender', 'label' => 'Jenis kelamin', 'type' => 'select', 'required' => '1', 'options' => ['Laki-laki', 'Perempuan']],
            ],
        ])->assertRedirect();

        $event = Event::query()->sole();
        $this->assertSame('gathering-keluarga', $event->slug);
        $this->assertCount(3, $event->registration_fields);
        $this->assertTrue($event->is_published);
    }

    public function test_guest_can_register_and_receive_downloadable_ticket(): void
    {
        $event = $this->event();

        $response = $this->post(route('events.store', $event->slug), ['answers' => ['name' => 'Hana Cinta', 'birth_date' => '1995-03-12', 'gender' => 'Perempuan']]);
        $registration = EventRegistration::query()->sole();

        $response->assertRedirect(route('events.ticket', $registration));
        $this->get(route('events.ticket', $registration))->assertOk()->assertSee('Hana Cinta')->assertSee('Tiket Anda siap');
        $this->get(route('events.ticket.download', $registration))->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertSee('Hana Cinta', false);
    }

    public function test_draft_event_cannot_accept_public_registration(): void
    {
        $event = $this->event(['is_published' => false]);

        $this->get(route('events.register', $event->slug))->assertNotFound();
        $this->post(route('events.store', $event->slug), ['answers' => ['name' => 'Hana']])->assertNotFound();
        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_admin_can_verify_and_check_in_ticket_once(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $registration = $this->event()->registrations()->create(['ticket_code' => '01K123456789ABCDEFGHJKMNPQ', 'attendee_name' => 'Hana Cinta', 'answers' => ['name' => 'Hana Cinta']]);

        $this->actingAs($admin)->get(route('admin.event-registrations.verify', $registration))->assertOk()->assertSee('Hana Cinta');
        $this->actingAs($admin)->post(route('admin.event-registrations.check-in', $registration))->assertRedirect();
        $this->assertNotNull($registration->fresh()->checked_in_at);
        $this->assertSame($admin->id, $registration->fresh()->checked_in_by);
        $this->actingAs($admin)->post(route('admin.event-registrations.check-in', $registration))->assertRedirect();
    }

    public function test_camera_permission_is_only_enabled_on_event_scanner(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $event = $this->event();

        $this->actingAs($admin)->get(route('admin.events.scanner', $event))
            ->assertOk()->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $this->actingAs($admin)->get(route('admin.events.show', $event))
            ->assertOk()->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_manual_confirmation_lists_searches_and_paginates_only_this_events_participants(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $event = $this->event();
        $hana = $event->registrations()->create(['ticket_code' => 'manual-hana', 'attendee_name' => 'Hana Cinta', 'answers' => ['name' => 'Hana Cinta', 'birth_date' => '1995-03-12']]);
        $event->registrations()->create(['ticket_code' => 'manual-budi', 'attendee_name' => 'Budi', 'answers' => ['name' => 'Budi']]);
        $otherEvent = $this->event(['slug' => 'other-event']);
        $otherEvent->registrations()->create(['ticket_code' => 'other-ticket', 'attendee_name' => 'Hana Lain', 'answers' => ['name' => 'Hana Lain']]);

        $this->actingAs($admin)->get(route('admin.events.show', $event))->assertOk()->assertSee('Konfirmasi Manual');
        $this->get(route('admin.events.manual', $event))->assertOk()->assertSee('Hana Cinta')->assertSee('Budi')->assertDontSee('Hana Lain')->assertSee('manual-dialog-title')->assertSee('Konfirmasi kehadiran');
        $this->assertNull($hana->fresh()->checked_in_at);
        $this->get(route('admin.events.manual', [$event, 'search' => 'Hana']))->assertOk()->assertSee('Hana Cinta')->assertDontSee('manual-budi')->assertDontSee('Hana Lain')->assertSee('1995-03-12');
        $this->get(route('admin.events.manual', [$event, 'search' => 'TidakAda']))->assertOk()->assertSee('Peserta tidak ditemukan');

        foreach (range(1, 26) as $number) {
            $event->registrations()->create(['ticket_code' => 'pagination-'.$number, 'attendee_name' => sprintf('Peserta %02d', $number), 'answers' => []]);
        }
        $this->get(route('admin.events.manual', [$event, 'search' => 'Peserta', 'page' => 2]))
            ->assertOk()->assertSee('Peserta 26')->assertDontSee('Peserta 01');
    }

    public function test_manual_confirmation_records_attendance_once_and_returns_to_search_results(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $event = $this->event();
        $registration = $event->registrations()->create(['ticket_code' => 'manual-attendance', 'attendee_name' => 'Hana Cinta', 'answers' => ['name' => 'Hana Cinta']]);
        $returnUrl = route('admin.events.manual', [$event, 'search' => 'Hana']);

        $this->actingAs($admin)->from($returnUrl)->post(route('admin.events.manual-check-in', [$event, $registration]))
            ->assertRedirect($returnUrl)->assertSessionHas('success', 'Check-in berhasil dicatat.');
        $checkedInAt = $registration->fresh()->checked_in_at;
        $this->assertNotNull($checkedInAt);
        $this->assertSame($admin->id, $registration->fresh()->checked_in_by);
        $this->get($returnUrl)->assertOk()->assertSee('Hadir');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('Super Admin');
        $this->travel(5)->minutes();
        $this->actingAs($otherAdmin)->from($returnUrl)->post(route('admin.events.manual-check-in', [$event, $registration]))
            ->assertRedirect($returnUrl)->assertSessionHas('success', 'Peserta ini sudah check-in sebelumnya.');
        $this->assertTrue($registration->fresh()->checked_in_at->equalTo($checkedInAt));
        $this->assertSame($admin->id, $registration->fresh()->checked_in_by);
    }

    public function test_manual_confirmation_requires_check_in_permission_and_matching_event(): void
    {
        $event = $this->event();
        $registration = $event->registrations()->create(['ticket_code' => 'protected-attendance', 'attendee_name' => 'Hana', 'answers' => []]);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['events.view', 'events.registrations']);
        $this->actingAs($viewer)->get(route('admin.events.manual', $event))->assertForbidden();
        $this->post(route('admin.events.manual-check-in', [$event, $registration]))->assertForbidden();
        $this->get(route('admin.events.show', $event))->assertOk()->assertDontSee('Konfirmasi Manual');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $otherEvent = $this->event(['slug' => 'wrong-event']);
        $this->actingAs($admin)->post(route('admin.events.manual-check-in', [$otherEvent, $registration]))->assertNotFound();
        $this->assertNull($registration->fresh()->checked_in_at);
    }

    public function test_registration_ticket_search_does_not_leak_participants_from_other_events(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $event = $this->event();
        $otherEvent = $this->event(['slug' => 'another-event']);
        $otherEvent->registrations()->create(['ticket_code' => 'private-other-ticket', 'attendee_name' => 'Peserta event lain', 'answers' => []]);

        $this->actingAs($admin)->get(route('admin.events.registrations', [$event, 'search' => 'private-other-ticket']))
            ->assertOk()->assertDontSee('Peserta event lain');
    }

    public function test_manual_attendance_tabs_filter_with_name_search_and_update_after_confirmation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $event = $this->event();
        $event->registrations()->create(['ticket_code' => 'tab-present', 'attendee_name' => 'Hana Hadir', 'answers' => [], 'checked_in_at' => now(), 'checked_in_by' => $admin->id]);
        $pending = $event->registrations()->create(['ticket_code' => 'tab-pending', 'attendee_name' => 'Hana Menunggu', 'answers' => []]);
        $event->registrations()->create(['ticket_code' => 'tab-budi', 'attendee_name' => 'Budi Menunggu', 'answers' => []]);
        $otherEvent = $this->event(['slug' => 'tabs-other-event']);
        $otherEvent->registrations()->create(['ticket_code' => 'tab-other', 'attendee_name' => 'Hana Event Lain', 'answers' => [], 'checked_in_at' => now()]);

        $this->actingAs($admin)->get(route('admin.events.manual', [$event, 'status' => 'present', 'search' => 'Hana']))
            ->assertOk()->assertSee('Hana Hadir')->assertDontSee('Hana Menunggu')->assertDontSee('Hana Event Lain')
            ->assertViewHas('statusCounts', ['all' => 3, 'present' => 1, 'pending' => 2])
            ->assertSee('name="status" value="present"', false);
        $returnUrl = route('admin.events.manual', [$event, 'status' => 'pending', 'search' => 'Hana']);
        $this->get($returnUrl)->assertOk()->assertSee('Hana Menunggu')->assertDontSee('Hana Hadir')->assertDontSee('Budi Menunggu');
        $this->from($returnUrl)->post(route('admin.events.manual-check-in', [$event, $pending]))->assertRedirect($returnUrl);
        $this->get($returnUrl)->assertOk()->assertDontSee('Hana Menunggu')->assertSee('Peserta tidak ditemukan')
            ->assertViewHas('statusCounts', ['all' => 3, 'present' => 2, 'pending' => 1]);
        $this->get(route('admin.events.manual', [$event, 'status' => 'present']))->assertOk()->assertSee('Hana Menunggu')->assertSee('Hana Hadir');
        $this->get(route('admin.events.manual', [$event, 'status' => 'all']))->assertOk()->assertSee('Budi Menunggu')->assertSee('Hana Menunggu')->assertSee('Hana Hadir');
        $this->get(route('admin.events.manual', [$event, 'status' => 'invalid']))->assertSessionHasErrors('status');
    }

    private function event(array $overrides = []): Event
    {
        return Event::query()->create(array_merge([
            'title' => 'Gathering Keluarga',
            'slug' => 'gathering-keluarga',
            'description' => 'Acara kebersamaan jemaat.',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
            'is_published' => true,
            'registration_fields' => [
                ['key' => 'name', 'label' => 'Nama lengkap', 'type' => 'text', 'required' => true, 'options' => []],
                ['key' => 'birth_date', 'label' => 'Tanggal lahir', 'type' => 'date', 'required' => false, 'options' => []],
                ['key' => 'gender', 'label' => 'Jenis kelamin', 'type' => 'select', 'required' => false, 'options' => ['Laki-laki', 'Perempuan']],
            ],
        ], $overrides));
    }
}
