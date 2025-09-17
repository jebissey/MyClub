<?php
declare(strict_types=1);

namespace app\helpers;

use InvalidArgumentException;

use app\helpers\Application;
use app\helpers\TranslationManager;

class Params
{
    private static array $commonParams = [];

    public static function getAll(array $specificParams): array
    {
        if (self::$commonParams === []) self::setDefaultParams($_SERVER['REQUEST_URI']);
        return array_merge(self::$commonParams, $specificParams);
    }

    public static function setParams($params)
    {
        self::$commonParams = $params;
    }

    #region Private functions
    private static function setDefaultParams(string $requestUri): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        if ($path === false || $path === null) throw new InvalidArgumentException('Invalid URI provided');
        $segments = explode('/', trim($path, '/'));
        $page = $segments[0] ?? '';
        $lang = TranslationManager::getCurrentLanguage();

        self::$commonParams = [
            'href' => '/user/sign/in',
            'userImg' => '🫥',
            'userEmail' => '',
            'isAdmin' => false,
            'isEventDesigner' => false,
            'isEventManager' => false,
            'isHomeDesigner' => false,
            'isPersonManager' => false,
            'isRedactor' => false,
            'isEditor' => false,
            'isVisitorInsights' => false,
            'isWebmaster' => false,
            'page' => $page,
            'currentVersion' => Application::VERSION,
            'currentLanguage' => $lang,
            'supportedLanguages' => TranslationManager::getSupportedLanguages(),
            'flag' => TranslationManager::getFlag($lang),
        ];
    }
}
