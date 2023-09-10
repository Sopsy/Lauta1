<?php
declare(strict_types=1);

namespace TVGuide\Module\Router;

use function array_shift;
use function preg_match;

final class Router
{
    private $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param string $requestUrl
     * @return Route
     * @throws RouterException when no route is found
     */
    public function route(string $requestUrl): Route
    {
        foreach ($this->routes as $routeUrl => $route) {
            if (preg_match('#^' . $routeUrl . '$#', $requestUrl, $routeMatches)) {

                if (!is_array($route) || count($route) !== 2) {
                    throw new RouterException("Invalid route for {$requestUrl}, not a callable in array format");
                }

                return new Route($route, array_shift($routeMatches), $routeMatches);
            }
        }

        throw new RouterException("No route found for {$requestUrl}");
    }
}