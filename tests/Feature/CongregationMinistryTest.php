<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MinistryUnit;
use App\Models\Congregation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CongregationMinistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_forms_include_all_units_and_restore_saved_selection(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'));
        $create = $this->get(route('admin.congregations.create'))->assertOk();
        $create->assertSee('name="is_serving"', false)->assertSee('name="ministry_unit"', false);
        $this->assertCount(18, MinistryUnit::cases());
        foreach (MinistryUnit::cases() as $unit) {
            $create->assertSee($unit->label());
        }
        $this->assertFormState($create->getContent(), serving: false, disabled: false);

        $congregation = Congregation::factory()->create(['is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        $edit = $this->get(route('admin.congregations.edit', $congregation))->assertOk();
        $this->assertFormState($edit->getContent(), serving: true, disabled: false, unit: MinistryUnit::Youth);
        $this->get(route('admin.congregations.show', $congregation))->assertOk()
            ->assertSee('Pelayanan')->assertSee('Unit Pelayanan')->assertSee('Youth');
    }

    public function test_super_admin_can_create_and_change_to_every_ministry_unit(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'))
            ->post(route('admin.congregations.store'), $this->payload() + ['is_serving' => '1', 'ministry_unit' => MinistryUnit::LoverAndWorshiper->value])
            ->assertSessionHasNoErrors()->assertRedirect();
        $congregation = Congregation::query()->sole();
        $this->assertTrue($congregation->is_serving);
        $this->assertSame(MinistryUnit::LoverAndWorshiper, $congregation->ministry_unit);

        foreach (MinistryUnit::cases() as $unit) {
            $this->put(route('admin.congregations.update', $congregation), $this->payload() + ['is_serving' => '1', 'ministry_unit' => $unit->value])
                ->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame($unit, $congregation->fresh()->ministry_unit);
        }
    }

    public function test_unit_is_required_and_must_be_a_listed_option_when_serving(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'));
        foreach ([null, 'invalid-unit', ['youth']] as $unit) {
            $this->post(route('admin.congregations.store'), $this->payload() + ['is_serving' => '1', 'ministry_unit' => $unit])
                ->assertSessionHasErrors('ministry_unit');
        }
        $this->post(route('admin.congregations.store'), $this->payload() + ['is_serving' => 'invalid', 'ministry_unit' => 'youth'])
            ->assertSessionHasErrors('is_serving');
        $this->assertDatabaseCount('congregations', 0);

        $congregation = Congregation::factory()->create(['is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        $this->put(route('admin.congregations.update', $congregation), $this->payload() + ['is_serving' => '1'])
            ->assertSessionHasErrors('ministry_unit');
        $this->assertSame(MinistryUnit::Youth, $congregation->fresh()->ministry_unit);
    }

    public function test_unchecking_service_clears_unit_and_unchecked_creation_defaults_to_false(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'))
            ->post(route('admin.congregations.store'), $this->payload())
            ->assertSessionHasNoErrors()->assertRedirect();
        $congregation = Congregation::query()->sole();
        $this->assertFalse($congregation->is_serving);
        $this->assertNull($congregation->ministry_unit);

        $congregation->update(['is_serving' => true, 'ministry_unit' => MinistryUnit::Pastoral]);
        $this->put(route('admin.congregations.update', $congregation), $this->payload() + ['is_serving' => '0', 'ministry_unit' => 'pastoral'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse($congregation->fresh()->is_serving);
        $this->assertNull($congregation->fresh()->ministry_unit);
    }

    public function test_non_super_admin_can_view_but_cannot_create_or_change_ministry_data(): void
    {
        $this->actingAs($this->userWithRole('Admin'));
        $create = $this->get(route('admin.congregations.create'))->assertOk();
        $this->assertFormState($create->getContent(), serving: false, disabled: true);
        $this->post(route('admin.congregations.store'), $this->payload() + ['is_serving' => '1', 'ministry_unit' => 'pastoral'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $congregation = Congregation::query()->sole();
        $this->assertFalse($congregation->is_serving);
        $this->assertNull($congregation->ministry_unit);

        $congregation->update(['is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        $edit = $this->get(route('admin.congregations.edit', $congregation))->assertOk();
        $this->assertFormState($edit->getContent(), serving: true, disabled: true, unit: MinistryUnit::Youth);
        foreach ([[], ['is_serving' => '0'], ['is_serving' => '1', 'ministry_unit' => 'pastoral']] as $ministryData) {
            $this->put(route('admin.congregations.update', $congregation), array_replace($this->payload(), ['full_name' => 'Nama Diperbarui'], $ministryData))
                ->assertSessionHasNoErrors()->assertRedirect();
            $this->assertTrue($congregation->fresh()->is_serving);
            $this->assertSame(MinistryUnit::Youth, $congregation->fresh()->ministry_unit);
            $this->assertSame('Nama Diperbarui', $congregation->fresh()->full_name);
        }
    }

    public function test_direct_congregation_permissions_do_not_allow_ministry_changes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['congregations.update', 'congregations.view']);
        $congregation = Congregation::factory()->create();
        $this->actingAs($user)->put(route('admin.congregations.update', $congregation), $this->payload() + ['is_serving' => '1', 'ministry_unit' => 'youth'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse($congregation->fresh()->is_serving);
        $this->assertNull($congregation->fresh()->ministry_unit);
    }

    public function test_validation_failure_preserves_submitted_service_selection(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'));
        $this->from(route('admin.congregations.create'))->post(route('admin.congregations.store'), array_replace($this->payload(), [
            'full_name' => '', 'is_serving' => '1', 'ministry_unit' => 'multimedia',
        ]))->assertSessionHasErrors('full_name')->assertRedirect(route('admin.congregations.create'));
        $response = $this->get(route('admin.congregations.create'))->assertOk();
        $this->assertFormState($response->getContent(), serving: true, disabled: false, unit: MinistryUnit::Multimedia);
    }

    public function test_ministry_filter_combines_with_search_gender_and_membership_status(): void
    {
        $match = Congregation::factory()->create(['full_name' => 'Jemaat Cocok', 'gender' => 'female', 'membership_status' => 'member', 'is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        Congregation::factory()->create(['full_name' => 'Jemaat Unit Lain', 'gender' => 'female', 'membership_status' => 'member', 'is_serving' => true, 'ministry_unit' => MinistryUnit::Usher]);
        Congregation::factory()->create(['full_name' => 'Jemaat Gender Lain', 'gender' => 'male', 'membership_status' => 'member', 'is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        Congregation::factory()->create(['full_name' => 'Jemaat Status Lain', 'gender' => 'female', 'membership_status' => 'visitor', 'is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        Congregation::factory()->create(['full_name' => 'Nama Lain', 'gender' => 'female', 'membership_status' => 'member', 'is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        Congregation::factory()->create(['full_name' => 'Jemaat Tanpa Pelayanan', 'gender' => 'female', 'membership_status' => 'member']);

        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.congregations.index', [
            'search' => 'Jemaat', 'gender' => 'female', 'membership_status' => 'member', 'ministry_unit' => 'youth',
        ]))->assertOk()->assertViewHas('items', fn ($items) => $items->total() === 1 && $items->first()['id'] === $match->id);
        foreach (MinistryUnit::cases() as $unit) {
            $response->assertSee($unit->label());
        }
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame('youth', $xpath->evaluate('string(//select[@name="ministry_unit"]/option[@selected]/@value)'));

        $this->get(route('admin.congregations.index'))->assertOk()->assertViewHas('items', fn ($items) => $items->total() === 6);
    }

    public function test_ministry_filter_is_preserved_when_changing_pages(): void
    {
        Congregation::factory()->count(16)->create(['is_serving' => true, 'ministry_unit' => MinistryUnit::Youth]);
        Congregation::factory()->count(2)->create(['is_serving' => true, 'ministry_unit' => MinistryUnit::Usher]);
        $this->actingAs($this->userWithRole('Admin'))->get(route('admin.congregations.index', ['ministry_unit' => 'youth']))
            ->assertOk()->assertViewHas('items', fn ($items) => $items->total() === 16 && $items->count() === 15 && str_contains($items->nextPageUrl(), 'ministry_unit=youth'));
        $this->get(route('admin.congregations.index', ['ministry_unit' => 'youth', 'page' => 2]))
            ->assertOk()->assertViewHas('items', fn ($items) => $items->total() === 16 && $items->count() === 1);
    }

    private function assertFormState(string $html, bool $serving, bool $disabled, ?MinistryUnit $unit = null): void
    {
        $this->assertStringNotContainsString('@js(', $html, 'Blade directives must compile before Alpine evaluates the form bindings.');
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $this->assertSame($serving, $xpath->evaluate('boolean(//input[@id="is_serving"]/@checked)'));
        $this->assertSame($disabled, $xpath->evaluate('boolean(//fieldset[.//input[@id="is_serving"]]/@disabled)'));
        $this->assertSame(! $serving || $disabled, $xpath->evaluate('boolean(//select[@id="ministry_unit"]/@disabled)'));
        $this->assertSame($serving && ! $disabled, $xpath->evaluate('boolean(//select[@id="ministry_unit"]/@required)'));
        if ($unit) {
            $this->assertSame($unit->value, $xpath->evaluate('string(//select[@id="ministry_unit"]/option[@selected]/@value)'));
        }
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function payload(): array
    {
        return ['full_name' => 'Jemaat Pelayanan', 'gender' => 'male', 'baptism_status' => 'unknown', 'membership_status' => 'member', 'is_active' => '1'];
    }
}
