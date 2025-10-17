<?php

namespace DigitalCoreHub\LaravelApiDocx\Support;

/**
 * Contract for AI providers that can generate endpoint descriptions.
 */
interface AiClientInterface
{
    /**
     * Generate a description for the given endpoint context.
     *
     * @param string $controller
     * @param string $method
     * @param array<string, mixed> $context
     * @return string
     */
    public function describeEndpoint(string $controller, string $method, array $context): string;
}
