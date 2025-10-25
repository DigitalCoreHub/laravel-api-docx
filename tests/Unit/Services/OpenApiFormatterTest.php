<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests\Unit\Services;

use DigitalCoreHub\LaravelApiDocx\Services\OpenApiFormatter;
use DigitalCoreHub\LaravelApiDocx\Tests\TestCase;

class OpenApiFormatterTest extends TestCase
{
    private OpenApiFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new OpenApiFormatter();
    }

    public function test_formats_single_route(): void
    {
        $documentation = [
            [
                'http_methods' => 'GET',
                'uri' => '/api/users',
                'controller' => 'App\Http\Controllers\UserController',
                'method' => 'index',
                'name' => 'users.index',
                'description' => 'Retrieves a list of users.',
            ],
        ];

        $result = $this->formatter->format($documentation);

        $this->assertIsString($result);
        $this->assertJson($result);
        
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('openapi', $decoded);
        $this->assertArrayHasKey('info', $decoded);
        $this->assertArrayHasKey('paths', $decoded);
    }

    public function test_handles_empty_documentation(): void
    {
        $documentation = [];

        $result = $this->formatter->format($documentation);

        $this->assertIsString($result);
        $this->assertJson($result);
        
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('openapi', $decoded);
        $this->assertArrayHasKey('info', $decoded);
        $this->assertArrayHasKey('paths', $decoded);
    }
}
