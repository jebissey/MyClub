<?php

declare(strict_types=1);

namespace app\helpers;

use InvalidArgumentException;
use Latte\Engine as LatteEngine;
use Latte\Loaders\StringLoader;
use app\enums\FilterInputRule;
use app\valueObjects\Person;

class WebApp
{
    public const MYCLUB_WEBAPP = "https://myclub.ovh/";

    /**
     * @param array<string, scalar|null> $newParams
     */
    public function buildUrl(array $newParams): string
    {
        /** @var array<string, scalar|null> $params */
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
     * @param array<string, string|list<string>> $schema
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    public static function filterInput(array $schema, array $source): array
    {
        $filtered = [];
        foreach ($schema as $key => $rule) {
            $filtered[$key] = self::filterSingleInput($rule, $source[$key] ?? null);
        }
        return $filtered;
    }

    public static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = To::str($_SERVER['HTTP_HOST'] ?? null);
        return $protocol . $host . '/';
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function getCompiledContent(string $content, array $params): string
    {
        $cacheDir = dirname(__DIR__, 2) . '/var/latte/admin_runtime';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $latte = new LatteEngine();
        $latte->setLoader(new StringLoader(['main' => $content]));
        $latte->setCacheDirectory($cacheDir);
        $latte->setAutoRefresh(true);

        return $latte->renderToString('main', $params);
    }

    /**
     * @param string|list<string> $rule
     * @param array<string, mixed> $source
     */
    public static function getFiltered(string $key, string|array $rule, array $source): mixed
    {
        $result = self::filterInput([$key => $rule], $source);
        return $result[$key] !== '' ? $result[$key] : false;
    }

    public static function getUserImg(Person $person, GravatarHandler $gravatarHandler): string
    {
        return self::computeUserImg($person->UseGravatar, $person->Email, $person->Avatar, $gravatarHandler);
    }

    public static function computeUserImg(bool $useGravatar, ?string $email, ?string $avatar, GravatarHandler $gravatarHandler): string
    {
        if ($useGravatar) {
            return $gravatarHandler->getGravatar($email ?? '', true);
        }
        return empty($avatar) ? '🤔' : $avatar;
    }

    public static function isMyClubWebSite(): bool
    {
        return self::getBaseUrl() === self::MYCLUB_WEBAPP;
    }

    public static function nullableCast(mixed $value, string $type): int|float|bool|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Value must be scalar to cast.');
        }

        return match ($type) {
            'int'    => (int)$value,
            'float'  => (float)$value,
            'bool'   => (bool)$value,
            default  => throw new InvalidArgumentException("Unsupported cast type: $type"),
        };
    }

    public static function sanitizeHtml(string $html): string
    {
        $allowed_tags = '<div><span><p><br><strong><em><ul><ol><li><a><img>'
            . '<hr><h1><h2><h3><h4><h5><h6><blockquote><pre><code>'
            . '<table><thead><tbody><tr><th><td>'
            . '<section><i>';

        $html = strip_tags($html, $allowed_tags);

        $html = preg_replace('/<(.*?)[\s|>]on[a-z]+=[\'"].*?[\'"]>(.*?)<\/\\1>/i', '<$1>$2</$1>', $html);
        $html = preg_replace('/javascript:.*?[\'"]/i', '', $html ?? '');

        return $html ?? '';
    }

    /**
     * @param list<string> $possibleValues
     */
    public static function sanitizeInput(string $data, array $possibleValues = [], string $defaultValue = ''): string
    {
        $data = trim($data);
        if (!empty($possibleValues)) {
            if (!in_array($data, $possibleValues, true)) {
                return $defaultValue;
            }
        }
        return $data;
    }

    public static function getRequestMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        return is_string($method) ? $method : 'UNKNOWN';
    }

    #region Private functions
    /**
     * @param string|list<string> $rule
     */
    private static function filterSingleInput(string|array $rule, mixed $raw): mixed
    {
        $maxLen = ($rule === FilterInputRule::DataUrl->value) ? 5 * 1024 * 1024 : 1024 * 1024;
        if (!isset($raw) || (is_string($raw) && strlen($raw) > $maxLen)) {
            return null;
        }

        if ($rule === FilterInputRule::ArrayInt->value || $rule === FilterInputRule::ArrayString->value) {
            return self::filterArrayInput($rule, $raw);
        }
        if ($rule === FilterInputRule::CheckboxMatrix->value) {
            if (!is_array($raw)) {
                return null;
            }
            $result = self::filterCheckboxMatrix($raw);
            return empty($result) ? null : $result;
        }
        if (is_array($raw)) {
            return null;
        }

        if ($rule === FilterInputRule::DataUrl->value) {
            return self::filterDataUrl($raw);
        }

        $rawStr = To::str($raw);
        $value = $rule === FilterInputRule::Html->value ? trim($rawStr) : trim(strip_tags($rawStr));

        if (is_array($rule)) {
            return in_array($value, $rule, true) ? $value : null;
        }

        if (str_starts_with($rule, '/')) {
            return preg_match($rule, $value) ? $value : null;
        }

        return match ($rule) {
            FilterInputRule::Bool->value => self::filterBool($value),
            FilterInputRule::Int->value => self::filterInt($value),
            FilterInputRule::Float->value => self::filterFloat($value),
            default => $value !== '' ? $value : null,
        };
    }

    /**
     * @return array<int, int|string>|null
     */
    private static function filterArrayInput(string $rule, mixed $raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }

        $isArrayInt = $rule === FilterInputRule::ArrayInt->value;

        $filtered = array_filter(
            $raw,
            static function ($v) use ($isArrayInt): bool {
                $str = To::str($v);
                return $isArrayInt
                    ? preg_match(FilterInputRule::Integer->value, $str) === 1
                    : trim($str) !== '';
            }
        );

        $result = array_values(array_map(
            static fn($v): int|string => $isArrayInt ? To::int($v) : trim(To::str($v)),
            $filtered
        ));

        return empty($result) ? null : $result;
    }

    /**
     * @param array<array-key, mixed> $arr
     * @return array<array-key, mixed>
     */
    private static function filterCheckboxMatrix(array $arr): array
    {
        $result = [];
        foreach ($arr as $itemKey => $val) {
            if (is_array($val)) {
                $nested = self::filterCheckboxMatrix($val);
                if (!empty($nested)) {
                    $result[$itemKey] = $nested;
                }
            } elseif ($val === 'on') {
                $result[$itemKey] = 'on';
            }
        }
        return $result;
    }

    private static function filterDataUrl(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }
        return preg_match('/^data:image\/(png|jpeg|gif|webp);base64,[A-Za-z0-9+\/]+=*$/', $raw) ? $raw : null;
    }

    private static function filterBool(string $value): int|null
    {
        return match (true) {
            $value === 'on', $value === '1', $value === 'true' => 1,
            $value === 'off', $value === '0', $value === 'false', $value === '' => 0,
            default => self::boolToIntOrNull(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)),
        };
    }

    private static function boolToIntOrNull(?bool $value): ?int
    {
        return $value === null ? null : (int)$value;
    }

    private static function filterInt(string $value): ?int
    {
        $val = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        return $val !== null ? (int)$val : null;
    }

    private static function filterFloat(string $value): ?float
    {
        $val = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        return $val !== null ? (float)$val : null;
    }
}
