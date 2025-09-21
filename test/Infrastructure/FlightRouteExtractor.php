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
    private const REGEX_NEW_ROUTE = '/new\s+Route\(\s*[\'"]([^\'"]+)[\'"]/';
    private array $routes = [];

    public function extractRoutes(string $filePath, string $directoryPath): array
    {
        if (!file_exists($filePath)) throw new InvalidArgumentException("File $filePath doesn't exist");

        $content = file_get_contents($filePath);        
        $this->lookingForRoutes($content, self::REGEX_MAP_ROUTE);
        $this->lookingForRoutes($content, self::REGEX_DIRECT_ROUTE);
        $this->lookingForRoutes($content, self::REGEX_ICONS_ROUTE);

        $files = array_filter(scandir($directoryPath), fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'php');
        foreach ($files as $file) {
            $filePath = $directoryPath . DIRECTORY_SEPARATOR . $file;
            $this->lookingForRoutes(file_get_contents($filePath), self::REGEX_NEW_ROUTE);
        }
        usort($this->routes, fn(Route $a, Route $b) => strcmp($a->originalPath, $b->originalPath));
        return $this->routes;
    }

    #region Private functions
    private function lookingForRoutes(string $content, string $regex): void
    {
        preg_match_all($regex, $content, $matches);
        foreach ($matches[1] as $route) {
            $parsed = $this->parseRoute($route);
            if ($parsed) $this->routes[] = $parsed;
        }
    }

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
