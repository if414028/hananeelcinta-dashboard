<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AnnouncementStatus;
use App\Enums\PastorMessageStatus;
use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestSource;
use App\Models\Announcement;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Models\PrayerRequest;
use Database\Seeders\WebsiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PhaseFourPublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WebsiteSettingSeeder::class);
    }

    public function test_home_only_displays_public_content(): void
    {
        $published = Announcement::factory()->published()->create(['title' => 'Pengumuman Publik']);
        Announcement::factory()->create(['title' => 'Pengumuman Draf', 'status' => AnnouncementStatus::Draft]);
        $activeAltar = FamilyAltar::factory()->create(['name' => 'Mezbah Aktif']);
        FamilyAltar::factory()->create(['name' => 'Mezbah Nonaktif', 'is_active' => false]);

        $this->get(route('home'))->assertOk()->assertSee($published->title)->assertDontSee('Pengumuman Draf')->assertSee($activeAltar->name)->assertDontSee('Mezbah Nonaktif');
    }

    public function test_draft_and_scheduled_content_cannot_be_opened(): void
    {
        $draft = Announcement::factory()->create(['status' => AnnouncementStatus::Draft]);
        $scheduled = PastorMessage::factory()->create(['status' => PastorMessageStatus::Published, 'published_at' => now()->addDay()]);

        $this->get(route('announcements.show', $draft))->assertNotFound();
        $this->get(route('pastor-messages.show', $scheduled))->assertNotFound();
    }

    public function test_prayer_request_can_be_submitted_with_safe_server_fields(): void
    {
        $payload = ['name' => 'Pendoa', 'email' => 'pendoa@example.test', 'prayer_category' => PrayerRequestCategory::Family->value, 'prayer_content' => 'Mohon dukungan doa untuk keluarga saya.', 'is_anonymous' => '0', 'is_confidential' => '1', 'privacy_accepted' => '1', 'source' => 'admin'];

        $this->post(route('prayer-request.store'), $payload)->assertRedirect(route('prayer-request.success', 'PR-'.now()->format('Ymd').'-0001'));
        $prayer = PrayerRequest::query()->sole();
        $this->assertSame(PrayerRequestSource::Website, $prayer->source);
        $this->assertTrue($prayer->is_confidential);
        $this->assertNull($prayer->admin_notes);
    }

    public function test_honeypot_and_privacy_consent_are_required(): void
    {
        $payload = ['name' => 'Bot', 'prayer_category' => 'family', 'prayer_content' => 'Permohonan doa yang cukup panjang.', 'website' => 'spam.example'];
        $this->post(route('prayer-request.store'), $payload)->assertSessionHasErrors(['website', 'privacy_accepted']);
        $this->assertDatabaseCount('prayer_requests', 0);
    }

    public function test_pastor_message_view_count_increments(): void
    {
        $message = PastorMessage::factory()->published()->create();
        $this->get(route('pastor-messages.show', $message))->assertOk()->assertSee($message->title);
        $this->assertSame(1, $message->fresh()->view_count);
    }

    public function test_sitemap_excludes_drafts_and_confirmation_is_noindex(): void
    {
        $published = Announcement::factory()->published()->create();
        $draft = Announcement::factory()->create();

        $this->get(route('sitemap'))->assertOk()->assertSee(route('announcements.show', $published), false)->assertDontSee(route('announcements.show', $draft), false);
        $this->get(route('prayer-request.success', 'PR-'.now()->format('Ymd').'-0001'))->assertOk()->assertSee('noindex,nofollow', false);
    }

    public function test_prayer_submission_is_rate_limited(): void
    {
        $payload = ['name' => 'Pendoa', 'prayer_category' => 'family', 'prayer_content' => 'Permohonan doa yang cukup panjang.', 'privacy_accepted' => '1'];
        foreach (range(1, 5) as $attempt) {
            $this->post(route('prayer-request.store'), $payload)->assertRedirect();
        }
        $this->post(route('prayer-request.store'), $payload)->assertTooManyRequests();
        $this->assertDatabaseCount('prayer_requests', 5);
    }
}
