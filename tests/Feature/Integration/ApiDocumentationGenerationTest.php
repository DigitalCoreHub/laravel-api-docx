<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests\Feature\Integration;

use DigitalCoreHub\LaravelApiDocx\Commands\GenerateDocsCommand;
use DigitalCoreHub\LaravelApiDocx\Tests\Helpers\TestHelper;
use Illuminate\Support\Facades\Route;
use DigitalCoreHub\LaravelApiDocx\Tests\TestCase;

class ApiDocumentationGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::clearRoutes();
    }

    public function test_full_documentation_generation_workflow(): void
    {
        // Create a comprehensive set of test routes
        TestHelper::createApiRoutes([
            [
                'method' => 'GET',
                'uri' => '/api/users',
                'action' => function () {
                    return 'users';
                },
                'name' => 'users.index',
            ],
            [
                'method' => 'POST',
                'uri' => '/api/users',
                'action' => function () {
                    return 'create user';
                },
                'name' => 'users.store',
            ],
            [
                'method' => 'GET',
                'uri' => '/api/users/{id}',
                'action' => function ($id) {
                    return "user {$id}";
                },
                'name' => 'users.show',
            ],
            [
                'method' => 'PUT',
                'uri' => '/api/users/{id}',
                'action' => function ($id) {
                    return "update user {$id}";
                },
                'name' => 'users.update',
            ],
            [
                'method' => 'DELETE',
                'uri' => '/api/users/{id}',
                'action' => function ($id) {
                    return "delete user {$id}";
                },
                'name' => 'users.destroy',
            ],
        ]);

        // Test markdown generation
        $this->artisan(GenerateDocsCommand::class, ['--format' => 'markdown'])
            ->expectsOutput('Found 5 API routes. Generating documentation...')
            ->expectsOutput('📝 Markdown documentation generated:')
            ->assertExitCode(0);

        // Test OpenAPI generation
        $this->artisan(GenerateDocsCommand::class, ['--format' => 'openapi'])
            ->expectsOutput('Found 5 API routes. Generating documentation...')
            ->expectsOutput('🔗 OpenAPI documentation generated:')
            ->assertExitCode(0);

        // Test Postman collection generation
        $this->artisan(GenerateDocsCommand::class, ['--format' => 'postman'])
            ->expectsOutput('Found 5 API routes. Generating documentation...')
            ->expectsOutput('📮 Postman collection generated:')
            ->assertExitCode(0);

        // Test ReDoc HTML generation
        $this->artisan(GenerateDocsCommand::class, ['--format' => 'redoc'])
            ->expectsOutput('Found 5 API routes. Generating documentation...')
            ->expectsOutput('🌐 ReDoc HTML page generated:')
            ->assertExitCode(0);
    }

    public function test_documentation_with_mixed_route_types(): void
    {
        // Create routes with different types
        TestHelper::createApiRoute('GET', '/api/health', function () {
            return 'ok';
        }, 'health.check');

        TestHelper::createControllerRoute('GET', '/api/posts', TestController::class, 'index', 'posts.index');
        TestHelper::createInvokableRoute('POST', '/api/comments', InvokableTestController::class, 'comments.store');

        // Test all formats
        $this->artisan(GenerateDocsCommand::class, ['--format' => 'all'])
            ->expectsOutput('Found 3 API routes. Generating documentation...')
            ->assertExitCode(0);
    }

    public function test_documentation_with_custom_output_paths(): void
    {
        TestHelper::createApiRoute('GET', '/api/test', function () {
            return 'test';
        }, 'test.index');

        $customPath = '/tmp/custom-api-docs.md';

        $this->artisan(GenerateDocsCommand::class, [
            '--format' => 'markdown',
            '--output' => $customPath
        ])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput("📝 Markdown documentation generated: {$customPath}")
            ->assertExitCode(0);

        // Verify file was created
        $this->assertFileExists($customPath);

        // Clean up
        TestHelper::cleanupTempFile($customPath);
    }

    public function test_documentation_ignores_non_api_routes(): void
    {
        // Create non-API routes
        Route::get('/admin/users', function () {
            return 'admin users';
        });

        Route::get('/dashboard', function () {
            return 'dashboard';
        });

        Route::get('/api/users', function () {
            return 'api users';
        });

        $this->artisan(GenerateDocsCommand::class)
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->assertExitCode(0);
    }

    public function test_documentation_with_advanced_ai_features(): void
    {
        TestHelper::createApiRoute('GET', '/api/users', function () {
            return 'users';
        }, 'users.index');

        $this->artisan(GenerateDocsCommand::class, ['--advanced' => true])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('Generating advanced AI documentation...')
            ->assertExitCode(0);
    }

    public function test_documentation_with_watch_mode(): void
    {
        TestHelper::createApiRoute('GET', '/api/users', function () {
            return 'users';
        }, 'users.index');

        // Note: Watch mode is difficult to test in CI, so we just test that it starts
        $this->artisan(GenerateDocsCommand::class, ['--watch' => true])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('Watch mode enabled. Press Ctrl+C to stop.')
            ->assertExitCode(0);
    }

    public function test_documentation_handles_empty_routes(): void
    {
        // No routes registered
        $this->artisan(GenerateDocsCommand::class)
            ->expectsOutput('No API routes found.')
            ->assertExitCode(0);
    }

    public function test_documentation_handles_closure_routes(): void
    {
        TestHelper::createApiRoute('GET', '/api/status', function () {
            return 'ok';
        }, 'status.check');

        $this->artisan(GenerateDocsCommand::class, ['--format' => 'markdown'])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('📝 Markdown documentation generated:')
            ->assertExitCode(0);
    }

    public function test_documentation_handles_controller_routes(): void
    {
        TestHelper::createControllerRoute('GET', '/api/posts', TestController::class, 'index', 'posts.index');

        $this->artisan(GenerateDocsCommand::class, ['--format' => 'markdown'])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('📝 Markdown documentation generated:')
            ->assertExitCode(0);
    }

    public function test_documentation_handles_invokable_controllers(): void
    {
        TestHelper::createInvokableRoute('POST', '/api/comments', InvokableTestController::class, 'comments.store');

        $this->artisan(GenerateDocsCommand::class, ['--format' => 'markdown'])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('📝 Markdown documentation generated:')
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        TestHelper::clearRoutes();
        parent::tearDown();
    }
}

/**
 * Test controller for integration tests
 */
class TestController
{
    /**
     * Retrieves a list of posts.
     */
    public function index(): void
    {
        // Implementation
    }
}

/**
 * Invokable test controller for integration tests
 */
class InvokableTestController
{
    /**
     * Handles comment creation requests.
     */
    public function __invoke(): void
    {
        // Implementation
    }
}
