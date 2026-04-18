<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\News;
use app\helpers\Params;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\DesignDataHelper;
use app\models\MetadataDataHelper;
use app\models\PersonDataHelper;
use app\models\SurveyDataHelper;
use app\modules\Common\AbstractController;

class HomeController extends AbstractController
{
    public function __construct(
        Application $application,
        private ArticleDataHelper $articleDataHelper,
        private SurveyDataHelper $surveyDataHelper,
        private DesignDataHelper $designDataHelper,
        private News $news,
        private PersonDataHelper $personDataHelper,
        private MetadataDataHelper $metadataDataHelper,
    ) {
        parent::__construct($application);
    }

    public function home(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = '';
        $userPendingSurveys = $userPendingDesigns = [];
        $userEmail = $_SESSION['user'] ?? '';

        $lang = TranslationManager::getCurrentLanguage();
        $connectedUser = $this->application->getConnectedUser();
        if ($userEmail) {
            if ($connectedUser->person === null) {
                unset($_SESSION['user']);
                $this->raiseBadRequest("Unknown user with this email address {$userEmail}", __FILE__, __LINE__);
            }
            $pendingSurveyResponses = $this->surveyDataHelper->getPendingSurveyResponses();
            $userPendingSurveys = array_filter($pendingSurveyResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });

            $pendingDesignResponses = $this->designDataHelper->getPendingDesignResponses();
            $userPendingDesigns = array_filter($pendingDesignResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });

            $news = $this->news->anyNews($connectedUser);
        } else {
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
                    'currentPath'        => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
                    'isMyclubWebSite'    => WebApp::isMyClubWebSite(),
                ],
                $this->metadataDataHelper->isTestSite() && !empty($prodSiteUrl = $this->metadataDataHelper->getProdSiteUrl()) ? $prodSiteUrl : null,
                $connectedUser?->person?->Alert ?? null
            );
        }
        $latestArticlesCount = (int) ($this->dataHelper->get('Settings', ['Name' => 'Home_LatestArticlesCount'], 'Value')->Value ?? 10);
        $featuredArticleId   = (int) ($this->dataHelper->get('Settings', ['Name' => 'Home_FeaturedArticleId'], 'Value')->Value ?? 0);
        $homeHeader          = $this->dataHelper->get('Languages', ['Name' => 'Home_Header'], $lang)->$lang ?? '';
        $homeFooter          = $this->dataHelper->get('Languages', ['Name' => 'Home_Footer'], $lang)->$lang ?? '';
        $articles            = $this->articleDataHelper->getLatestArticles($userEmail, $latestArticlesCount);
        $latestArticle       = $articles['latestArticle'];
        if ($featuredArticleId > 0) {
            if ($this->articleDataHelper->isUserAllowedToReadArticle($userEmail, $featuredArticleId)) {
                $featured = $this->articleDataHelper->getWithAuthor($featuredArticleId);
                if ($featured !== null) {
                    $latestArticle = $featured;
                }
            }
        } else {
            $spotlight = $this->articleDataHelper->getSpotlightArticle();
            if ($spotlight !== null) {
                $articleId = $spotlight['articleId'];
                if ($this->articleDataHelper->isUserAllowedToReadArticle($userEmail, $articleId)) {
                    if (strtotime($spotlight['spotlightUntil']) >= strtotime(date('Y-m-d'))) {
                        $latestArticle = $this->articleDataHelper->getWithAuthor((int) $articleId);
                    }
                }
            }
        }

        $this->render('Common/views/home.latte', $this->getAllParams([
            'latestArticle'          => $latestArticle,
            'latestArticles'         => $articles['latestArticles'],
            'latestArticlesCount'    => $latestArticlesCount,
            'homeHeader'             => $homeHeader,
            'homeFooter'             => $homeFooter,
            'navItems'               => $this->getNavItems($connectedUser->person ?? false),
            'sidebarMenu'            => $this->getSidebarMenuItems($connectedUser->person ?? false),
            'publishedBy'            => $articles['latestArticle']
                && $articles['latestArticle']->PublishedBy != $articles['latestArticle']->CreatedBy
                ? $this->personDataHelper->getPublisher($articles['latestArticle']->PublishedBy) : '',
            'latestArticleHasSurvey' => $this->surveyDataHelper->articleHasSurveyNotClosed($articles['latestArticle']->Id ?? 0),
            'pendingSurveys'         => $userPendingSurveys,
            'pendingDesigns'         => $userPendingDesigns,
            'news'                   => $news ?? false,
            'page'                   => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function helpHome(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $lang = TranslationManager::getCurrentLanguage();
        $this->render('Common/views/info.latte', [
            'content' => $this->dataHelper->get('Languages', ['Name' => 'Help_Home'], $lang)->$lang ?? '',
            'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization() ?? false,
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true,
            'page' => $this->application->getConnectedUser()->getPage(),
        ]);
    }

    public function legalNotice(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $lang = TranslationManager::getCurrentLanguage();

        $content = $this->latte->renderToString('Common/views/info.latte', [
            'content' => $this->dataHelper->get('Languages', ['Name' => 'LegalNotices'], $lang)->$lang ?? '',
            'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization() ?? false,
            'currentVersion' => Application::VERSION,
            'page' => $this->application->getConnectedUser()->getPage(),
            'timer' => 0,
        ]);
        echo $content;
    }

    public function signpost(): void
    {
        $user = $this->application->getConnectedUser();

        $this->render('Common/views/signpost.latte', $this->getAllParams([
            'navItems' => $this->getNavItems($user->person),
            'title'   => 'Que souhaitez-vous faire ?',
            'page'    => $user->getPage(),
            'user'    => $user,
        ]));
    }
}
