<?php

namespace DigitalCoreHub\LaravelApiDocx\Services;

use DigitalCoreHub\LaravelApiDocx\Support\AiClientInterface;
use DigitalCoreHub\LaravelApiDocx\Support\CacheManager;

/**
 * Generates docblocks using AI services when no manual description exists.
 */
class AiDocGenerator
{
    /**
     * @param AiClientInterface $client
     * @param CacheManager $cacheManager
     * @param bool $enabled
     */
    public function __construct(
        private readonly AiClientInterface $client,
        private readonly CacheManager $cacheManager,
        private readonly bool $enabled
    ) {
    }

    /**
     * Determine if AI generation is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Generate a description for the provided route metadata.
     *
     * @param array<string, mixed> $route
     * @return string
     */
    public function generate(array $route): string
    {
        $cacheKey = sprintf('%s:%s', $route['http_methods'], $route['uri']);

        if ($this->cacheManager->isEnabled()) {
            $cached = $this->cacheManager->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $description = $this->client->describeEndpoint(
            (string) ($route['controller'] ?? ''),
            (string) ($route['method'] ?? ''),
            $route
        );

        if ($this->cacheManager->isEnabled() && $description !== '') {
            $this->cacheManager->put($cacheKey, $description);
        }

        return $description;
    }
}
