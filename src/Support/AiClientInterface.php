<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Support;

/**
 * Contract for AI providers that can generate endpoint descriptions.
 */
interface AiClientInterface
{
    /**
     * Generate a description for the given endpoint context.
     *
     * @param array<string, mixed> $context
     */
    public function describeEndpoint(string $controller, string $method, array $context): string;
}
