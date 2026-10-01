<?php

declare(strict_types=1);

namespace test\Core;

use test\Core\ValueObjects\Route;

final class UrlBuilder
{
    public function __construct() {}

    /**
     * @param array<string, scalar|null> $getParameters
     */
    public function build(Route $route, array $getParameters = []): string
    {
        $url = $route->path;

        foreach ($getParameters as $key => $value) {
            $replacement = $value === null ? '' : (string) $value;

            $result = preg_replace(
                '/@' . preg_quote($key, '/') . '(?::[^\s\/]+)?/',
                $replacement,
                $url
            );

            // preg_replace() returns null only on PCRE error; keep the previous
            // value in that case rather than poisoning the URL with null.
            if ($result !== null) {
                $url = $result;
            }
        }

        return $url;
    }
}
