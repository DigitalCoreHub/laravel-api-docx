<?php

if (!function_exists('digitalcorehub_config')) {
    /**
     * Retrieve configuration values safely when the config helper is not available.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    function digitalcorehub_config(string $key, mixed $default = null): mixed
    {
        if (function_exists('config')) {
            return config($key, $default);
        }

        return $default;
    }
}

if (!function_exists('digitalcorehub_storage_path')) {
    /**
     * Resolve the storage path when the framework helper is missing.
     *
     * @param string $path
     * @return string
     */
    function digitalcorehub_storage_path(string $path = ''): string
    {
        if (function_exists('storage_path')) {
            return storage_path($path);
        }

        $base = getcwd();

        return $path === '' ? $base . '/storage' : $base . '/storage/' . ltrim($path, '/');
    }
}
