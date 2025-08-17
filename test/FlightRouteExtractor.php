<?php

class FlightRouteExtractor implements RouteExtractorInterface
{
    private const REGEX_MAP_ROUTE = '/mapRoute\(\$flight,\s*[\'"]([^\'"]+)[\'"]\s*,/';
    private const REGEX_DIRECT_ROUTE = '/\$flight->route\(\s*[\'"]([^\'"]+)[\'"]/';
    private const REGEX_ROUTE_PARAM = '/@\w+(?::[^\s\/]+)?/';

    public function extractRoutes(string $filePath): array
    {
        if (!file_exists($filePath)) throw new InvalidArgumentException("Le fichier $filePath n'existe pas");

        $content = file_get_contents($filePath);
        $routes = [];
        preg_match_all(self::REGEX_MAP_ROUTE, $content, $matches);
        foreach ($matches[1] as $route) {
            $parsed = $this->parseRoute($route);
            if ($parsed) {
                $routes[] = $parsed;
            }
        }
        preg_match_all(self::REGEX_DIRECT_ROUTE, $content, $directMatches);
        foreach ($directMatches[1] as $route) {
            $parsed = $this->parseRoute($route);
            if ($parsed) $routes[] = $parsed;
        }
        usort($routes, fn(Route $a, Route $b) => strcmp($a->originalPath, $b->originalPath));
        return $routes;
    }

    #region Private functions
    private function parseRoute(string $routeDefinition): ?Route
    {
        $parts = preg_split('/\s+/', trim($routeDefinition), 2);
        if (count($parts) !== 2) return null;
        $path = $parts[1];
        if (!str_starts_with($path, '/')) return null;

        return new Route(
            method: strtoupper($parts[0]),
            originalPath: $path,
            hasParameters: preg_match(self::REGEX_ROUTE_PARAM, $path) > 0,
            testedPath: ''
        );
    }
}
