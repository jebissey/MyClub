<?php

declare(strict_types=1);

namespace app\modules\User\services;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\Params;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\MetadataDataHelper;

final class GuestLayoutParams
{
    public function __construct(
        private readonly DataHelper $dataHelper,
        private readonly MetadataDataHelper $metadataDataHelper
    ) {
    }

    /**
     * Sets the layout parameters shown to a visitor who is not signed in.
     */
    public function apply(string $lang, ConnectedUser $connectedUser): void
    {
        $navbarColors = $this->dataHelper->getNavbarColors();
        $requestUri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '';

        Params::setParams(
            [
                'href'               => '/user/sign/in',
                'userImg'            => '👻',
                'userEmail'          => '',
                'isAdmin'            => false,
                'currentVersion'     => Application::VERSION,
                'currentLanguage'    => $lang,
                'supportedLanguages' => TranslationManager::getSupportedLanguages(),
                'flag'               => TranslationManager::getFlag($lang),
                'isRedactor'         => false,
                'page'               => $connectedUser->getPage(),
                'currentPath'        => parse_url($requestUri, PHP_URL_PATH),
                'isMyclubWebSite'    => WebApp::isMyClubWebSite(),
                'navbarBgColor'      => $navbarColors->bgColor,
                'navbarInkColor'     => $navbarColors->inkColor,
                'navbarIconColor'    => $navbarColors->iconColor,
            ],
            $this->getProdSiteUrl(),
            $connectedUser->person?->alert
        );
    }

    /**
     * Returns the production site URL when running on a test site, null otherwise.
     */
    private function getProdSiteUrl(): ?string
    {
        if (!$this->metadataDataHelper->isTestSite()) {
            return null;
        }

        $prodSiteUrl = $this->metadataDataHelper->getProdSiteUrl();

        return !empty($prodSiteUrl) ? $prodSiteUrl : null;
    }
}
