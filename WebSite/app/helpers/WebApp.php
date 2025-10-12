<?php

declare(strict_types=1);

namespace app\helpers;

use InvalidArgumentException;

use app\enums\FilterInputRule;

class WebApp
{
    public const MYCLUB_WEBAPP = "https://myclub.alwaysdata.net/";

    public function buildUrl($newParams): string
    {
        $params = array_merge($_GET, $newParams);
        return '?' . http_build_query($params);
    }

    /**
     * Sanitize and validate input based on a schema.
     *
     * Examples:
     * ---------
     * // From $_POST:
     * $schema = [
     *     'article_id' => FilterInputRule::Int->value,
     *     'question' => FilterInputRule::HtmlSafeText->value,
     *     'closingDate' => FilterInputRule::DateTime->value,
     *     'visibility' => $this->application->enumToValues(SurveyVisibility::class),
     * ];
     * $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
     *
     * @param array $schema Associative array [key => rule]
     * @param array $source Input source (e.g. $_GET, $_POST, etc.)
     * @return array Filtered values, with null for invalid or missing inputs
     */
    static public function filterInput(array $schema, array $source): array
    {
        $filtered = [];
        foreach ($schema as $key => $rule) {
            $raw = $source[$key] ?? null;
            if (!isset($raw) || (is_string($raw) && strlen($raw) > 1024 * 1024)) {
                $filtered[$key] = null;
                continue;
            }
            if ($rule === FilterInputRule::ArrayInt->value || $rule === FilterInputRule::ArrayString->value) {
                if (is_array($raw)) {
                    $filtered[$key] = array_values(array_filter(
                        $raw,
                        fn($v) =>
                        $rule === FilterInputRule::ArrayInt
                            ? preg_match(FilterInputRule::Integer->value, (string)$v)
                            : is_string($v) && trim($v) !== ''
                    ));
                    if (empty($filtered[$key])) $filtered[$key] = null;
                } else $filtered[$key] = null;
                continue;
            }
            if ($rule === FilterInputRule::CheckboxMatrix->value) {
                $validateCheckboxMatrix = function (array $arr) use (&$validateCheckboxMatrix) {
                    $result = [];
                    foreach ($arr as $key => $val) {
                        if (is_array($val)) {
                            $nested = $validateCheckboxMatrix($val);
                            if (!empty($nested)) $result[$key] = $nested;
                        } elseif ($val === 'on') $result[$key] = 'on';
                    }
                    return $result;
                };
                if (is_array($raw)) {
                    $filtered[$key] = $validateCheckboxMatrix($raw);
                    if (empty($filtered[$key])) $filtered[$key] = null;
                } else $filtered[$key] = null;
                continue;
            }
            if (is_array($raw)) {
                $filtered[$key] = null;
                continue;
            }
            if ($rule === FilterInputRule::Html->value) $value = trim($raw);
            else                                        $value = trim(strip_tags($raw));
            if (is_array($rule))                                     $filtered[$key] = in_array($value, $rule, true) ? $value : null;
            elseif (is_string($rule) && str_starts_with($rule, '/')) $filtered[$key] = preg_match($rule, $value) ? $value : null;
            elseif ($rule === FilterInputRule::Bool->value) {
                if (is_array($raw)) {
                    $filtered[$key] = !empty($raw) ? 1 : 0;
                    continue;
                }
                if ($value === 'on' || $value === '1' || $value === 'true')                        $filtered[$key] = 1;
                elseif ($value === 'off' || $value === '0' || $value === 'false' || $value === '') $filtered[$key] = 0;
                else                                                                               $filtered[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } elseif ($rule === FilterInputRule::Int) $filtered[$key] = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            elseif ($rule === FilterInputRule::Float) $filtered[$key] = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            else                                      $filtered[$key] = $value !== '' ? $value : null;
        }
        return $filtered;
    }

    static public function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host . '/';

        return $baseUrl;
    }

    static public function getFiltered(string $key, string|array $rule, array $source): mixed
    {
        $result = self::filterInput([$key => $rule], $source);
        return $result[$key] !== '' ? $result[$key] : false;
    }

    static public function isMyClubWebSite(): bool
    {
        return self::getBaseUrl() == self::MYCLUB_WEBAPP;
    }

    static public function nullableCast(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') return null;
        return match ($type) {
            'int'    => (int)$value,
            'float'  => (float)$value,
            'bool'   => (bool)$value,
            default  => throw new InvalidArgumentException("Unsupported cast type: $type"),
        };
    }

    static public function sanitizeHtml($html)
    {
        $allowed_tags = '<div><span><p><br><strong><em><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowed_tags);

        $html = preg_replace('/<(.*?)[\s|>]on[a-z]+=[\'"].*?[\'"]>(.*?)<\/\\1>/i', '<$1>$2</$1>', $html);
        $html = preg_replace('/javascript:.*?[\'"]/i', '', $html);

        return $html;
    }

    static public function sanitizeInput(string $data, array $possibleValues = [], string $defaultValue = ''): string
    {
        $data = trim($data ?? '');
        if (!empty($possibleValues)) {
            if (!in_array($data, $possibleValues, true)) return $defaultValue;
        }
        return $data;
    }
}
