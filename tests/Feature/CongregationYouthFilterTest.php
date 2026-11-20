<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MinistryUnit;
use App\Models\Congregation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CongregationYouthFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(10, 0));
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);
    }

    public function test_youth_includes_ages_zero_through_thirty_five_regardless_of_ministry(): void
    {
        $expected = collect(['2026-11-20', '2025-11-20', '1991-11-20', '1990-11-21'])
            ->map(fn ($birthday) => Congregation::factory()->create(['date_of_birth' => $birthday, 'is_serving' => false, 'ministry_unit' => null])->id);
        foreach (['1990-11-20', '1980-01-01', '2026-11-21', null] as $birthday) {
            Congregation::factory()->create(['date_of_birth' => $birthday, 'is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        }

        $response = $this->get(route('admin.congregations.index', ['age_group' => 'youth']))->assertOk();
        $this->assertEqualsCanonicalizing($expected->all(), $response->viewData('items')->pluck('id')->all());
        $response->assertSee('Kelompok Usia')->assertSee('Youth (0–35 tahun)');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame('youth', $xpath->evaluate('string(//select[@name="age_group"]/option[@selected]/@value)'));
    }

    public function test_youth_combines_with_all_other_filters_and_csv_export(): void
    {
        $attributes = ['full_name' => 'Jemaat Youth Cocok', 'date_of_birth' => '2000-01-01', 'gender' => 'female', 'membership_status' => 'member', 'is_serving' => true, 'ministry_unit' => MinistryUnit::Usher];
        $match = Congregation::factory()->create($attributes);
        foreach ([
            ['full_name' => 'Nama Lain'],
            ['gender' => 'male'],
            ['membership_status' => 'visitor'],
            ['ministry_unit' => MinistryUnit::Youth],
            ['date_of_birth' => '1980-01-01'],
        ] as $difference) {
            Congregation::factory()->create(array_replace($attributes, $difference));
        }
        $filters = ['age_group' => 'youth', 'search' => 'Jemaat', 'gender' => 'female', 'membership_status' => 'member', 'ministry_unit' => 'usher'];

        $response = $this->get(route('admin.congregations.index', $filters))->assertOk();
        $this->assertSame([$match->id], $response->viewData('items')->pluck('id')->all());

        $export = $this->get(route('admin.congregations.export', $filters))->assertOk()->streamedContent();
        $lines = preg_split('/\r?\n/', trim($export));
        $this->assertCount(2, $lines);
        $this->assertSame($match->member_number, str_getcsv($lines[1], escape: '')[0]);
    }

    public function test_youth_is_preserved_on_next_page_and_reset_shows_all_ages(): void
    {
        Congregation::factory()->count(16)->create(['date_of_birth' => '2000-01-01']);
        Congregation::factory()->create(['date_of_birth' => '1980-01-01']);
        Congregation::factory()->create(['date_of_birth' => null]);

        $firstPage = $this->get(route('admin.congregations.index', ['age_group' => 'youth']))->assertOk()->viewData('items');
        $this->assertSame(16, $firstPage->total());
        $this->assertCount(15, $firstPage);
        $this->assertStringContainsString('age_group=youth', $firstPage->nextPageUrl());
        $this->get($firstPage->nextPageUrl())->assertOk()
            ->assertViewHas('items', fn ($items) => $items->total() === 16 && $items->count() === 1);
        $this->get(route('admin.congregations.index'))->assertOk()
            ->assertViewHas('items', fn ($items) => $items->total() === 18);
    }
}
