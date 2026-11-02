<?php

declare(strict_types=1);

namespace app\helpers;

use flight\net\Request;
use app\enums\FilterInputRule;

/**
 * Single entry point for reading the current HTTP request (query string, form data, cookies).
 * Replaces direct reads of $_GET, $_POST and $_COOKIE.
 */
final readonly class RequestInput
{
    public function __construct(private Request $request)
    {
    }

    /**
     * Builds a query string by merging the current query parameters with the given ones.
     *
     * Called from controllers and, through the buildUrl() Latte function registered in
     * Application, from templates: search both PHP and .latte files before renaming it.
     *
     * @param array<string, scalar|null> $newParams
     */
    public function buildUrl(array $newParams): string
    {
        /** @var array<string, scalar|null> $params */
        $params = array_merge($this->query(), $newParams);

        return '?' . http_build_query($params);
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $this->request->query->getData();
    }

    /** @return array<string, mixed> */
    public function form(): array
    {
        return $this->request->data->getData();
    }

    public function cookie(string $name): ?string
    {
        $value = $this->request->cookies->getData()[$name] ?? null;

        return is_string($value) ? $value : null;
    }


    public function hasQuery(string $key): bool
    {
        return isset($this->query()[$key]);
    }


    /**
     * Accepts "1"/"on"/"true" as true and "0"/"off"/"false" as false.
     * Returns $default when the parameter is missing or invalid.
     */
    public function queryBool(string $key, ?bool $default = null): ?bool
    {
        $value = $this->filteredQuery($key, FilterInputRule::Bool->value);

        return is_int($value) ? $value === 1 : $default;
    }

    public function queryInt(string $key, ?int $default = null): ?int
    {
        $value = $this->filteredQuery($key, FilterInputRule::Int->value, $default);

        return is_int($value) ? $value : $default;
    }

    /**
     * When $allowed is provided, the value must be one of the allowed values.
     * Otherwise any non-empty string is accepted.
     * Returns $default when the parameter is missing, empty or invalid.
     *
     * @param list<string> $allowed
     */
    public function queryString(string $key, array $allowed = [], ?string $default = null): ?string
    {
        $rule = $allowed !== [] ? $allowed : FilterInputRule::Text->value;
        $value = $this->filteredQuery($key, $rule);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public function formString(string $key, string $default = ''): string
    {
        return To::str($this->form()[$key] ?? null, $default);
    }

    /**
     * @param string|list<string> $rule A FilterInputRule value or a list of allowed values
     */
    private function filteredQuery(string $key, string|array $rule, mixed $default = null): mixed
    {
        $filtered = WebApp::getFiltered($key, $rule, $this->query());

        // getFiltered returns false when the value is missing, empty or invalid
        return ($filtered !== false && $filtered !== null) ? $filtered : $default;
    }
}
