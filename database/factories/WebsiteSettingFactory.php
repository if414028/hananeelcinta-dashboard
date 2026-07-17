<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WebsiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebsiteSetting> */
final class WebsiteSettingFactory extends Factory
{
    public function definition(): array
    {
        return ['group' => 'general', 'key' => fake()->unique()->slug(2), 'value' => fake()->sentence(), 'type' => 'text', 'is_public' => false];
    }
}
