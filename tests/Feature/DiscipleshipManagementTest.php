<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Auth\Contracts\FirebaseTokenVerifier;
use App\Auth\VerifiedFirebaseToken;
use App\Models\Congregation;
use App\Models\DiscipleshipClass;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\WebsiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DiscipleshipManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, WebsiteSettingSeeder::class]);
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);
    }

    public function test_admin_creates_class_with_images_and_selected_congregation_members(): void
    {
        $members = Congregation::factory()->count(2)->create();
        $this->get(route('admin.discipleship.create'))->assertOk()->assertSee('Nama Kelas')->assertSee('Peserta kelas');
        $this->post(route('admin.discipleship.store'), $this->payload($members->modelKeys()) + [
            'banner' => UploadedFile::fake()->image('banner.jpg'),
            'badge' => UploadedFile::fake()->image('badge.png'),
            'completed_at' => now(), 'completed_by' => 999,
        ])->assertRedirect();

        $class = DiscipleshipClass::query()->sole();
        $this->assertSame('Dasar Iman', $class->name);
        $this->assertSame('Pengajar Hana', $class->teacher_name);
        $this->assertCount(2, $class->participants);
        $this->assertNull($class->completed_at);
        Storage::disk('public')->assertExists([$class->banner, $class->badge]);
        $this->get(route('admin.discipleship.show', $class))->assertOk()->assertSee('Selesaikan Kelas')->assertSee($members->first()->full_name);
        $this->get(route('admin.discipleship.index', ['search' => 'Pengajar Hana']))->assertOk()->assertSee('Dasar Iman');
    }

    public function test_ongoing_class_can_be_edited_and_members_added_without_losing_existing_images(): void
    {
        $first = Congregation::factory()->create(['profile_photo' => 'congregations/hana.jpg']);
        $second = Congregation::factory()->create();
        $class = $this->class();
        $class->participants()->attach($first);
        $this->get(route('admin.discipleship.edit', $class))->assertOk()->assertSee($first->full_name)
            ->assertViewHas('selectedParticipants', fn ($selected) => $selected->first()['profile_photo_url'] === $first->profilePhotoUrl());
        $payload = $this->payload([$first->id, $second->id]);
        $payload['name'] = 'Dasar Iman Diperbarui';
        $this->put(route('admin.discipleship.update', $class), $payload)->assertSessionHasNoErrors()->assertRedirect(route('admin.discipleship.show', $class));
        $this->assertCount(2, $class->fresh()->participants);
        $this->assertSame('Dasar Iman Diperbarui', $class->fresh()->name);
        $this->assertSame('discipleship/badge/initial.png', $class->fresh()->badge);
        $this->assertCount(0, $first->completedDiscipleshipClasses);
        $this->get(route('admin.discipleship.show', [$class, 'search' => $second->member_number]))->assertOk()->assertSee($second->full_name)->assertDontSee($first->full_name);
    }

    public function test_replacing_images_deletes_only_the_old_assets_after_a_successful_save(): void
    {
        $member = Congregation::factory()->create();
        $class = $this->class();
        $class->participants()->attach($member);
        Storage::disk('public')->put($class->banner, 'old banner');
        Storage::disk('public')->put($class->badge, 'old badge');
        $this->put(route('admin.discipleship.update', $class), $this->payload([$member->id]) + [
            'banner' => UploadedFile::fake()->image('replacement.jpg'),
            'badge' => UploadedFile::fake()->image('replacement.png'),
        ])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing([$class->banner, $class->badge]);
        Storage::disk('public')->assertExists([$class->fresh()->banner, $class->fresh()->badge]);
    }

    public function test_completion_awards_each_participant_once_and_profiles_show_only_earned_badges(): void
    {
        $members = Congregation::factory()->count(2)->create();
        $outsider = Congregation::factory()->create();
        $class = $this->class();
        $otherClass = $this->class(['name' => 'Kelas yang belum selesai']);
        $class->participants()->attach($members->modelKeys());
        $otherClass->participants()->attach($members->first());
        $this->get(route('admin.congregations.show', $members->first()))->assertOk()->assertSee('Belum ada badge kelulusan pemuridan.');

        $this->post(route('admin.discipleship.complete', $class))->assertSessionHasNoErrors()->assertRedirect(route('admin.discipleship.show', $class));
        $completedAt = $class->fresh()->completed_at;
        $completedBy = $class->fresh()->completed_by;
        $this->assertNotNull($completedAt);
        $this->assertSame(2, DB::table('discipleship_enrollments')->where('discipleship_class_id', $class->id)->whereNotNull('completed_at')->count());
        foreach ($members as $member) {
            $this->assertSame([$class->id], $member->fresh()->completedDiscipleshipClasses->modelKeys());
            $this->get(route('admin.congregations.show', $member))->assertOk()->assertSee('Badge Dasar Iman')->assertDontSee('Badge Kelas yang belum selesai');
        }
        $this->assertCount(0, $outsider->completedDiscipleshipClasses);
        $this->get(route('admin.discipleship.show', $class))->assertOk()->assertSee('Lulus')->assertDontSee('>Selesaikan Kelas<', false)->assertDontSee('>Tambah Peserta<', false);
        $this->travel(1)->day();
        $this->post(route('admin.discipleship.complete', $class))->assertRedirect();
        $this->assertTrue($class->fresh()->completed_at->equalTo($completedAt));
        $this->assertSame($completedBy, $class->fresh()->completed_by);
        $this->assertDatabaseCount('discipleship_enrollments', 3);
        $this->get(route('admin.discipleship.index', ['status' => 'completed']))->assertOk()->assertSee('Dasar Iman')->assertDontSee('Kelas yang belum selesai');

        $this->post(route('admin.discipleship.complete', $otherClass))->assertSessionHasNoErrors();
        $this->assertSame([$otherClass->id, $class->id], $members->first()->fresh()->completedDiscipleshipClasses->modelKeys());
        $this->assertSame([$class->id], $members->last()->fresh()->completedDiscipleshipClasses->modelKeys());
        $this->get(route('admin.congregations.show', $members->first()))->assertOk()->assertSee('Badge Dasar Iman')->assertSee('Badge Kelas yang belum selesai');
    }

    public function test_completed_class_rejects_late_edits_and_new_participants(): void
    {
        $member = Congregation::factory()->create();
        $outsider = Congregation::factory()->create();
        $class = $this->class();
        $class->participants()->attach($member);
        $this->post(route('admin.discipleship.complete', $class))->assertRedirect();
        $this->get(route('admin.discipleship.edit', $class))->assertRedirect(route('admin.discipleship.show', $class));
        $payload = $this->payload([$member->id, $outsider->id]);
        $payload['name'] = 'Late edit';
        $payload['badge'] = UploadedFile::fake()->image('late.png');
        $this->put(route('admin.discipleship.update', $class), $payload)->assertSessionHasErrors('class');
        $this->assertSame('Dasar Iman', $class->fresh()->name);
        $this->assertCount(1, $class->fresh()->participants);
        $this->assertCount(0, Storage::disk('public')->allFiles());
        $this->assertCount(0, $outsider->completedDiscipleshipClasses);
    }

    public function test_ended_schedule_does_not_automatically_grant_a_badge_and_empty_class_cannot_complete(): void
    {
        $class = $this->class(['starts_at' => now()->subWeek(), 'ends_at' => now()->subDay()]);
        $this->post(route('admin.discipleship.complete', $class))->assertSessionHasErrors('participants');
        $member = Congregation::factory()->create();
        $class->participants()->attach($member);
        $this->assertSame('Menunggu penyelesaian', $class->statusLabel());
        $this->assertCount(0, $member->completedDiscipleshipClasses);
    }

    public function test_validation_prevents_invalid_dates_images_and_unavailable_or_duplicate_participants(): void
    {
        $member = Congregation::factory()->create();
        $deleted = Congregation::factory()->create();
        $deleted->delete();
        $this->post(route('admin.discipleship.store'), $this->payload([$member->id]))->assertSessionHasErrors(['banner', 'badge']);
        $payload = $this->payload([$member->id, $member->id, $deleted->id, 999999]);
        $payload['ends_at'] = now()->subMonth()->format('Y-m-d H:i:s');
        $payload['banner'] = UploadedFile::fake()->create('bad.txt', 1, 'text/plain');
        $payload['badge'] = UploadedFile::fake()->create('large.png', 6000, 'image/png');
        $this->post(route('admin.discipleship.store'), $payload)->assertSessionHasErrors(['ends_at', 'banner', 'badge', 'participant_ids.0', 'participant_ids.2', 'participant_ids.3']);
        $this->post(route('admin.discipleship.store'), $this->payload([]))->assertSessionHasErrors('participant_ids');
        $this->assertDatabaseCount('discipleship_classes', 0);
        $this->assertDatabaseCount('discipleship_enrollments', 0);
    }

    public function test_member_picker_searches_and_paginates_without_exposing_private_fields_or_deleted_members(): void
    {
        $member = Congregation::factory()->create(['full_name' => 'Hana Peserta', 'notes' => 'Private notes', 'email' => 'private@example.com', 'profile_photo' => 'congregations/hana.jpg', 'legacy_firebase_uid' => 'private-firebase-uid']);
        $deleted = Congregation::factory()->create(['full_name' => 'Hana Dihapus']);
        $deleted->delete();
        $this->getJson(route('admin.discipleship.members', ['search' => 'Hana']))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $member->id)
            ->assertJsonPath('data.0.profile_photo_url', $member->profilePhotoUrl())
            ->assertJsonMissingPath('data.0.email')->assertJsonMissingPath('data.0.notes')
            ->assertJsonMissingPath('data.0.legacy_firebase_uid')->assertJsonMissingPath('data.0.profile_photo');
        $this->getJson(route('admin.discipleship.members', ['search' => $member->member_number]))->assertOk()->assertJsonCount(1, 'data');
        Congregation::factory()->count(21)->create();
        $this->getJson(route('admin.discipleship.members'))->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('next_page', 2);
        $this->getJson(route('admin.discipleship.members', ['page' => 2]))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('next_page', null);
    }

    public function test_permissions_protect_class_mutations_completion_and_member_search(): void
    {
        $class = $this->class();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('discipleship.view');
        $this->actingAs($viewer)->get(route('admin.discipleship.index'))->assertOk()->assertDontSee('>Buat Kelas<', false);
        $this->get(route('admin.discipleship.show', $class))->assertOk()->assertDontSee('>Edit Kelas<', false)->assertDontSee('>Selesaikan Kelas<', false);
        $this->get(route('admin.discipleship.create'))->assertForbidden();
        $this->get(route('admin.discipleship.edit', $class))->assertForbidden();
        $this->getJson(route('admin.discipleship.members'))->assertForbidden();
        $this->post(route('admin.discipleship.store'), [])->assertForbidden();
        $this->put(route('admin.discipleship.update', $class), [])->assertForbidden();
        $this->post(route('admin.discipleship.complete', $class))->assertForbidden();
        $this->assertNull($class->fresh()->completed_at);
        $pastor = User::factory()->create();
        $pastor->assignRole('Pastor');
        $this->actingAs($pastor)->get(route('admin.discipleship.index'))->assertForbidden();
    }

    public function test_mobile_profile_returns_only_badges_earned_by_the_authenticated_member(): void
    {
        $member = Congregation::factory()->create(['legacy_firebase_uid' => 'discipleship-uid']);
        $class = $this->class();
        $class->participants()->attach($member);
        $otherClass = $this->class(['name' => 'Kelas jemaat lain']);
        $otherClass->participants()->attach(Congregation::factory()->create());
        $this->post(route('admin.discipleship.complete', $class));
        $this->post(route('admin.discipleship.complete', $otherClass));
        $this->mock(FirebaseTokenVerifier::class)->shouldReceive('verify')->andReturn(new VerifiedFirebaseToken(
            'discipleship-uid', $member->email, true, ['password'], CarbonImmutable::now(), [],
        ));
        $this->withToken('valid-token')->getJson('/api/v1/me')->assertOk()->assertJsonCount(1, 'data.profile.discipleship_badges')
            ->assertJsonPath('data.profile.discipleship_badges.0.class_id', $class->id)
            ->assertJsonPath('data.profile.discipleship_badges.0.class_name', 'Dasar Iman')
            ->assertJsonPath('data.profile.discipleship_badges.0.badge_url', $class->badgeUrl());
    }

    private function class(array $overrides = []): DiscipleshipClass
    {
        return DiscipleshipClass::query()->create(array_merge([
            'name' => 'Dasar Iman', 'description' => 'Belajar dasar iman bersama.', 'teacher_name' => 'Pengajar Hana',
            'banner' => 'discipleship/banner/initial.jpg', 'badge' => 'discipleship/badge/initial.png',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ], $overrides));
    }

    private function payload(array $participantIds): array
    {
        return [
            'name' => 'Dasar Iman', 'description' => 'Belajar dasar iman bersama.', 'teacher_name' => 'Pengajar Hana',
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'), 'ends_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'participant_ids' => $participantIds,
        ];
    }
}
