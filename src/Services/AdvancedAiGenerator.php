<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Services;

use DigitalCoreHub\LaravelApiDocx\Support\AiClientInterface;
use DigitalCoreHub\LaravelApiDocx\Support\CacheManager;
use Exception;

/**
 * Advanced AI generator with enhanced features for API documentation.
 */
class AdvancedAiGenerator
{
    public function __construct(
        private readonly AiClientInterface $client,
        private readonly CacheManager $cacheManager,
        private readonly bool $enabled
    ) {}

    /**
     * Generate comprehensive API documentation with examples.
     *
     * @param array<string, mixed> $route
     *
     * @return array<string, mixed>
     */
    public function generateComprehensiveDocs(array $route): array
    {
        $cacheKey = sprintf('comprehensive_%s:%s', $route['http_methods'], $route['uri']);

        if ($this->cacheManager->isEnabled()) {
            $cached = $this->cacheManager->get($cacheKey);
            if ($cached !== null) {
                return json_decode($cached, true);
            }
        }

        $docs = [
            'description' => $this->generateDescription($route),
            'summary' => $this->generateSummary($route),
            'parameters' => $this->generateParameters($route),
            'request_example' => $this->generateRequestExample($route),
            'response_example' => $this->generateResponseExample($route),
            'error_responses' => $this->generateErrorResponses($route),
            'tags' => $this->generateTags($route),
        ];

        if ($this->cacheManager->isEnabled()) {
            $this->cacheManager->put($cacheKey, json_encode($docs));
        }

        return $docs;
    }

    /**
     * Generate a detailed description.
     */
    private function generateDescription(array $route): string
    {
        $prompt = $this->buildDescriptionPrompt($route);

        return $this->makeAiRequest($prompt, 300);
    }

    /**
     * Generate a concise summary.
     */
    private function generateSummary(array $route): string
    {
        $prompt = $this->buildSummaryPrompt($route);

        return $this->makeAiRequest($prompt, 100);
    }

    /**
     * Generate parameter documentation.
     */
    private function generateParameters(array $route): array
    {
        $prompt = $this->buildParametersPrompt($route);
        $response = $this->makeAiRequest($prompt, 500);

        // Parse AI response into structured parameters
        return $this->parseParametersResponse($response, $route);
    }

    /**
     * Generate request example.
     */
    private function generateRequestExample(array $route): array
    {
        $methods = explode('|', $route['http_methods']);
        $method = strtoupper($methods[0]);

        if (! in_array($method, ['POST', 'PUT', 'PATCH'])) {
            return [];
        }

        $prompt = $this->buildRequestExamplePrompt($route);
        $response = $this->makeAiRequest($prompt, 200);

        return $this->parseJsonResponse($response);
    }

    /**
     * Generate response example.
     */
    private function generateResponseExample(array $route): array
    {
        $prompt = $this->buildResponseExamplePrompt($route);
        $response = $this->makeAiRequest($prompt, 300);

        return $this->parseJsonResponse($response);
    }

    /**
     * Generate error responses.
     */
    private function generateErrorResponses(array $route): array
    {
        $prompt = $this->buildErrorResponsesPrompt($route);
        $response = $this->makeAiRequest($prompt, 200);

        return $this->parseErrorResponses($response);
    }

    /**
     * Generate relevant tags.
     */
    private function generateTags(array $route): array
    {
        $uri = $route['uri'];
        $segments = explode('/', trim($uri, '/'));

        // Remove 'api' prefix if present
        if ($segments[0] === 'api') {
            array_shift($segments);
        }

        $tags = [];
        foreach ($segments as $segment) {
            if (! preg_match('/\{.*\}/', $segment)) {
                $tags[] = ucfirst(str_replace('-', ' ', $segment));
            }
        }

        return array_slice($tags, 0, 3); // Max 3 tags
    }

    /**
     * Make AI request with proper error handling.
     */
    private function makeAiRequest(string $prompt, int $maxTokens): string
    {
        if (! $this->enabled) {
            return '';
        }

        try {
            return $this->client->describeEndpoint('', '', ['prompt' => $prompt, 'max_tokens' => $maxTokens]);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Build description prompt.
     */
    private function buildDescriptionPrompt(array $route): string
    {
        return sprintf(
            "Generate a detailed description for this Laravel API endpoint:\n\n" .
            "Controller: %s\n" .
            "Method: %s\n" .
            "HTTP Methods: %s\n" .
            "URI: %s\n" .
            "Route Name: %s\n\n" .
            "Provide a comprehensive description including:\n" .
            "- What this endpoint does\n" .
            "- When to use it\n" .
            "- Business logic involved\n" .
            "- Any important notes\n\n" .
            'Keep it professional and detailed (2-3 sentences).',
            $route['controller'] ?? 'Unknown',
            $route['method'] ?? 'Unknown',
            $route['http_methods'],
            $route['uri'],
            $route['name'] ?? 'No name'
        );
    }

    /**
     * Build summary prompt.
     */
    private function buildSummaryPrompt(array $route): string
    {
        return sprintf(
            "Generate a concise one-line summary for this API endpoint:\n\n" .
            "Controller: %s\n" .
            "Method: %s\n" .
            "HTTP Methods: %s\n" .
            "URI: %s\n\n" .
            "Example: 'Retrieves a list of users with pagination'",
            $route['controller'] ?? 'Unknown',
            $route['method'] ?? 'Unknown',
            $route['http_methods'],
            $route['uri']
        );
    }

    /**
     * Build parameters prompt.
     */
    private function buildParametersPrompt(array $route): string
    {
        return sprintf(
            "Analyze this Laravel API endpoint and generate parameter documentation:\n\n" .
            "Controller: %s\n" .
            "Method: %s\n" .
            "HTTP Methods: %s\n" .
            "URI: %s\n\n" .
            "Provide JSON format with:\n" .
            "- name: parameter name\n" .
            "- type: data type\n" .
            "- required: boolean\n" .
            "- description: what it does\n" .
            "- example: sample value\n\n" .
            'Include both path parameters and query parameters.',
            $route['controller'] ?? 'Unknown',
            $route['method'] ?? 'Unknown',
            $route['http_methods'],
            $route['uri']
        );
    }

    /**
     * Build request example prompt.
     */
    private function buildRequestExamplePrompt(array $route): string
    {
        return sprintf(
            "Generate a realistic request body example for this Laravel API endpoint:\n\n" .
            "Controller: %s\n" .
            "Method: %s\n" .
            "HTTP Methods: %s\n" .
            "URI: %s\n\n" .
            'Provide a JSON object with realistic sample data that would be sent to this endpoint.',
            $route['controller'] ?? 'Unknown',
            $route['method'] ?? 'Unknown',
            $route['http_methods'],
            $route['uri']
        );
    }

    /**
     * Build response example prompt.
     */
    private function buildResponseExamplePrompt(array $route): string
    {
        return sprintf(
            "Generate a realistic response example for this Laravel API endpoint:\n\n" .
            "Controller: %s\n" .
            "Method: %s\n" .
            "HTTP Methods: %s\n" .
            "URI: %s\n\n" .
            'Provide a JSON object showing what this endpoint would return on success.',
            $route['controller'] ?? 'Unknown',
            $route['method'] ?? 'Unknown',
            $route['http_methods'],
            $route['uri']
        );
    }

    /**
     * Build error responses prompt.
     */
    private function buildErrorResponsesPrompt(array $route): string
    {
        return sprintf(
            "Generate common error responses for this Laravel API endpoint:\n\n" .
            "Controller: %s\n" .
            "Method: %s\n" .
            "HTTP Methods: %s\n" .
            "URI: %s\n\n" .
            "Provide JSON format with:\n" .
            "- status_code: HTTP status\n" .
            "- message: error message\n" .
            "- description: when this error occurs\n\n" .
            'Include common errors like 400, 401, 403, 404, 422, 500.',
            $route['controller'] ?? 'Unknown',
            $route['method'] ?? 'Unknown',
            $route['http_methods'],
            $route['uri']
        );
    }

    /**
     * Parse parameters response from AI.
     */
    private function parseParametersResponse(string $response, array $route): array
    {
        $parameters = [];

        // Extract path parameters from URI
        preg_match_all('/\{([^}]+)\}/', $route['uri'], $matches);
        foreach ($matches[1] as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'type' => 'string',
                'description' => "The {$param} parameter",
                'example' => 'example-value',
            ];
        }

        // Try to parse AI response for additional parameters
        try {
            $aiParams = json_decode($response, true);
            if (is_array($aiParams)) {
                $parameters = array_merge($parameters, $aiParams);
            }
        } catch (Exception $e) {
            // Fallback to basic parameters
        }

        return $parameters;
    }

    /**
     * Parse JSON response from AI.
     */
    private function parseJsonResponse(string $response): array
    {
        try {
            $decoded = json_decode($response, true);

            return is_array($decoded) ? $decoded : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Parse error responses from AI.
     */
    private function parseErrorResponses(string $response): array
    {
        try {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (Exception $e) {
            // Fallback to common errors
        }

        return [
            ['status_code' => 400, 'message' => 'Bad Request', 'description' => 'Invalid request data'],
            ['status_code' => 401, 'message' => 'Unauthorized', 'description' => 'Authentication required'],
            ['status_code' => 404, 'message' => 'Not Found', 'description' => 'Resource not found'],
            ['status_code' => 422, 'message' => 'Validation Error', 'description' => 'Request validation failed'],
            ['status_code' => 500, 'message' => 'Server Error', 'description' => 'Internal server error'],
        ];
    }
}
