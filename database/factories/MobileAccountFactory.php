<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Congregation;
use App\Models\MobileAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MobileAccount> */
final class MobileAccountFactory extends Factory
{
    protected $model = MobileAccount::class;

    public function definition(): array
    {
        return [
            'congregation_id' => Congregation::factory(),
            'firebase_uid' => fake()->unique()->regexify('[A-Za-z0-9]{28}'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'provider_ids' => ['password'],
            'is_active' => true,
        ];
    }
}
