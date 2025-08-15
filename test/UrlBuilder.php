<?php

class UrlBuilder
{
    public function __construct(private TestConfiguration $config) {}

    public function build(Route $route, array $getParameters = []): string
    {
        $url = $route->originalPath;
        foreach ($getParameters as $key => $value) {
            $url = preg_replace('/@' . preg_quote($key, '/') . '(?::[^\s\/]+)?/', $value, $url);
        }
        return $url;
    }

    public function convertToTestPath(string $path): string
    {
        return str_replace(
            ['@id:[0-9]+', '@year:[0-9]+', '@month:[0-9]+', '@table:[A-Za-z0-9_]+', '@filename', '@encodedEmail', '@token:[a-f0-9]+', '@articleId:[0-9]+', '@personId:[0-9]+', '@groupId:[0-9]+'],
            ['1',          '2025',         '01',            'test_table',           'test.jpg', 'test@example.foo', 'abc123',          '1',                 '1',                '1',],
            $path
        );
    }
}
