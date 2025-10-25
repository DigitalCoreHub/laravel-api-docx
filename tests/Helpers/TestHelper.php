<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests\Helpers;

use Illuminate\Support\Facades\Route;

class TestHelper
{
    /**
     * Create a test API route
     */
    public static function createApiRoute(
        string $method,
        string $uri,
        callable|string $action,
        ?string $name = null
    ): void {
        $route = Route::match([$method], $uri, $action);
        
        if ($name) {
            $route->name($name);
        }
    }

    /**
     * Create multiple test API routes
     */
    public static function createApiRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            self::createApiRoute(
                $route['method'],
                $route['uri'],
                $route['action'],
                $route['name'] ?? null
            );
        }
    }

    /**
     * Create a test controller route
     */
    public static function createControllerRoute(
        string $method,
        string $uri,
        string $controller,
        string $action,
        ?string $name = null
    ): void {
        $route = Route::match([$method], $uri, [$controller, $action]);
        
        if ($name) {
            $route->name($name);
        }
    }

    /**
     * Create a test invokable controller route
     */
    public static function createInvokableRoute(
        string $method,
        string $uri,
        string $controller,
        ?string $name = null
    ): void {
        $route = Route::match([$method], $uri, $controller);
        
        if ($name) {
            $route->name($name);
        }
    }

    /**
     * Clear all routes
     */
    public static function clearRoutes(): void
    {
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * Get sample route data for testing
     */
    public static function getSampleRouteData(): array
    {
        return [
            'http_methods' => 'GET',
            'uri' => '/api/users',
            'controller' => 'App\Http\Controllers\UserController',
            'method' => 'index',
            'name' => 'users.index',
            'description' => 'Retrieves a list of users.',
        ];
    }

    /**
     * Get sample documentation array for testing
     */
    public static function getSampleDocumentation(): array
    {
        return [
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
            [
                'http_methods' => 'GET',
                'uri' => '/api/posts',
                'controller' => 'App\Http\Controllers\PostController',
                'method' => 'index',
                'name' => 'posts.index',
                'description' => 'Retrieves a list of posts.',
            ],
        ];
    }

    /**
     * Create a temporary file for testing
     */
    public static function createTempFile(string $content = '', string $extension = 'txt'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.' . $extension;
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * Clean up temporary file
     */
    public static function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Assert that a string contains all required markdown elements
     */
    public static function assertMarkdownStructure(string $markdown): void
    {
        $requiredElements = [
            '# API Documentation',
            'Generated on:',
            '## ',
            '**Controller:**',
            '**Method:**',
            '**Description:**',
        ];

        foreach ($requiredElements as $element) {
            if (!str_contains($markdown, $element)) {
                throw new \AssertionError("Markdown does not contain required element: {$element}");
            }
        }
    }

    /**
     * Assert that a string contains valid JSON structure
     */
    public static function assertValidJson(string $json): void
    {
        $decoded = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \AssertionError('String is not valid JSON: ' . json_last_error_msg());
        }
        
        if (!is_array($decoded)) {
            throw new \AssertionError('JSON does not decode to an array');
        }
    }

    /**
     * Get mock AI response for testing
     */
    public static function getMockAiResponse(): array
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => 'This is a test AI-generated description for the API endpoint.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get mock route data with various HTTP methods
     */
    public static function getMockRouteData(): array
    {
        return [
            'http_methods' => 'GET,POST,PUT,DELETE',
            'uri' => '/api/users/{id}',
            'controller' => 'App\Http\Controllers\UserController',
            'method' => 'show',
            'name' => 'users.show',
            'description' => 'Shows a specific user by ID.',
        ];
    }
}
