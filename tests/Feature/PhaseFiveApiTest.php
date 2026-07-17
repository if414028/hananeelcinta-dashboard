<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AnnouncementStatus;
use App\Enums\PrayerRequestSource;
use App\Models\Announcement;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Models\PrayerRequest;
use Database\Seeders\WebsiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PhaseFiveApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WebsiteSettingSeeder::class);
    }

    public function test_config_and_home_have_consistent_envelopes(): void
    {
        Announcement::factory()->published()->create();
        PastorMessage::factory()->published()->create();
        FamilyAltar::factory()->create();

        $this->getJson('/api/v1/config')->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.church.logo_url', route('brand.logo'))->assertJsonStructure(['success', 'message', 'data' => ['church', 'social_media', 'links', 'mobile']])->assertHeader('X-Request-Id');
        $this->get(route('brand.logo'))->assertOk()->assertHeader('content-type', 'image/webp');
        $this->getJson('/api/v1/home')->assertOk()->assertJsonStructure(['success', 'message', 'data' => ['featured_announcements', 'latest_pastor_messages', 'family_altars']]);
    }

    public function test_announcement_api_only_returns_current_published_content(): void
    {
        $published = Announcement::factory()->published()->create(['title' => 'Published API']);
        Announcement::factory()->create(['title' => 'Draft API', 'status' => AnnouncementStatus::Draft]);
        Announcement::factory()->create(['title' => 'Expired API', 'status' => AnnouncementStatus::Published, 'published_at' => now()->subWeek(), 'expired_at' => now()->subDay()]);

        $this->getJson('/api/v1/announcements?per_page=1')->assertOk()->assertJsonPath('meta.per_page', 1)->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', $published->title)->assertJsonMissing(['legacy_firebase_key' => $published->legacy_firebase_key])->assertJsonStructure(['links' => ['first', 'last', 'prev', 'next']]);
        $this->getJson('/api/v1/announcements/'.$published->slug)->assertOk()->assertJsonPath('data.slug', $published->slug);
    }

    public function test_draft_and_inactive_resources_return_consistent_404(): void
    {
        $draft = Announcement::factory()->create();
        $inactive = FamilyAltar::factory()->create(['is_active' => false]);

        $this->getJson('/api/v1/announcements/'.$draft->slug)->assertNotFound()->assertExactJson(['success' => false, 'message' => 'Resource not found.']);
        $this->getJson('/api/v1/family-altars/'.$inactive->id)->assertNotFound()->assertExactJson(['success' => false, 'message' => 'Resource not found.']);
        $this->getJson('/api/v1/announcements/does-not-exist')->assertNotFound()->assertExactJson(['success' => false, 'message' => 'Resource not found.']);
    }

    public function test_pagination_validation_uses_api_error_format(): void
    {
        $this->getJson('/api/v1/announcements?per_page=500')->assertUnprocessable()->assertJsonPath('success', false)->assertJsonPath('message', 'Validation failed.')->assertJsonValidationErrors('per_page');
    }

    public function test_filters_work_for_pastor_messages_and_family_altars(): void
    {
        PastorMessage::factory()->published()->create(['writer' => 'Penulis A']);
        PastorMessage::factory()->published()->create(['writer' => 'Penulis B']);
        FamilyAltar::factory()->create(['city' => 'Karawang']);
        FamilyAltar::factory()->create(['city' => 'Jakarta']);

        $this->getJson('/api/v1/pastor-messages?writer=Penulis%20A')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.writer', 'Penulis A');
        $this->getJson('/api/v1/family-altars?city=Karawang')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.city', 'Karawang');
    }

    public function test_prayer_submission_uses_trusted_header_and_hides_internal_fields(): void
    {
        $payload = ['name' => 'Mobile User', 'prayer_category' => 'healing', 'prayer_content' => 'Mohon dukungan doa untuk kesembuhan.', 'is_confidential' => true, 'privacy_accepted' => true, 'source' => 'admin'];

        $this->withHeader('X-App-Platform', 'ios')->postJson('/api/v1/prayer-requests', $payload)->assertCreated()->assertJsonPath('success', true)->assertJsonPath('data.status', 'new')->assertJsonMissing(['admin_notes'])->assertJsonMissing(['ip_address'])->assertJsonStructure(['data' => ['reference_number', 'status', 'submitted_at']]);
        $prayer = PrayerRequest::query()->sole();
        $this->assertSame(PrayerRequestSource::Ios, $prayer->source);
        $this->assertSame('PR-'.now()->format('Ymd').'-0001', $prayer->reference_number);
    }

    public function test_invalid_mobile_platform_and_prayer_content_are_rejected(): void
    {
        $this->withHeader('X-App-Platform', 'windows')->postJson('/api/v1/prayer-requests', ['name' => 'User', 'prayer_category' => 'healing', 'prayer_content' => 'pendek', 'privacy_accepted' => false])->assertUnprocessable()->assertJsonPath('success', false)->assertJsonValidationErrors(['client_platform', 'prayer_content', 'privacy_accepted']);
        $this->assertDatabaseCount('prayer_requests', 0);
    }
}
