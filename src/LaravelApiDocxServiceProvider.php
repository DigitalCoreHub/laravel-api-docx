<?php

namespace DigitalCoreHub\LaravelApiDocx;

use DigitalCoreHub\LaravelApiDocx\Commands\GenerateDocsCommand;
use DigitalCoreHub\LaravelApiDocx\Services\AdvancedAiGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\AiDocGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\DocBlockParser;
use DigitalCoreHub\LaravelApiDocx\Services\MarkdownFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\OpenApiFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\PostmanFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\ReDocGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\RouteCollector;
use DigitalCoreHub\LaravelApiDocx\Support\AiClientInterface;
use DigitalCoreHub\LaravelApiDocx\Support\CacheManager;
use DigitalCoreHub\LaravelApiDocx\Support\Providers\OpenAiClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * The package service provider responsible for bootstrapping all bindings.
 */
class LaravelApiDocxServiceProvider extends ServiceProvider
{
    /**
     * Register bindings and configuration merging.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/api-docs.php', 'api-docs');

        $this->app->singleton(RouteCollector::class);
        $this->app->singleton(DocBlockParser::class);
        $this->app->singleton(MarkdownFormatter::class);
        $this->app->singleton(OpenApiFormatter::class);
        $this->app->singleton(PostmanFormatter::class);
        $this->app->singleton(ReDocGenerator::class);
        $this->app->singleton(CacheManager::class, function ($app): CacheManager {
            return new CacheManager(
                $app['files'],
                (string) digitalcorehub_config('api-docs.cache.store_path', digitalcorehub_storage_path('app/laravel-api-docx-cache.php')),
                (bool) digitalcorehub_config('api-docs.cache.enabled', true)
            );
        });

        $this->app->bind(AiClientInterface::class, function ($app): AiClientInterface {
            $config = digitalcorehub_config('api-docs.ai', []);
            $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;

            return new OpenAiClient(
                new GuzzleClient([
                    'timeout' => $config['timeout'] ?? 15,
                ]),
                $config,
                $logger
            );
        });

        $this->app->singleton(AiDocGenerator::class, function ($app): AiDocGenerator {
            return new AiDocGenerator(
                $app->make(AiClientInterface::class),
                $app->make(CacheManager::class),
                (bool) digitalcorehub_config('api-docs.enable_ai', true)
            );
        });

        $this->app->singleton(AdvancedAiGenerator::class, function ($app): AdvancedAiGenerator {
            return new AdvancedAiGenerator(
                $app->make(AiClientInterface::class),
                $app->make(CacheManager::class),
                (bool) digitalcorehub_config('api-docs.enable_ai', true)
            );
        });
    }

    /**
     * Bootstrap the package by registering commands and publishable assets.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateDocsCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/api-docs.php' => $this->app->configPath('api-docs.php'),
            ], 'api-docs-config');
        }
    }
}
