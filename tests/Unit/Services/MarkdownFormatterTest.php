<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests\Unit\Services;

use DigitalCoreHub\LaravelApiDocx\Services\MarkdownFormatter;
use DigitalCoreHub\LaravelApiDocx\Tests\TestCase;

class MarkdownFormatterTest extends TestCase
{
    private MarkdownFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new MarkdownFormatter();
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

        $this->assertStringContainsString('# API Documentation', $result);
        $this->assertStringContainsString('## GET /api/users', $result);
        $this->assertStringContainsString('**Controller:** App\Http\Controllers\UserController', $result);
        $this->assertStringContainsString('**Method:** index', $result);
        $this->assertStringContainsString('**Description:** Retrieves a list of users.', $result);
    }

    public function test_formats_multiple_routes(): void
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
            [
                'http_methods' => 'POST',
                'uri' => '/api/users',
                'controller' => 'App\Http\Controllers\UserController',
                'method' => 'store',
                'name' => 'users.store',
                'description' => 'Creates a new user.',
            ],
        ];

        $result = $this->formatter->format($documentation);

        $this->assertStringContainsString('## GET /api/users', $result);
        $this->assertStringContainsString('## POST /api/users', $result);
        $this->assertStringContainsString('Retrieves a list of users.', $result);
        $this->assertStringContainsString('Creates a new user.', $result);
    }

    public function test_handles_closure_routes(): void
    {
        $documentation = [
            [
                'http_methods' => 'GET',
                'uri' => '/api/health',
                'controller' => 'Closure',
                'method' => 'invoke',
                'name' => 'health.check',
                'description' => 'Health check endpoint.',
            ],
        ];

        $result = $this->formatter->format($documentation);

        $this->assertStringContainsString('## GET /api/health', $result);
        $this->assertStringContainsString('**Controller:** Closure', $result);
        $this->assertStringContainsString('**Method:** invoke', $result);
    }

    public function test_handles_missing_descriptions(): void
    {
        $documentation = [
            [
                'http_methods' => 'GET',
                'uri' => '/api/users',
                'controller' => 'App\Http\Controllers\UserController',
                'method' => 'index',
                'name' => 'users.index',
                'description' => 'No description available.',
            ],
        ];

        $result = $this->formatter->format($documentation);

        $this->assertStringContainsString('**Description:** No description available.', $result);
    }

    public function test_includes_timestamp(): void
    {
        $documentation = [];

        $result = $this->formatter->format($documentation);

        $this->assertStringContainsString('Generated on:', $result);
        $this->assertStringContainsString(date('Y-m-d H:i:s'), $result);
    }

    public function test_handles_empty_documentation(): void
    {
        $documentation = [];

        $result = $this->formatter->format($documentation);

        $this->assertStringContainsString('# API Documentation', $result);
        $this->assertStringContainsString('No API routes found.', $result);
    }

    public function test_escapes_special_characters(): void
    {
        $documentation = [
            [
                'http_methods' => 'GET',
                'uri' => '/api/users/{id}',
                'controller' => 'App\Http\Controllers\UserController',
                'method' => 'show',
                'name' => 'users.show',
                'description' => 'Retrieves a user with ID containing special chars: <>&"\'',
            ],
        ];

        $result = $this->formatter->format($documentation);

        $this->assertStringContainsString('/api/users/{id}', $result);
        $this->assertStringNotContainsString('<>&"\'', $result);
    }
}
