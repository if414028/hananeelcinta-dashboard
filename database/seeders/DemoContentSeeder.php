<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Congregation;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Models\PrayerRequest;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoContentSeeder tidak boleh dijalankan di production.');
        }
        Congregation::factory(25)->create();
        Announcement::factory(4)->published()->create();
        Announcement::factory(2)->create();
        PrayerRequest::factory(8)->create();
        FamilyAltar::factory(7)->create();
        PastorMessage::factory(5)->published()->create();
        PastorMessage::factory(2)->create();
    }
}
