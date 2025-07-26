<?php

namespace app\utils;

use InvalidArgumentException;
use app\helpers\Application;
use app\utils\TranslationManager;

class Params
{
    private static array $commonParams = [];

    public static function getAll(array $specificParams): array
    {
        return array_merge(self::$commonParams, $specificParams);
    }

    public static function setDefaultParams(string $requestUri, int $segment = 0): void
    {
        if ($segment < 0) throw new InvalidArgumentException('Page segment index must be non-negative');
        $path = parse_url($requestUri, PHP_URL_PATH);
        if ($path === false || $path === null) throw new InvalidArgumentException('Invalid URI provided');
        $segments = explode('/', trim($path, '/'));
        $page = $segments[$segment] ?? '';
        $lang = TranslationManager::getCurrentLanguage();

        self::$commonParams = [
            'href' => '/user/sign/in',
            'userImg' => '/app/images/anonymat.png',
            'userEmail' => '',
            'keys' => false,
            'isEventManager' => false,
            'isPersonManager' => false,
            'isRedactor' => false,
            'isEditor' => false,
            'isWebmaster' => false,
            'page' => $page,
            'currentVersion' => Application::getVersion(),
            'currentLanguage' => $lang,
            'supportedLanguages' => TranslationManager::getSupportedLanguages(),
            'flag' => TranslationManager::getFlag($lang),
        ];
    }

    public static function setParams($params)
    {
        self::$commonParams = $params;
    }
}
