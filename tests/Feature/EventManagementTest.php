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
