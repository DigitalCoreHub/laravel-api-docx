<?php

namespace DigitalCoreHub\LaravelApiDocx\Commands;

use DigitalCoreHub\LaravelApiDocx\Services\AiDocGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\DocBlockParser;
use DigitalCoreHub\LaravelApiDocx\Services\MarkdownFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\OpenApiFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\RouteCollector;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Artisan command that generates the API documentation markdown file.
 */
class GenerateDocsCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'api:docs 
                            {--format=both : Output format (markdown, openapi, both)}
                            {--output= : Custom output path}';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Generate API documentation for all API routes.';

    /**
     * @param RouteCollector $collector
     * @param DocBlockParser $docBlockParser
     * @param AiDocGenerator $aiDocGenerator
     * @param MarkdownFormatter $markdownFormatter
     * @param OpenApiFormatter $openApiFormatter
     * @param Filesystem $files
     */
    public function __construct(
        private readonly RouteCollector $collector,
        private readonly DocBlockParser $docBlockParser,
        private readonly AiDocGenerator $aiDocGenerator,
        private readonly MarkdownFormatter $markdownFormatter,
        private readonly OpenApiFormatter $openApiFormatter,
        private readonly Filesystem $files
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $routes = $this->collector->collect();

        if ($routes === []) {
            $this->warn('No API routes found.');

            return 0;
        }

        $documentation = [];

        foreach ($routes as $route) {
            $docBlock = null;

            if ($route['controller'] !== null && $route['method'] !== null) {
                $docBlock = $this->docBlockParser->extractSummary(
                    $route['controller'],
                    $route['method']
                );

                if (($docBlock === null || trim((string) $docBlock) === '') && $this->aiDocGenerator->isEnabled()) {
                    $docBlock = $this->aiDocGenerator->generate($route);
                }
            }

            if (is_string($docBlock) && trim($docBlock) === '') {
                $docBlock = null;
            }

            $documentation[] = [
                'http_methods' => $route['http_methods'],
                'uri' => $route['uri'],
                'controller' => $route['controller'] ?? 'Closure',
                'method' => $route['method'] ?? 'invoke',
                'name' => $route['name'],
                'description' => $docBlock ?? 'No description available.',
            ];
        }

        $format = $this->option('format');
        $customOutput = $this->option('output');
        
        $this->info(sprintf('Found %d API routes. Generating documentation...', count($documentation)));

        if ($format === 'markdown' || $format === 'both') {
            $this->generateMarkdown($documentation, $customOutput);
        }

        if ($format === 'openapi' || $format === 'both') {
            $this->generateOpenApi($documentation, $customOutput);
        }

        return 0;
    }

    /**
     * Generate Markdown documentation.
     *
     * @param array $documentation
     * @param string|null $customOutput
     */
    private function generateMarkdown(array $documentation, ?string $customOutput): void
    {
        $markdown = $this->markdownFormatter->format($documentation);
        $defaultOutput = function_exists('base_path') ? base_path('docs/api.md') : getcwd() . '/docs/api.md';
        $outputPath = $customOutput ?: (string) digitalcorehub_config('api-docs.output', $defaultOutput);

        $this->files->ensureDirectoryExists(dirname($outputPath));
        $this->files->put($outputPath, $markdown);

        $this->info(sprintf('Markdown documentation generated: %s', $outputPath));
    }

    /**
     * Generate OpenAPI documentation.
     *
     * @param array $documentation
     * @param string|null $customOutput
     */
    private function generateOpenApi(array $documentation, ?string $customOutput): void
    {
        $openApi = $this->openApiFormatter->format($documentation);
        $defaultOutput = function_exists('base_path') ? base_path('docs/api.json') : getcwd() . '/docs/api.json';
        $outputPath = $customOutput ?: (string) digitalcorehub_config('api-docs.openapi_output', $defaultOutput);

        $this->files->ensureDirectoryExists(dirname($outputPath));
        $this->files->put($outputPath, $openApi);

        $this->info(sprintf('OpenAPI documentation generated: %s', $outputPath));
    }
}
