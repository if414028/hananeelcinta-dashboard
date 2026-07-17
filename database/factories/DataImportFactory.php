<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DataImportStatus;
use App\Models\DataImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DataImport> */
final class DataImportFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->unique()->slug().'.json';

        return [
            'type' => 'firebase',
            'filename' => $filename,
            'checksum' => hash('sha256', $filename),
            'status' => DataImportStatus::Pending,
        ];
    }
}
