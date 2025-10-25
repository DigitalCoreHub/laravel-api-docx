<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests\Unit\Services;

use DigitalCoreHub\LaravelApiDocx\Services\DocBlockParser;
use DigitalCoreHub\LaravelApiDocx\Tests\TestCase;

class DocBlockParserTest extends TestCase
{
    private DocBlockParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DocBlockParser;
    }

    /**
     * @covers \DigitalCoreHub\LaravelApiDocx\Services\DocBlockParser::extractSummary
     */
    public function test_extracts_summary_from_docblock(): void
    {
        $controller = TestController::class;
        $method = 'index';

        $result = $this->parser->extractSummary($controller, $method);

        $this->assertEquals('Retrieves a list of users.', $result);
    }

    public function test_returns_null_for_missing_docblock(): void
    {
        $controller = TestController::class;
        $method = 'noDocblock';

        $result = $this->parser->extractSummary($controller, $method);

        $this->assertNull($result);
    }

    public function test_handles_invokable_controller(): void
    {
        $controller = InvokableTestController::class;
        $method = '';

        $result = $this->parser->extractSummary($controller, $method);

        $this->assertEquals('Invokable controller for handling requests.', $result);
    }

    public function test_returns_null_for_invalid_controller(): void
    {
        $controller = 'NonExistentController';
        $method = 'index';

        $result = $this->parser->extractSummary($controller, $method);

        $this->assertNull($result);
    }

    public function test_returns_null_for_invalid_method(): void
    {
        $controller = TestController::class;
        $method = 'nonExistentMethod';

        $result = $this->parser->extractSummary($controller, $method);

        $this->assertNull($result);
    }

    public function test_ignores_annotation_lines(): void
    {
        $controller = TestController::class;
        $method = 'withAnnotations';

        $result = $this->parser->extractSummary($controller, $method);

        $this->assertEquals('Method with annotations.', $result);
    }

    public function test_handles_empty_docblock(): void
    {
        $controller = TestController::class;
        $method = 'emptyDocblock';

        $result = $this->parser->extractSummary($controller, $method);

        $this->assertNull($result);
    }
}

/**
 * Test controller for DocBlockParser tests.
 */
class TestController
{
    /**
     * Retrieves a list of users.
     */
    public function index(): void
    {
        // Implementation
    }

    public function noDocblock(): void
    {
        // No docblock
    }

    /**
     * Method with annotations.
     */
    public function withAnnotations(string $id): array
    {
        return [];
    }

    public function emptyDocblock(): void
    {
        // Empty docblock
    }
}

/**
 * Invokable test controller.
 */
class InvokableTestController
{
    /**
     * Invokable controller for handling requests.
     */
    public function __invoke(): void
    {
        // Implementation
    }
}
