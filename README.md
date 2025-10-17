# Laravel API Docx

`digitalcorehub/laravel-api-docx` is an AI-assisted API documentation generator for Laravel 12 applications. It analyses your API routes, enriches missing controller docblocks with OpenAI, and compiles a comprehensive `docs/api.md` reference file.

## Installation

```bash
composer require digitalcorehub/laravel-api-docx
```

Publish the configuration file to tailor output paths, AI settings, or caching:

```bash
php artisan vendor:publish --tag=api-docs-config
```

## Usage

Trigger the documentation build using the dedicated Artisan command:

```bash
php artisan api:docs
```

The command scans `routes/api.php`, extracts controller metadata, and writes a Markdown report to `docs/api.md` (configurable via `config/api-docs.php`). When docblocks are absent, the package prompts OpenAI to generate a concise summary and caches the result for subsequent runs.

## Configuration

The published `api-docs.php` file exposes the following keys:

- `output`: Destination of the generated Markdown file.
- `enable_ai`: Toggle AI-based generation globally.
- `ai`: Provider options (model, API key, endpoint, timeout).
- `cache`: Enable/disable caching and configure the cache file location.

Set the `OPENAI_API_KEY` environment variable to authenticate requests to OpenAI. You may also override `OPENAI_API_ENDPOINT` if you are using a compatible proxy or gateway.

## Testing the Command

Within a Laravel project containing API routes, run:

```bash
php artisan api:docs
```

You should see a success message pointing to the generated Markdown file. Re-running the command will reuse cached AI descriptions when available.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
