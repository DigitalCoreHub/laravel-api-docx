<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Provides a lightweight file-based cache for AI generated descriptions.
 */
class CacheManager
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $path,
        private readonly bool $enabled = true
    ) {}

    /**
     * Determine if caching is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Retrieve a cached value.
     */
    public function get(string $key): ?string
    {
        if (! $this->enabled || ! $this->files->exists($this->path)) {
            return null;
        }

        $cache = include $this->path;

        if (! is_array($cache)) {
            return null;
        }

        return $cache[$key] ?? null;
    }

    /**
     * Store a value in the cache file.
     */
    public function put(string $key, string $value): void
    {
        if (! $this->enabled) {
            return;
        }

        $cache = [];

        if ($this->files->exists($this->path)) {
            $existing = include $this->path;
            if (is_array($existing)) {
                $cache = $existing;
            }
        }

        $cache[$key] = $value;

        $this->files->ensureDirectoryExists(dirname($this->path));
        $export = '<?php return ' . var_export($cache, true) . ';';
        $this->files->put($this->path, $export);
    }
}
