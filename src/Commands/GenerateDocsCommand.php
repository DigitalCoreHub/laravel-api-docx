<?php

namespace DigitalCoreHub\LaravelApiDocx\Commands;

use DigitalCoreHub\LaravelApiDocx\Services\AiDocGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\DocBlockParser;
use DigitalCoreHub\LaravelApiDocx\Services\MarkdownFormatter;
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
    protected $signature = 'api:docs';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Generate API documentation for all API routes.';

    /**
     * @param RouteCollector $collector
     * @param DocBlockParser $docBlockParser
     * @param AiDocGenerator $aiDocGenerator
     * @param MarkdownFormatter $formatter
     * @param Filesystem $files
     */
    public function __construct(
        private readonly RouteCollector $collector,
        private readonly DocBlockParser $docBlockParser,
        private readonly AiDocGenerator $aiDocGenerator,
        private readonly MarkdownFormatter $formatter,
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

            return self::SUCCESS;
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

        $markdown = $this->formatter->format($documentation);
        $defaultOutput = function_exists('base_path') ? base_path('docs/api.md') : getcwd() . '/docs/api.md';
        $outputPath = (string) digitalcorehub_config('api-docs.output', $defaultOutput);

        $this->files->ensureDirectoryExists(dirname($outputPath));
        $this->files->put($outputPath, $markdown);

        $this->info(sprintf('API documentation generated: %s', $outputPath));

        return self::SUCCESS;
    }
}
