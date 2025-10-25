<?php

declare(strict_types=1);

namespace DigitalCoreHub\LaravelApiDocx\Tests\Unit\Services;

use DigitalCoreHub\LaravelApiDocx\Services\RouteCollector;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;
use DigitalCoreHub\LaravelApiDocx\Tests\TestCase;
use Mockery;

class RouteCollectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_collects_api_routes(): void
    {
        // Register some test routes
        RouteFacade::get('/api/users', function () {
            return 'users';
        })->name('users.index');

        RouteFacade::post('/api/users', function () {
            return 'create user';
        })->name('users.store');

        RouteFacade::get('/api/posts', function () {
            return 'posts';
        })->name('posts.index');

        // Register a non-API route (should be ignored)
        RouteFacade::get('/admin/dashboard', function () {
            return 'dashboard';
        })->name('admin.dashboard');

        $collector = new RouteCollector();
        $routes = $collector->collect();

        $this->assertCount(3, $routes);
        
        // Check first route
        $this->assertEquals('/api/users', $routes[0]['uri']);
        $this->assertEquals('GET', $routes[0]['http_methods']);
        $this->assertEquals('users.index', $routes[0]['name']);
        $this->assertNull($routes[0]['controller']);
        $this->assertNull($routes[0]['method']);

        // Check second route
        $this->assertEquals('/api/users', $routes[1]['uri']);
        $this->assertEquals('POST', $routes[1]['http_methods']);
        $this->assertEquals('users.store', $routes[1]['name']);

        // Check third route
        $this->assertEquals('/api/posts', $routes[2]['uri']);
        $this->assertEquals('GET', $routes[2]['http_methods']);
        $this->assertEquals('posts.index', $routes[2]['name']);
    }

    public function test_ignores_non_api_routes(): void
    {
        // Register non-API routes
        RouteFacade::get('/admin/users', function () {
            return 'admin users';
        });

        RouteFacade::get('/dashboard', function () {
            return 'dashboard';
        });

        RouteFacade::get('/api/users', function () {
            return 'api users';
        });

        $collector = new RouteCollector();
        $routes = $collector->collect();

        $this->assertCount(1, $routes);
        $this->assertEquals('/api/users', $routes[0]['uri']);
    }

    public function test_handles_controller_routes(): void
    {
        // Mock a controller route
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('uri')->andReturn('/api/users');
        $route->shouldReceive('getName')->andReturn('users.index');
        $route->shouldReceive('methods')->andReturn(['GET']);
        $route->shouldReceive('getActionName')->andReturn('App\Http\Controllers\UserController@index');
        $route->shouldReceive('middleware')->andReturn([]);

        // Mock RouteCollection
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection->shouldReceive('getIterator')->andReturn(new \ArrayIterator([$route]));

        // Mock Route facade
        RouteFacade::shouldReceive('getRoutes')->andReturn($routeCollection);

        $collector = new RouteCollector();
        $routes = $collector->collect();

        $this->assertCount(1, $routes);
        $this->assertEquals('App\Http\Controllers\UserController', $routes[0]['controller']);
        $this->assertEquals('index', $routes[0]['method']);
    }

    public function test_handles_invokable_controllers(): void
    {
        // Mock an invokable controller route
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('uri')->andReturn('/api/users');
        $route->shouldReceive('getName')->andReturn('users.index');
        $route->shouldReceive('methods')->andReturn(['GET']);
        $route->shouldReceive('getActionName')->andReturn('App\Http\Controllers\UserController');
        $route->shouldReceive('middleware')->andReturn([]);

        // Mock RouteCollection
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection->shouldReceive('getIterator')->andReturn(new \ArrayIterator([$route]));

        // Mock Route facade
        RouteFacade::shouldReceive('getRoutes')->andReturn($routeCollection);

        $collector = new RouteCollector();
        $routes = $collector->collect();

        $this->assertCount(1, $routes);
        $this->assertEquals('App\Http\Controllers\UserController', $routes[0]['controller']);
        $this->assertEquals('__invoke', $routes[0]['method']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
