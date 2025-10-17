<?php

namespace DigitalCoreHub\LaravelApiDocx\Support\Providers;

use DigitalCoreHub\LaravelApiDocx\Support\AiClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * OpenAI implementation of the AI client.
 */
class OpenAiClient implements AiClientInterface
{
    /**
     * @param GuzzleClient $httpClient
     * @param array<string, mixed> $config
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private readonly GuzzleClient $httpClient,
        private readonly array $config,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function describeEndpoint(string $controller, string $method, array $context): string
    {
        $apiKey = $this->config['api_key'] ?? null;

        if ($apiKey === null || $apiKey === '') {
            $this->log('OpenAI API key is missing.');

            return '';
        }

        $endpoint = $this->config['endpoint'] ?? 'https://api.openai.com/v1/chat/completions';
        $model = $this->config['model'] ?? 'gpt-4o-mini';

        $prompt = $this->buildPrompt($controller, $method, $context);

        try {
            $response = $this->httpClient->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You generate concise PHP docblock summaries for Laravel API endpoints.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => 200,
                ],
            ]);
        } catch (GuzzleException $exception) {
            $this->log('OpenAI request failed: ' . $exception->getMessage());

            return '';
        }

        $payload = json_decode((string) $response->getBody(), true);

        if (!is_array($payload)) {
            return '';
        }

        $content = $payload['choices'][0]['message']['content'] ?? '';

        return is_string($content) ? trim($content) : '';
    }

    /**
     * Construct the prompt describing the route context.
     *
     * @param string $controller
     * @param string $method
     * @param array<string, mixed> $context
     * @return string
     */
    private function buildPrompt(string $controller, string $method, array $context): string
    {
        $uri = $context['uri'] ?? '';
        $httpMethods = $context['http_methods'] ?? '';
        $name = $context['name'] ?? '';

        return implode(PHP_EOL, array_filter([
            sprintf('Controller: %s', $controller),
            sprintf('Method: %s', $method),
            sprintf('HTTP Methods: %s', $httpMethods),
            sprintf('URI: %s', $uri),
            $name !== '' ? sprintf('Route Name: %s', $name) : null,
            'Provide a one or two sentence description suitable for a PHP docblock summary.',
        ]));
    }

    /**
     * Log debug information when a logger is available.
     */
    private function log(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->warning($message);
        }
    }
}
