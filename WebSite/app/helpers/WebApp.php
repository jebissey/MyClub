<?php

namespace app\helpers;

use RuntimeException;

class WebApp
{
    public function buildUrl($newParams)
    {
        $params = array_merge($_GET, $newParams);
        return '?' . http_build_query($params);
    }

    static public function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host . '/';

        return $baseUrl;
    }

    static public function getLayout()
    {
        $navbar = $_SESSION['navbar'] ?? '';
        if ($navbar == 'user') return '../user/user.latte';
        else if ($navbar == 'eventManager') return '../admin/eventManager.latte';
        else if ($navbar == 'personManager') return '../admin/personManager.latte';
        else if ($navbar == 'webmaster') return '../admin/webmaster.latte';
        else if ($navbar == 'redactor') return '../admin/redactor.latte';
        else if ($navbar == '') return '../home.latte';

        throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__ . " with navbar=" . $navbar);
    }

    static public function sanitizeHtml($html)
    {
        $allowed_tags = '<div><span><p><br><strong><em><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowed_tags);

        $html = preg_replace('/<(.*?)[\s|>]on[a-z]+=[\'"].*?[\'"]>(.*?)<\/\\1>/i', '<$1>$2</$1>', $html);
        $html = preg_replace('/javascript:.*?[\'"]/i', '', $html);

        return $html;
    }

    static public function sanitizeInput(string $data): string
    {
        return trim($data ?? '');
    }

    /**
     * Sanitize and validate input based on a schema.
     *
     * The schema defines expected keys and how to filter them.
     * Each rule can be:
     * - a regex string (e.g. '/^[a-z]+$/')
     * - an array of allowed values (e.g. ['yes', 'no'])
     * - a type keyword: 'bool', 'int', 'float'
     *
     * Examples
     * ========
     * // From $_GET:
     * $schema = [
     *     'name' => '/^[a-zA-Z\s\-]+$/',
     *     'age' => 'int',
     *     'newsletter' => ['yes', 'no'],
     *     'score' => 'float',
     *     'valid' => 'bool',
     * ];
     * $input = WebApp::filterInput($schema, $_GET);
     *
     * // From custom array:
     * $data = [
     *     'token' => '<script>bad()</script>ABC123',
     *     'confirm' => 'yes'
     * ];
     * $input = WebApp::filterInput([
     *     'token' => '/^[A-Z0-9]+$/',
     *     'confirm' => ['yes', 'no']
     * ], $data);
     *
     * @param array $schema Associative array [key => rule] where rule is:
     *                      - regex string (starts with '/')
     *                      - array of allowed values
     *                      - 'bool', 'int', or 'float'
     * @param array $source Input source (e.g. $_GET, $_POST, decoded JSON, etc.)
     * @return array Filtered values (invalid values return empty string or null for bool)
     */
    static public function filterInput(array $schema, array $source): array
    {
        $filtered = [];
        foreach ($schema as $key => $rule) {
            $raw = $source[$key] ?? '';
            if (is_array($raw)) {
                $filtered[$key] = '';
                continue;
            }
            $value = trim(strip_tags($raw));
            if (is_array($rule))                                     $filtered[$key] = in_array($value, $rule, true) ? $value : ''; // White list
            elseif (is_string($rule) && str_starts_with($rule, '/')) $filtered[$key] = preg_match($rule, $value) ? $value : '';     // Regex
            elseif ($rule === 'bool')                                $filtered[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            elseif ($rule === 'int')                                 $filtered[$key] = filter_var($value, FILTER_VALIDATE_INT) ?? '';
            elseif ($rule === 'float')                               $filtered[$key] = filter_var($value, FILTER_VALIDATE_FLOAT) ?? '';
            else                                                     $filtered[$key] = $value;
        }
        return $filtered;
    }

    static public function getFiltered(string $key, string|array $rule, array $source): mixed
    {
        $source ??= $_GET;
        $result = self::filterInput([$key => $rule], $source);
        return $result[$key] !== '' ? $result[$key] : false;
    }
}
