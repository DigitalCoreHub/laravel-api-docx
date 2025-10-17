<?php

namespace DigitalCoreHub\LaravelApiDocx\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * Collects API routes from the application router.
 */
class RouteCollector
{
    /**
     * Collect structured information for all API routes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function collect(): array
    {
        $routes = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (!$route instanceof Route) {
                continue;
            }

            $uri = $route->uri();

            if (!Str::startsWith($uri, 'api')) {
                continue;
            }

            $actionName = $route->getActionName();
            $controller = null;
            $method = null;

            if (is_string($actionName) && Str::contains($actionName, '@')) {
                [$controller, $method] = explode('@', $actionName);
            } elseif (is_string($actionName) && class_exists($actionName)) {
                $controller = $actionName;
                $method = '__invoke';
            }

            $routes[] = [
                'uri' => $uri,
                'name' => $route->getName() ?? '',
                'http_methods' => $this->filterHttpMethods($route->methods()),
                'controller' => $controller,
                'method' => $method,
                'middleware' => Arr::wrap($route->middleware()),
            ];
        }

        return $routes;
    }

    /**
     * Normalise HTTP methods excluding HEAD when GET is present.
     *
     * @param array<int, string> $methods
     * @return string
     */
    private function filterHttpMethods(array $methods): string
    {
        $normalised = array_filter($methods, static function (string $method): bool {
            return $method !== 'HEAD';
        });

        return implode('|', $normalised);
    }
}
