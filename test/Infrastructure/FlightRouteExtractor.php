<?php

declare(strict_types=1);

namespace test\Infrastructure;

use InvalidArgumentException;

use test\Core\Route;
use test\Interfaces\RouteExtractorInterface;

class FlightRouteExtractor implements RouteExtractorInterface
{
    private const REGEX_MAP_ROUTE = '/mapRoute\(\$flight,\s*[\'"]([^\'"]+)[\'"]\s*,/';
    private const REGEX_DIRECT_ROUTE = '/\$flight->route\(\s*[\'"]([^\'"]+)[\'"]/';
    private const REGEX_ICONS_ROUTE = '/(\/[A-Za-z0-9\-]+\.[A-Za-z]{3})\'\s*=>/s';
    private const REGEX_ROUTE_PARAM = '/@\w+(?::[^\s\/]+)?/';

    public function extractRoutes(string $filePath): array
    {
        if (!file_exists($filePath)) throw new InvalidArgumentException("File $filePath doesn't exist");

        $content = file_get_contents($filePath);
        $routes = [];
        preg_match_all(self::REGEX_MAP_ROUTE, $content, $matches);
        foreach ($matches[1] as $route) {
            $parsed = $this->parseRoute($route);
            if ($parsed) $routes[] = $parsed;
        }
        preg_match_all(self::REGEX_DIRECT_ROUTE, $content, $directMatches);
        foreach ($directMatches[1] as $route) {
            $parsed = $this->parseRoute($route);
            if ($parsed) $routes[] = $parsed;
        }
        preg_match_all(self::REGEX_ICONS_ROUTE, $content, $iconRoutes);
        foreach ($iconRoutes[1] as $route) {
            $parsed = $this->parseRoute($route, 'GET');
            if ($parsed) {
                $routes[] = $parsed;
            }
        }

        usort($routes, fn(Route $a, Route $b) => strcmp($a->originalPath, $b->originalPath));
        return $routes;
    }

    #region Private functions
    private function parseRoute(string $routeDefinition, string $defaultMethod = 'GET'): ?Route
    {
        $parts = preg_split('/\s+/', trim($routeDefinition), 2);
        if (count($parts) === 1) {
            $path = $parts[0];
            if (!str_starts_with($path, '/')) return null;
            return new Route(
                method: strtoupper($defaultMethod),
                originalPath: $path,
                hasParameters: preg_match(self::REGEX_ROUTE_PARAM, $path) > 0,
                testedPath: ''
            );
        }
        if (count($parts) !== 2) return null;
        $method = strtoupper($parts[0]);
        $path = $parts[1];
        if (!str_starts_with($path, '/')) return null;

        return new Route(
            method: $method,
            originalPath: $path,
            hasParameters: preg_match(self::REGEX_ROUTE_PARAM, $path) > 0,
            testedPath: ''
        );
    }
    #endregion
}
