<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests\Unit\Support;

use DigitalCoreHub\LaravelApiDocx\Support\CacheManager;
use Illuminate\Filesystem\Filesystem;
use DigitalCoreHub\LaravelApiDocx\Tests\TestCase;
use Mockery;

class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->cacheManager = new CacheManager(
            $this->filesystem,
            '/tmp/test-cache.php',
            true
        );
    }

    public function test_is_enabled_returns_true_when_enabled(): void
    {
        $this->assertTrue($this->cacheManager->isEnabled());
    }

    public function test_is_enabled_returns_false_when_disabled(): void
    {
        $cacheManager = new CacheManager(
            $this->filesystem,
            '/tmp/test-cache.php',
            false
        );

        $this->assertFalse($cacheManager->isEnabled());
    }

    public function test_get_returns_cached_value(): void
    {
        $key = 'test-key';
        $expectedValue = 'test-value';
        $cacheData = [$key => $expectedValue];

        $this->filesystem
            ->shouldReceive('exists')
            ->with('/tmp/test-cache.php')
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('get')
            ->with('/tmp/test-cache.php')
            ->andReturn('<?php return ' . var_export($cacheData, true) . ';');

        $result = $this->cacheManager->get($key);

        $this->assertEquals($expectedValue, $result);
    }

    public function test_get_returns_null_when_cache_disabled(): void
    {
        $cacheManager = new CacheManager(
            $this->filesystem,
            '/tmp/test-cache.php',
            false
        );

        $result = $cacheManager->get('test-key');

        $this->assertNull($result);
    }

    public function test_get_returns_null_when_key_not_found(): void
    {
        $key = 'non-existent-key';
        $cacheData = ['other-key' => 'other-value'];

        $this->filesystem
            ->shouldReceive('exists')
            ->with('/tmp/test-cache.php')
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('get')
            ->with('/tmp/test-cache.php')
            ->andReturn('<?php return ' . var_export($cacheData, true) . ';');

        $result = $this->cacheManager->get($key);

        $this->assertNull($result);
    }

    public function test_get_returns_null_when_cache_file_not_exists(): void
    {
        $this->filesystem
            ->shouldReceive('exists')
            ->with('/tmp/test-cache.php')
            ->andReturn(false);

        $result = $this->cacheManager->get('test-key');

        $this->assertNull($result);
    }

    public function test_put_stores_value_when_enabled(): void
    {
        $key = 'test-key';
        $value = 'test-value';
        $expectedCacheData = [$key => $value];

        $this->filesystem
            ->shouldReceive('exists')
            ->with('/tmp/test-cache.php')
            ->andReturn(false);

        $this->filesystem
            ->shouldReceive('ensureDirectoryExists')
            ->with(dirname('/tmp/test-cache.php'))
            ->once();

        $this->filesystem
            ->shouldReceive('put')
            ->with('/tmp/test-cache.php', Mockery::type('string'))
            ->once();

        $this->cacheManager->put($key, $value);

        // Assertion is implicit - if put was called, the test passes
        $this->assertTrue(true);
    }

    public function test_put_does_nothing_when_disabled(): void
    {
        $cacheManager = new CacheManager(
            $this->filesystem,
            '/tmp/test-cache.php',
            false
        );

        $this->filesystem
            ->shouldNotReceive('put');

        $cacheManager->put('test-key', 'test-value');
    }

    public function test_put_updates_existing_cache(): void
    {
        $key = 'test-key';
        $newValue = 'new-value';
        $existingCacheData = ['other-key' => 'other-value'];

        $this->filesystem
            ->shouldReceive('exists')
            ->with('/tmp/test-cache.php')
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('get')
            ->with('/tmp/test-cache.php')
            ->andReturn('<?php return ' . var_export($existingCacheData, true) . ';');

        $this->filesystem
            ->shouldReceive('put')
            ->with('/tmp/test-cache.php', Mockery::type('string'))
            ->once();

        $this->cacheManager->put($key, $newValue);
    }

    public function test_clear_removes_cache_file(): void
    {
        $this->filesystem
            ->shouldReceive('exists')
            ->with('/tmp/test-cache.php')
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('delete')
            ->with('/tmp/test-cache.php')
            ->once();

        $this->cacheManager->clear();
    }

    public function test_clear_does_nothing_when_file_not_exists(): void
    {
        $this->filesystem
            ->shouldReceive('exists')
            ->with('/tmp/test-cache.php')
            ->andReturn(false);

        $this->filesystem
            ->shouldNotReceive('delete');

        $this->cacheManager->clear();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
