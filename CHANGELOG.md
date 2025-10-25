# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release
- AI-powered documentation generation using OpenAI
- Support for both Markdown and OpenAPI 3.0 output formats
- Automatic route collection from Laravel API routes
- DocBlock parsing and AI enhancement
- Intelligent caching system
- Laravel 12 compatibility
- PHP 8.3+ support
- Command-line interface with multiple output options
- Configurable output paths and AI settings

### Features
- **Route Analysis**: Automatically scans and analyzes API routes
- **AI Integration**: Uses OpenAI to generate meaningful descriptions for missing docblocks
- **Multiple Formats**: Generates documentation in both Markdown and OpenAPI formats
- **Smart Caching**: Avoids redundant AI API calls with intelligent caching
- **Zero Configuration**: Works out of the box with sensible defaults
- **Flexible Output**: Customizable output paths and formats

### Technical Details
- Built for Laravel 12 with PHP 8.3+
- Uses Guzzle HTTP client for OpenAI API communication
- Implements PSR-4 autoloading
- Follows Laravel package development best practices
- Includes comprehensive error handling and logging
