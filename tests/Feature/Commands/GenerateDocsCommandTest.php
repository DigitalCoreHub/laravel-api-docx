<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests\Feature\Commands;

use DigitalCoreHub\LaravelApiDocx\Commands\GenerateDocsCommand;
use DigitalCoreHub\LaravelApiDocx\Services\AdvancedAiGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\AiDocGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\DocBlockParser;
use DigitalCoreHub\LaravelApiDocx\Services\MarkdownFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\OpenApiFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\PostmanFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\ReDocGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\RouteCollector;
use DigitalCoreHub\LaravelApiDocx\Support\CacheManager;
use DigitalCoreHub\LaravelApiDocx\Support\Providers\OpenAiClient;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use DigitalCoreHub\LaravelApiDocx\Tests\TestCase;
use Mockery;

class GenerateDocsCommandTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = new Filesystem();
        
        // Clear any existing routes
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    public function test_command_handles_no_routes(): void
    {
        $this->artisan(GenerateDocsCommand::class)
            ->expectsOutput('No API routes found.')
            ->assertExitCode(0);
    }

    public function test_command_generates_markdown_documentation(): void
    {
        // Register test routes
        Route::get('/api/users', function () {
            return 'users';
        })->name('users.index');

        Route::post('/api/users', function () {
            return 'create user';
        })->name('users.store');

        $this->artisan(GenerateDocsCommand::class, ['--format' => 'markdown'])
            ->expectsOutput('Found 2 API routes. Generating documentation...')
            ->expectsOutput('📝 Markdown documentation generated:')
            ->assertExitCode(0);
    }

    public function test_command_generates_openapi_documentation(): void
    {
        // Register test routes
        Route::get('/api/users', function () {
            return 'users';
        })->name('users.index');

        $this->artisan(GenerateDocsCommand::class, ['--format' => 'openapi'])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('🔗 OpenAPI documentation generated:')
            ->assertExitCode(0);
    }

    public function test_command_generates_postman_collection(): void
    {
        // Register test routes
        Route::get('/api/users', function () {
            return 'users';
        })->name('users.index');

        $this->artisan(GenerateDocsCommand::class, ['--format' => 'postman'])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('📮 Postman collection generated:')
            ->assertExitCode(0);
    }

    public function test_command_generates_redoc_html(): void
    {
        // Register test routes
        Route::get('/api/users', function () {
            return 'users';
        })->name('users.index');

        $this->artisan(GenerateDocsCommand::class, ['--format' => 'redoc'])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('🌐 ReDoc HTML page generated:')
            ->assertExitCode(0);
    }

    public function test_command_generates_all_formats(): void
    {
        // Register test routes
        Route::get('/api/users', function () {
            return 'users';
        })->name('users.index');

        $this->artisan(GenerateDocsCommand::class, ['--format' => 'all'])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('📝 Markdown documentation generated:')
            ->expectsOutput('🔗 OpenAPI documentation generated:')
            ->expectsOutput('📮 Postman collection generated:')
            ->expectsOutput('🌐 ReDoc HTML page generated:')
            ->assertExitCode(0);
    }

    public function test_command_with_custom_output_path(): void
    {
        // Register test routes
        Route::get('/api/users', function () {
            return 'users';
        })->name('users.index');

        $customPath = '/tmp/custom-api-docs.md';

        $this->artisan(GenerateDocsCommand::class, [
            '--format' => 'markdown',
            '--output' => $customPath
        ])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput("📝 Markdown documentation generated: {$customPath}")
            ->assertExitCode(0);

        // Clean up
        if ($this->filesystem->exists($customPath)) {
            $this->filesystem->delete($customPath);
        }
    }

    public function test_command_with_advanced_ai(): void
    {
        // Register test routes
        Route::get('/api/users', function () {
            return 'users';
        })->name('users.index');

        $this->artisan(GenerateDocsCommand::class, ['--advanced' => true])
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->expectsOutput('Generating advanced AI documentation...')
            ->assertExitCode(0);
    }

    public function test_command_ignores_non_api_routes(): void
    {
        // Register non-API routes
        Route::get('/admin/users', function () {
            return 'admin users';
        });

        Route::get('/dashboard', function () {
            return 'dashboard';
        });

        // Register API route
        Route::get('/api/users', function () {
            return 'api users';
        });

        $this->artisan(GenerateDocsCommand::class)
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->assertExitCode(0);
    }

    public function test_command_handles_controller_routes(): void
    {
        // Register controller route
        Route::get('/api/users', [TestController::class, 'index'])->name('users.index');

        $this->artisan(GenerateDocsCommand::class)
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->assertExitCode(0);
    }

    public function test_command_handles_invokable_controllers(): void
    {
        // Register invokable controller route
        Route::get('/api/users', InvokableTestController::class)->name('users.index');

        $this->artisan(GenerateDocsCommand::class)
            ->expectsOutput('Found 1 API routes. Generating documentation...')
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

/**
 * Test controller for command tests
 */
class TestController
{
    /**
     * Retrieves a list of users.
     */
    public function index(): void
    {
        // Implementation
    }
}

/**
 * Invokable test controller
 */
class InvokableTestController
{
    /**
     * Invokable controller for handling requests.
     */
    public function __invoke(): void
    {
        // Implementation
    }
}
