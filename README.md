# Laravel API Docx

[![Latest Version on Packagist](https://img.shields.io/packagist/v/digitalcorehub/laravel-api-docx.svg?style=flat-square)](https://packagist.org/packages/digitalcorehub/laravel-api-docx)
[![Total Downloads](https://img.shields.io/packagist/dt/digitalcorehub/laravel-api-docx.svg?style=flat-square)](https://packagist.org/packages/digitalcorehub/laravel-api-docx)
[![License](https://img.shields.io/packagist/l/digitalcorehub/laravel-api-docx.svg?style=flat-square)](https://packagist.org/packages/digitalcorehub/laravel-api-docx)

**Write code. Laravel API Docx writes the docs.**

AI-powered automatic API documentation generator for Laravel 12. This package automatically reads your API routes and generates comprehensive documentation in both Markdown and OpenAPI formats using artificial intelligence.

## Features

- 🤖 **AI-Powered Documentation**: Automatically generates meaningful descriptions using OpenAI
- 📝 **Multiple Output Formats**: Generate documentation in Markdown and OpenAPI 3.0 formats
- 🔍 **Smart Route Analysis**: Automatically extracts HTTP methods, URIs, controllers, and methods
- 📚 **DocBlock Integration**: Uses existing docblocks when available, generates AI descriptions when missing
- ⚡ **Caching**: Intelligent caching system to avoid redundant AI API calls
- 🎯 **Laravel 12 Ready**: Built specifically for Laravel 12 with PHP 8.3+ support
- 🚀 **Zero Configuration**: Works out of the box with sensible defaults

## Installation

You can install the package via Composer:

```bash
composer require digitalcorehub/laravel-api-docx
```

The package will automatically register itself.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="DigitalCoreHub\LaravelApiDocx\LaravelApiDocxServiceProvider" --tag="api-docs-config"
```

This will create a `config/api-docs.php` file where you can configure:

- Output paths for Markdown and OpenAPI files
- AI settings (OpenAI API key, model, timeout)
- Caching preferences

### Environment Variables

Add your OpenAI API key to your `.env` file:

```env
OPENAI_API_KEY=your-openai-api-key-here
OPENAI_API_ENDPOINT=https://api.openai.com/v1/chat/completions
```

## Usage

### Basic Usage

Generate documentation for all your API routes:

```bash
php artisan api:docs
```

This will create:
- `docs/api.md` - Markdown documentation
- `docs/api.json` - OpenAPI 3.0 specification

### Advanced Usage

#### Generate Only Markdown Documentation

```bash
php artisan api:docs --format=markdown
```

#### Generate Only OpenAPI Documentation

```bash
php artisan api:docs --format=openapi
```

#### Custom Output Path

```bash
php artisan api:docs --output=/path/to/custom/documentation.md
```

## How It Works

1. **Route Collection**: The package scans all registered routes and filters for API routes (those starting with `api/`)
2. **Controller Analysis**: For each route, it extracts the controller and method information
3. **DocBlock Parsing**: It checks for existing docblock comments in your controller methods
4. **AI Generation**: If no docblock exists or it's empty, it uses OpenAI to generate meaningful descriptions
5. **Documentation Generation**: Creates comprehensive documentation in your chosen format(s)

## Example Output

### Markdown Format

```markdown
# API Documentation

Generated automatically by **digitalcorehub/laravel-api-docx**.

## GET|POST /api/users

- **Controller:** `App\Http\Controllers\UserController@index`
- **Name:** `users.index`

Retrieves a list of all users with optional filtering and pagination.

## GET /api/users/{user}

- **Controller:** `App\Http\Controllers\UserController@show`
- **Name:** `users.show`

Displays the specified user's details including profile information and settings.
```

### OpenAPI Format

```json
{
  "openapi": "3.0.0",
  "info": {
    "title": "API Documentation",
    "description": "Generated automatically by digitalcorehub/laravel-api-docx",
    "version": "1.0.0"
  },
  "servers": [
    {
      "url": "http://localhost",
      "description": "API Server"
    }
  ],
  "paths": {
    "/users": {
      "get": {
        "summary": "Retrieves a list of all users",
        "description": "Retrieves a list of all users with optional filtering and pagination.",
        "tags": ["Users"],
        "responses": {
          "200": {
            "description": "Successful response",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object"
                }
              }
            }
          }
        }
      }
    }
  }
}
```

## Configuration Options

### AI Settings

```php
'ai' => [
    'provider' => 'openai',
    'model' => 'gpt-4o-mini', // or 'gpt-4', 'gpt-3.5-turbo'
    'api_key' => env('OPENAI_API_KEY'),
    'endpoint' => env('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
    'timeout' => 15,
],
```

### Output Paths

```php
'output' => base_path('docs/api.md'),
'openapi_output' => base_path('docs/api.json'),
```

### Caching

```php
'cache' => [
    'enabled' => true,
    'store_path' => storage_path('app/laravel-api-docx-cache.php'),
],
```

## Requirements

- PHP 8.3+
- Laravel 12.x
- OpenAI API key (for AI-generated descriptions)

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email info@digitalcorehub.com instead of using the issue tracker.

## Credits

- [Digital Core Hub](https://github.com/digitalcorehub)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

---

**Made with ❤️ by [Digital Core Hub](https://digitalcorehub.com)**