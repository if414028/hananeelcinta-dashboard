<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BaptismStatus;
use App\Enums\CongregationGender;
use App\Enums\CongregationMaritalStatus;
use App\Enums\CongregationMembershipStatus;
use App\Models\Congregation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Congregation> */
final class CongregationFactory extends Factory
{
    public function definition(): array
    {
        $joinedAt = fake()->dateTimeBetween('-8 years');

        return [
            'member_number' => 'HC-'.$joinedAt->format('Y').'-'.fake()->unique()->numerify('#####'),
            'full_name' => fake()->name(), 'nickname' => fake()->firstName(),
            'gender' => fake()->randomElement(CongregationGender::cases()),
            'place_of_birth' => fake()->city(), 'date_of_birth' => fake()->dateTimeBetween('-75 years', '-15 years'),
            'marital_status' => fake()->randomElement(CongregationMaritalStatus::cases()),
            'phone_number' => fake()->numerify('08##########'), 'whatsapp_number' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(), 'address' => fake()->address(), 'city' => fake()->city(),
            'province' => 'Jawa Barat', 'postal_code' => fake()->postcode(), 'occupation' => fake()->jobTitle(),
            'baptism_status' => fake()->randomElement(BaptismStatus::cases()),
            'membership_status' => fake()->randomElement(CongregationMembershipStatus::cases()),
            'joined_at' => $joinedAt, 'is_active' => true,
        ];
    }
}
