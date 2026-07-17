<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AnnouncementStatus;
use App\Enums\CongregationGender;
use App\Enums\DayOfWeek;
use App\Enums\PastorMessageStatus;
use App\Enums\PrayerRequestCategory;
use App\Models\Announcement;
use App\Models\Congregation;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Models\PrayerRequest;
use App\Models\User;
use App\Models\WebsiteSetting;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\WebsiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PhaseTwoDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_factories_and_enum_casts_work(): void
    {
        $congregation = Congregation::factory()->create();
        $prayerRequest = PrayerRequest::factory()->create();
        $familyAltar = FamilyAltar::factory()->create();

        $this->assertInstanceOf(CongregationGender::class, $congregation->gender);
        $this->assertInstanceOf(PrayerRequestCategory::class, $prayerRequest->prayer_category);
        $this->assertInstanceOf(DayOfWeek::class, $familyAltar->day_of_week);
    }

    public function test_only_current_non_expired_announcements_are_published(): void
    {
        $visible = Announcement::factory()->published()->create();
        Announcement::factory()->create(['status' => AnnouncementStatus::Draft]);
        Announcement::factory()->create(['status' => AnnouncementStatus::Published, 'published_at' => now()->addDay()]);
        Announcement::factory()->create(['status' => AnnouncementStatus::Published, 'published_at' => now()->subWeek(), 'expired_at' => now()->subDay()]);

        $this->assertEquals([$visible->id], Announcement::query()->published()->pluck('id')->all());
    }

    public function test_only_current_pastor_messages_are_published(): void
    {
        $visible = PastorMessage::factory()->published()->create();
        PastorMessage::factory()->create(['status' => PastorMessageStatus::Draft]);
        PastorMessage::factory()->create(['status' => PastorMessageStatus::Published, 'published_at' => now()->addDay()]);

        $this->assertEquals([$visible->id], PastorMessage::query()->published()->pluck('id')->all());
    }

    public function test_audit_user_relationships_and_null_on_delete_work(): void
    {
        $admin = User::factory()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id]);

        $this->assertTrue($announcement->creator->is($admin));
        $admin->forceDelete();
        $this->assertNull($announcement->fresh()->created_by);
    }

    public function test_prayer_request_internal_fields_are_hidden_from_serialization(): void
    {
        $request = PrayerRequest::factory()->create(['admin_notes' => 'Catatan privat', 'ip_address' => '127.0.0.1']);

        $this->assertArrayNotHasKey('admin_notes', $request->toArray());
        $this->assertArrayNotHasKey('ip_address', $request->toArray());
        $this->assertArrayHasKey('prayer_content', $request->toArray());
    }

    public function test_whatsapp_url_is_normalized(): void
    {
        $altar = FamilyAltar::factory()->create(['contact_phone' => '0812-3456-7890']);
        $this->assertSame('https://wa.me/6281234567890', $altar->whatsapp_url);
    }

    public function test_website_settings_seeder_is_idempotent(): void
    {
        $this->seed(WebsiteSettingSeeder::class);
        $count = WebsiteSetting::query()->count();
        $this->seed(WebsiteSettingSeeder::class);

        $this->assertSame($count, WebsiteSetting::query()->count());
        $this->assertSame('JKI Hananeel Cinta', WebsiteSetting::query()->where('key', 'church_name')->value('value'));
    }

    public function test_main_database_seeder_can_run_from_a_clean_database(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
        $this->assertDatabaseHas('website_settings', ['key' => 'church_name']);
    }
}
