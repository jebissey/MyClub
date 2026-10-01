<?php

declare(strict_types=1);

namespace test\Core;

use test\Core\ValueObjects\Route;

final class UrlBuilder
{
    public function __construct() {}

    public function build(Route $route, array $getParameters = []): string
    {
        $url = $route->path;
        foreach ($getParameters as $key => $value) {
            $url = preg_replace('/@' . preg_quote($key, '/') . '(?::[^\s\/]+)?/', (string)$value, $url);
        }
        return $url;
    }
}
