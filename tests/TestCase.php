<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests;

use DigitalCoreHub\LaravelApiDocx\LaravelApiDocxServiceProvider;
use DigitalCoreHub\LaravelApiDocx\Support\AiClientInterface;
use DigitalCoreHub\LaravelApiDocx\Support\Providers\OpenAiClient;
use Illuminate\Support\Facades\Route;
use Mockery;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing routes
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelApiDocxServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Mock AI client interface
        $app->bind(AiClientInterface::class, function () {
            $mockClient = Mockery::mock(OpenAiClient::class);
            $mockClient->shouldReceive('generate')->andReturn('Mock AI generated description');

            return $mockClient;
        });

        // Set up test environment
        $app['config']->set('api-docs.enable_ai', false);
        $app['config']->set('api-docs.cache.enabled', false);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
