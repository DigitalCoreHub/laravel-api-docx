<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Services;

use Illuminate\Support\Str;

/**
 * Formats route documentation into a Markdown document.
 */
class MarkdownFormatter
{
    /**
     * Build the markdown string for the provided documentation entries.
     *
     * @param array<int, array<string, string>> $documentation
     */
    public function format(array $documentation): string
    {
        $lines = [
            '# API Documentation',
            '',
            'Generated automatically by **digitalcorehub/laravel-api-docx**.',
            '',
        ];

        foreach ($documentation as $entry) {
            $title = sprintf('## %s %s', $entry['http_methods'], $entry['uri']);
            $lines[] = $title;
            $lines[] = '';
            $lines[] = sprintf('- **Controller:** `%s@%s`', $entry['controller'], $entry['method']);

            if ($entry['name'] !== '') {
                $lines[] = sprintf('- **Name:** `%s`', $entry['name']);
            }

            $lines[] = '';
            $lines[] = Str::of($entry['description'])->trim()->isEmpty()
                ? '_No description available._'
                : $entry['description'];
            $lines[] = '';
        }

        return rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
    }
}
