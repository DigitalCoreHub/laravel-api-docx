<?php

namespace DigitalCoreHub\LaravelApiDocx\Commands;

use DigitalCoreHub\LaravelApiDocx\Services\AdvancedAiGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\AiDocGenerator;
use DigitalCoreHub\LaravelApiDocx\Services\DocBlockParser;
use DigitalCoreHub\LaravelApiDocx\Services\MarkdownFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\OpenApiFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\PostmanFormatter;
use DigitalCoreHub\LaravelApiDocx\Services\ReDocGenerator;
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
                            {--format=all : Output format (markdown, openapi, postman, redoc, all)}
                            {--output= : Custom output path}
                            {--advanced : Generate advanced AI documentation with examples}
                            {--watch : Watch for changes and regenerate automatically}';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Generate API documentation for all API routes.';

    /**
     * @param RouteCollector $collector
     * @param DocBlockParser $docBlockParser
     * @param AiDocGenerator $aiDocGenerator
     * @param AdvancedAiGenerator $advancedAiGenerator
     * @param MarkdownFormatter $markdownFormatter
     * @param OpenApiFormatter $openApiFormatter
     * @param PostmanFormatter $postmanFormatter
     * @param ReDocGenerator $reDocGenerator
     * @param Filesystem $files
     */
    public function __construct(
        private readonly RouteCollector $collector,
        private readonly DocBlockParser $docBlockParser,
        private readonly AiDocGenerator $aiDocGenerator,
        private readonly AdvancedAiGenerator $advancedAiGenerator,
        private readonly MarkdownFormatter $markdownFormatter,
        private readonly OpenApiFormatter $openApiFormatter,
        private readonly PostmanFormatter $postmanFormatter,
        private readonly ReDocGenerator $reDocGenerator,
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
        $advanced = $this->option('advanced');
        $watch = $this->option('watch');
        
        $this->info(sprintf('Found %d API routes. Generating documentation...', count($documentation)));

        if ($watch) {
            $this->info('Watch mode enabled. Press Ctrl+C to stop.');
            $this->watchForChanges($documentation, $format, $customOutput, $advanced);
            return 0;
        }

        $this->generateDocumentation($documentation, $format, $customOutput, $advanced);

        return 0;
    }

    /**
     * Generate documentation based on format.
     */
    private function generateDocumentation(array $documentation, string $format, ?string $customOutput, bool $advanced): void
    {
        if ($advanced) {
            $this->info('Generating advanced AI documentation...');
            $documentation = $this->enhanceWithAdvancedAi($documentation);
        }

        if ($format === 'markdown' || $format === 'all') {
            $this->generateMarkdown($documentation, $customOutput);
        }

        if ($format === 'openapi' || $format === 'all') {
            $this->generateOpenApi($documentation, $customOutput);
        }

        if ($format === 'postman' || $format === 'all') {
            $this->generatePostman($documentation, $customOutput);
        }

        if ($format === 'redoc' || $format === 'all') {
            $this->generateReDoc($documentation, $customOutput);
        }
    }

    /**
     * Enhance documentation with advanced AI features.
     */
    private function enhanceWithAdvancedAi(array $documentation): array
    {
        $enhanced = [];

        foreach ($documentation as $route) {
            $enhancedRoute = $route;
            
            if ($this->advancedAiGenerator->isEnabled()) {
                $aiDocs = $this->advancedAiGenerator->generateComprehensiveDocs($route);
                $enhancedRoute = array_merge($route, $aiDocs);
            }

            $enhanced[] = $enhancedRoute;
        }

        return $enhanced;
    }

    /**
     * Watch for file changes and regenerate documentation.
     */
    private function watchForChanges(array $documentation, string $format, ?string $customOutput, bool $advanced): void
    {
        $lastModified = 0;
        
        while (true) {
            $currentModified = $this->getLastModifiedTime();
            
            if ($currentModified > $lastModified) {
                $this->info('Changes detected. Regenerating documentation...');
                $this->generateDocumentation($documentation, $format, $customOutput, $advanced);
                $lastModified = $currentModified;
            }
            
            sleep(2);
        }
    }

    /**
     * Get the last modified time of relevant files.
     */
    private function getLastModifiedTime(): int
    {
        $files = [
            base_path('routes/api.php'),
            base_path('app/Http/Controllers'),
        ];

        $maxTime = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                $maxTime = max($maxTime, filemtime($file));
            } elseif (is_dir($file)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($file));
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $maxTime = max($maxTime, $file->getMTime());
                    }
                }
            }
        }

        return $maxTime;
    }

    /**
     * Generate Markdown documentation.
     */
    private function generateMarkdown(array $documentation, ?string $customOutput): void
    {
        $markdown = $this->markdownFormatter->format($documentation);
        $defaultOutput = function_exists('base_path') ? base_path('docs/api.md') : getcwd() . '/docs/api.md';
        $outputPath = $customOutput ?: (string) digitalcorehub_config('api-docs.output', $defaultOutput);

        $this->files->ensureDirectoryExists(dirname($outputPath));
        $this->files->put($outputPath, $markdown);

        $this->info(sprintf('📝 Markdown documentation generated: %s', $outputPath));
    }

    /**
     * Generate OpenAPI documentation.
     */
    private function generateOpenApi(array $documentation, ?string $customOutput): void
    {
        $openApi = $this->openApiFormatter->format($documentation);
        $defaultOutput = function_exists('base_path') ? base_path('docs/api.json') : getcwd() . '/docs/api.json';
        $outputPath = $customOutput ?: (string) digitalcorehub_config('api-docs.openapi_output', $defaultOutput);

        $this->files->ensureDirectoryExists(dirname($outputPath));
        $this->files->put($outputPath, $openApi);

        $this->info(sprintf('🔗 OpenAPI documentation generated: %s', $outputPath));
    }

    /**
     * Generate Postman collection.
     */
    private function generatePostman(array $documentation, ?string $customOutput): void
    {
        $postman = $this->postmanFormatter->format($documentation);
        $defaultOutput = function_exists('base_path') ? base_path('docs/api.postman.json') : getcwd() . '/docs/api.postman.json';
        $outputPath = $customOutput ?: (string) digitalcorehub_config('api-docs.postman_output', $defaultOutput);

        $this->files->ensureDirectoryExists(dirname($outputPath));
        $this->files->put($outputPath, $postman);

        $this->info(sprintf('📮 Postman collection generated: %s', $outputPath));
    }

    /**
     * Generate ReDoc HTML page.
     */
    private function generateReDoc(array $documentation, ?string $customOutput): void
    {
        $openApi = $this->openApiFormatter->format($documentation);
        $html = $this->reDocGenerator->generate($openApi);
        $defaultOutput = function_exists('base_path') ? base_path('docs/api.html') : getcwd() . '/docs/api.html';
        $outputPath = $customOutput ?: (string) digitalcorehub_config('api-docs.redoc_output', $defaultOutput);

        $this->files->ensureDirectoryExists(dirname($outputPath));
        $this->files->put($outputPath, $html);

        $this->info(sprintf('🌐 ReDoc HTML page generated: %s', $outputPath));
    }
}
