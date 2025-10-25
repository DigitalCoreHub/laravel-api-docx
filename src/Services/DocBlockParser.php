<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Services;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Parses docblocks from controller methods.
 */
class DocBlockParser
{
    /**
     * Extract the first meaningful summary line from a docblock.
     */
    public function extractSummary(string $controller, string $method): ?string
    {
        try {
            $reflectionMethod = $this->resolveMethod($controller, $method);
        } catch (ReflectionException) {
            return null;
        }

        $docComment = $reflectionMethod->getDocComment();

        if ($docComment === false) {
            return null;
        }

        $lines = preg_split('/\r?\n/', $docComment) ?: [];
        $cleaned = [];

        foreach ($lines as $line) {
            $line = trim($line, "/*\t ");
            if ($line === '' || str_starts_with($line, '@')) {
                continue;
            }

            $cleaned[] = $line;
        }

        return $cleaned[0] ?? null;
    }

    /**
     * Resolve the reflection method handling invokable controllers.
     *
     * @throws ReflectionException
     */
    private function resolveMethod(string $controller, string $method): ReflectionMethod
    {
        $reflectionClass = new ReflectionClass($controller);

        if ($method === '' || ! $reflectionClass->hasMethod($method)) {
            if ($reflectionClass->hasMethod('__invoke')) {
                return $reflectionClass->getMethod('__invoke');
            }

            throw new ReflectionException(sprintf('Method %s not found on %s', $method, $controller));
        }

        return $reflectionClass->getMethod($method);
    }
}
