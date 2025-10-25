<?php

namespace DigitalCoreHub\LaravelApiDocx\Services;

use Illuminate\Support\Str;

/**
 * Formats route documentation into an OpenAPI 3.0 specification.
 */
class OpenApiFormatter
{
    /**
     * Build the OpenAPI specification for the provided documentation entries.
     *
     * @param array<int, array<string, string>> $documentation
     * @return string
     */
    public function format(array $documentation): string
    {
        $openApi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'API Documentation',
                'description' => 'Generated automatically by digitalcorehub/laravel-api-docx',
                'version' => '1.0.0',
            ],
            'servers' => [
                [
                    'url' => config('app.url', 'http://localhost'),
                    'description' => 'API Server',
                ],
            ],
            'paths' => $this->buildPaths($documentation),
        ];

        return json_encode($openApi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build the paths section of the OpenAPI specification.
     *
     * @param array<int, array<string, string>> $documentation
     * @return array<string, mixed>
     */
    private function buildPaths(array $documentation): array
    {
        $paths = [];

        foreach ($documentation as $entry) {
            $path = $this->convertLaravelRouteToOpenApiPath($entry['uri']);
            $methods = explode('|', $entry['http_methods']);

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }

            foreach ($methods as $method) {
                $method = strtolower($method);
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'])) {
                    $paths[$path][$method] = $this->buildOperation($entry, $method);
                }
            }
        }

        return $paths;
    }

    /**
     * Convert Laravel route pattern to OpenAPI path format.
     *
     * @param string $uri
     * @return string
     */
    private function convertLaravelRouteToOpenApiPath(string $uri): string
    {
        // Convert Laravel route parameters to OpenAPI format
        $path = preg_replace('/\{([^}]+)\}/', '{$1}', $uri);
        
        // Remove api prefix if present
        if (Str::startsWith($path, 'api/')) {
            $path = '/' . substr($path, 4);
        } elseif (!Str::startsWith($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * Build an operation object for a specific HTTP method.
     *
     * @param array<string, string> $entry
     * @param string $method
     * @return array<string, mixed>
     */
    private function buildOperation(array $entry, string $method): array
    {
        $operation = [
            'summary' => $this->extractSummary($entry['description']),
            'description' => $entry['description'],
            'tags' => [$this->extractTag($entry['uri'])],
            'responses' => [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Add operationId if route has a name
        if (!empty($entry['name'])) {
            $operation['operationId'] = $entry['name'];
        }

        // Add parameters for path variables
        $parameters = $this->extractPathParameters($entry['uri']);
        if (!empty($parameters)) {
            $operation['parameters'] = $parameters;
        }

        // Add request body for methods that typically have one
        if (in_array($method, ['post', 'put', 'patch'])) {
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                        ],
                    ],
                ],
            ];
        }

        return $operation;
    }

    /**
     * Extract a summary from the description.
     *
     * @param string $description
     * @return string
     */
    private function extractSummary(string $description): string
    {
        $lines = explode("\n", $description);
        $firstLine = trim($lines[0]);
        
        return Str::length($firstLine) > 100 
            ? Str::substr($firstLine, 0, 97) . '...'
            : $firstLine;
    }

    /**
     * Extract a tag from the URI for grouping operations.
     *
     * @param string $uri
     * @return string
     */
    private function extractTag(string $uri): string
    {
        $segments = explode('/', trim($uri, '/'));
        $firstSegment = $segments[0] ?? 'api';
        
        return Str::title(str_replace('-', ' ', $firstSegment));
    }

    /**
     * Extract path parameters from the URI.
     *
     * @param string $uri
     * @return array<int, array<string, mixed>>
     */
    private function extractPathParameters(string $uri): array
    {
        $parameters = [];
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);
        
        foreach ($matches[1] as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                ],
            ];
        }

        return $parameters;
    }
}
