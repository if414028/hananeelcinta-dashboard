<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PastorMessageStatus;
use App\Models\PastorMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PastorMessage> */
final class PastorMessageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return ['title' => $title, 'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'), 'writer' => fake()->name(), 'content' => '<p>'.fake()->paragraphs(4, true).'</p>', 'excerpt' => fake()->sentence(18), 'status' => PastorMessageStatus::Draft, 'is_featured' => false, 'view_count' => 0];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => PastorMessageStatus::Published, 'published_at' => fake()->dateTimeBetween('-6 months', 'now')]);
    }
}
