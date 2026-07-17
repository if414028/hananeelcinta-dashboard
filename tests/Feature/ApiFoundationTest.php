<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ApiFoundationTest extends TestCase
{
    public function test_v1_health_endpoint_has_consistent_success_shape(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'API is available.',
                'data' => ['version' => 'v1'],
            ]);
    }
}
