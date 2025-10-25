<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Services;

use Illuminate\Support\Str;

/**
 * Formats route documentation into a Postman Collection v2.1.
 */
class PostmanFormatter
{
    /**
     * Build the Postman collection for the provided documentation entries.
     *
     * @param array<int, array<string, string>> $documentation
     */
    public function format(array $documentation): string
    {
        $collection = [
            'info' => [
                'name' => 'API Documentation',
                'description' => 'Generated automatically by digitalcorehub/laravel-api-docx',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                'version' => '1.0.0',
            ],
            'item' => $this->buildItems($documentation),
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => config('app.url', 'http://localhost'),
                    'type' => 'string',
                ],
            ],
        ];

        return json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build the items array for the Postman collection.
     *
     * @param array<int, array<string, string>> $documentation
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(array $documentation): array
    {
        $grouped = $this->groupByController($documentation);
        $items = [];

        foreach ($grouped as $controller => $routes) {
            $folder = [
                'name' => $this->formatControllerName($controller),
                'description' => "Routes for {$controller}",
                'item' => [],
            ];

            foreach ($routes as $route) {
                $folder['item'][] = $this->buildRequest($route);
            }

            $items[] = $folder;
        }

        return $items;
    }

    /**
     * Group routes by controller.
     *
     * @param array<int, array<string, string>> $documentation
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function groupByController(array $documentation): array
    {
        $grouped = [];

        foreach ($documentation as $route) {
            $controller = $route['controller'] ?? 'Unknown';
            $grouped[$controller][] = $route;
        }

        return $grouped;
    }

    /**
     * Build a Postman request item.
     *
     * @param array<string, string> $route
     *
     * @return array<string, mixed>
     */
    private function buildRequest(array $route): array
    {
        $methods = explode('|', $route['http_methods']);
        $method = strtoupper($methods[0]); // Use first method for Postman
        $uri = $this->convertLaravelRouteToPostmanUrl($route['uri']);

        $request = [
            'name' => $this->generateRequestName($route),
            'request' => [
                'method' => $method,
                'header' => [
                    [
                        'key' => 'Accept',
                        'value' => 'application/json',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'Content-Type',
                        'value' => 'application/json',
                        'type' => 'text',
                    ],
                ],
                'url' => [
                    'raw' => '{{base_url}}' . $uri,
                    'host' => ['{{base_url}}'],
                    'path' => explode('/', trim($uri, '/')),
                ],
                'description' => $route['description'],
            ],
        ];

        // Add request body for methods that typically have one
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $request['request']['body'] = [
                'mode' => 'raw',
                'raw' => json_encode([
                    'example' => 'data',
                ], JSON_PRETTY_PRINT),
                'options' => [
                    'raw' => [
                        'language' => 'json',
                    ],
                ],
            ];
        }

        // Add path variables
        $pathVariables = $this->extractPathVariables($route['uri']);
        if (! empty($pathVariables)) {
            $request['request']['url']['variable'] = array_map(function ($var) {
                return [
                    'key' => $var,
                    'value' => 'example',
                    'description' => "The {$var} parameter",
                ];
            }, $pathVariables);
        }

        return $request;
    }

    /**
     * Convert Laravel route pattern to Postman URL format.
     */
    private function convertLaravelRouteToPostmanUrl(string $uri): string
    {
        // Remove api prefix if present
        if (Str::startsWith($uri, 'api/')) {
            $uri = '/' . substr($uri, 4);
        } elseif (! Str::startsWith($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    /**
     * Generate a meaningful request name.
     *
     * @param array<string, string> $route
     */
    private function generateRequestName(array $route): string
    {
        $methods = explode('|', $route['http_methods']);
        $method = strtoupper($methods[0]);
        $uri = $route['uri'];

        // Extract the main resource from URI
        $segments = explode('/', trim($uri, '/'));
        $resource = $segments[1] ?? 'resource';

        $action = match ($method) {
            'GET' => 'List',
            'POST' => 'Create',
            'PUT', 'PATCH' => 'Update',
            'DELETE' => 'Delete',
            default => 'Action',
        };

        return "{$action} {$resource}";
    }

    /**
     * Format controller name for folder display.
     */
    private function formatControllerName(string $controller): string
    {
        $name = class_basename($controller);

        return Str::of($name)->replace('Controller', '')->snake()->title()->toString();
    }

    /**
     * Extract path variables from the URI.
     *
     * @return array<int, string>
     */
    private function extractPathVariables(string $uri): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        return $matches[1];
    }
}
