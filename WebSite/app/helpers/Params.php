<?php

declare(strict_types=1);

namespace app\helpers;

use InvalidArgumentException;

use app\helpers\Application;
use app\helpers\TranslationManager;
use app\models\MetadataDataHelper;

class Params
{
    private static array $commonParams = [];
    private static MetadataDataHelper $metadataDataHelper;

    public static function getAll(array $specificParams): array
    {
        if (self::$commonParams === []) {
            self::$metadataDataHelper = new MetadataDataHelper(Application::init());
            self::setDefaultParams($_SERVER['REQUEST_URI']);
        }
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
            'isEditor' => false,
            'isEventDesigner' => false,
            'isEventManager' => false,
            'isHomeDesigner' => false,
            'isMember' => false,
            'isPersonManager' => false,
            'isRedactor' => false,
            'isVisitorInsights' => false,
            'isWebmaster' => false,
            'page' => $page,
            'currentVersion' => Application::VERSION,
            'currentLanguage' => $lang,
            'supportedLanguages' => TranslationManager::getSupportedLanguages(),
            'flag' => TranslationManager::getFlag($lang),
        ];

        if (self::$metadataDataHelper->isTestSite()) self::$commonParams['productionSiteUrl'] = self::$metadataDataHelper->getProdSiteUrl();
    }
}
