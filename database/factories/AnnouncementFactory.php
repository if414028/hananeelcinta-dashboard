<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Announcement> */
final class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return ['title' => $title, 'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'), 'excerpt' => fake()->sentence(16), 'description' => fake()->paragraphs(3, true), 'status' => AnnouncementStatus::Draft, 'is_featured' => false, 'sort_order' => 0];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => AnnouncementStatus::Published, 'published_at' => fake()->dateTimeBetween('-2 months', 'now')]);
    }
}
