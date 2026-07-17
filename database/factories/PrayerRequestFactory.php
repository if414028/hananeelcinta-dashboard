<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestSource;
use App\Enums\PrayerRequestStatus;
use App\Models\PrayerRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrayerRequest> */
final class PrayerRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_number' => 'PR-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone_number' => fake()->numerify('08##########'),
            'prayer_category' => fake()->randomElement(PrayerRequestCategory::cases()), 'prayer_content' => fake()->paragraph(),
            'is_anonymous' => false, 'is_confidential' => true, 'status' => PrayerRequestStatus::New,
            'source' => PrayerRequestSource::Website, 'ip_address' => fake()->ipv4(), 'user_agent' => fake()->userAgent(),
        ];
    }
}
