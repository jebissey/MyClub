<?php

class FlightRouteExtractor implements RouteExtractorInterface
{
    public function extractRoutes(string $filePath): array
    {
        if (!file_exists($filePath)) throw new InvalidArgumentException("Le fichier $filePath n'existe pas");

        $content = file_get_contents($filePath);
        $routes = [];

        preg_match_all('/mapRoute\(\$flight,\s*[\'"]([^\'\"]+)[\'"]\s*,/', $content, $matches);
        foreach ($matches[1] as $route) {
            $parsed = $this->parseRoute($route);
            if ($parsed) {
                $routes[] = $parsed;
            }
        }
        preg_match_all('/\$flight->route\(\s*[\'"]([^\'\"]+)[\'"]/', $content, $directMatches);
        foreach ($directMatches[1] as $route) {
            $parsed = $this->parseRoute($route);
            if ($parsed) $routes[] = $parsed;
        }
        return $routes;
    }

    private function parseRoute(string $routeDefinition): ?Route
    {
        $parts = preg_split('/\s+/', trim($routeDefinition), 2);
        if (count($parts) !== 2) return null;
        $method = strtoupper($parts[0]);
        $path = $parts[1];
        if (!str_starts_with($path, '/')) return null;
        $hasParameters = preg_match('/@\w+(?::[^\s\/]+)?/', $path) > 0;

        return new Route(
            method: $method,
            originalPath: $path,
            hasParameters: $hasParameters,
            fullUrlTemplate: $path
        );
    }
}
