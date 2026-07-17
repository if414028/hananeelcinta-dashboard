<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\FamilyAltar;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FamilyAltar> */
final class FamilyAltarFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Mezbah Keluarga '.fake()->city(), 'description' => fake()->sentence(), 'day_of_week' => fake()->randomElement(DayOfWeek::cases()), 'start_time' => fake()->time('H:i'), 'location_name' => fake()->streetName(), 'address' => fake()->address(), 'city' => fake()->city(), 'pic_name' => fake()->name(), 'contact_phone' => fake()->numerify('08##########'), 'is_active' => true, 'sort_order' => 0];
    }
}
