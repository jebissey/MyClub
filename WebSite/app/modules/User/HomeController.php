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
use app\modules\Common\viewModels\HomeViewModel;

final class HomeController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly SurveyDataHelper $surveyDataHelper,
        private readonly DesignDataHelper $designDataHelper,
        private readonly News $news,
        private readonly PersonDataHelper $personDataHelper,
        private readonly MetadataDataHelper $metadataDataHelper,
    ) {
        parent::__construct($application);
    }

    public function home(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $_SESSION['navbar'] = '';
        $userPendingSurveys = $userPendingDesigns = [];
        $userEmail = is_string($_SESSION['user'] ?? null) ? $_SESSION['user'] : '';
        $news = false;

        $lang = TranslationManager::getCurrentLanguage();
        $connectedUser = $this->application->getConnectedUser();

        if ($userEmail) {
            if ($connectedUser->person === null) {
                unset($_SESSION['user']);
                $this->raiseBadRequest("Unknown user with this email address {$userEmail}", __FILE__, __LINE__);
                return;
            }

            $pendingSurveyResponses = $this->surveyDataHelper->getPendingSurveyResponses();
            $userPendingSurveys = array_values(array_filter(
                $pendingSurveyResponses,
                static fn($item) => strcasecmp($item->Email, $userEmail) === 0
            ));

            $pendingDesignResponses = $this->designDataHelper->getPendingDesignResponses();
            $userPendingDesigns = array_values(array_filter(
                $pendingDesignResponses,
                static fn($item) => strcasecmp($item->Email, $userEmail) === 0
            ));

            $news = $this->news->anyNews($connectedUser);
        } else {
            $defaultColors = $this->dataHelper->getDefaultColors();
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
                    'currentPath'        => parse_url(
                        is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '',
                        PHP_URL_PATH
                    ),
                    'isMyclubWebSite'    => WebApp::isMyClubWebSite(),
                    'navbarBgColor'      => $defaultColors['navbarBgColor'],
                    'navbarInkColor'     => $defaultColors['navbarInkColor'],
                    'navbarIconColor'    => $defaultColors['navbarIconColor'],
                ],
                $this->metadataDataHelper->isTestSite() && !empty($prodSiteUrl = $this->metadataDataHelper->getProdSiteUrl())
                    ? $prodSiteUrl
                    : null,
                $connectedUser->person?->Alert
            );
        }
        $latestArticlesCount = (int) ($this->dataHelper->get('Settings', ['Name' => 'Home_LatestArticlesCount'], 'Value')->Value ?? 10);
        $featuredArticleId   = (int) ($this->dataHelper->get('Settings', ['Name' => 'Home_FeaturedArticleId'], 'Value')->Value ?? 0);
        $homeHeader          = $this->dataHelper->get('Languages', ['Name' => 'Home_Header'], $lang)->$lang ?? '';
        $homeFooter          = $this->dataHelper->get('Languages', ['Name' => 'Home_Footer'], $lang)->$lang ?? '';
        $articles            = $this->articleDataHelper->getLatestArticles($userEmail, $latestArticlesCount);

        $latestArticle = null;
        $displayArticleId = 0;

        if (
            $featuredArticleId > 0
            && $this->articleDataHelper->isUserAllowedToReadArticle($userEmail, $featuredArticleId)
        ) {
            $displayArticleId = $featuredArticleId;
        } else {
            $spotlight = $this->articleDataHelper->getSpotlightArticle();
            if ($spotlight !== null) {
                /** @var array{articleId: int, spotlightUntil: string} $spotlight */
                $articleId = (int) $spotlight['articleId'];
                if (
                    $this->articleDataHelper->isUserAllowedToReadArticle($userEmail, $articleId)
                    && strtotime($spotlight['spotlightUntil']) >= strtotime(date('Y-m-d'))
                ) {
                    $displayArticleId = $articleId;
                }
            }
        }

        if ($displayArticleId > 0) {
            $latestArticle = $this->articleDataHelper->getWithAuthor($displayArticleId) ?: null;
        }

        if ($latestArticle === null && isset($articles['latestArticle'])) {
            $latestArticle = $this->articleDataHelper->getWithAuthor(
                $articles['latestArticle']->Id
            ) ?: null;
        }

        $footerArticleId = (int) (
            $this->dataHelper->get(
                'Settings',
                ['Name' => 'Home_FooterArticleId'],
                'Value'
            )->Value ?? 0
        );

        $footerArticle = $footerArticleId > 0
            ? $this->dataHelper->get('Article', ['Id' => $footerArticleId], 'Title, Content') ?: null
            : null;

        $publishedBy = '';

        if (
            $latestArticle !== null
            && $latestArticle->PublishedBy !== $latestArticle->CreatedBy
        ) {
            $publishedBy = $this->personDataHelper->getPublisher(
                $latestArticle->PublishedBy
            ) ?? '';
        }

        $latestArticleHasSurvey = $latestArticle !== null
            && (bool) $this->surveyDataHelper->articleHasSurveyNotClosed($latestArticle->Id);

        $carouselItems = $latestArticle !== null
            ? array_values(
                $this->dataHelper->gets(
                    'Carousel',
                    ['IdArticle' => $latestArticle->Id],
                    'Item'
                )
            )
            : [];

        $viewModel = new HomeViewModel(
            latestArticle: $latestArticle,
            latestArticles: $articles['latestArticles'],
            latestArticlesCount: $latestArticlesCount,
            homeHeader: $homeHeader,
            homeFooter: $homeFooter,
            navItems: $this->getNavItems($connectedUser->person),
            sidebarMenu: $this->getSidebarMenuItems($connectedUser->person),
            publishedBy: $publishedBy,
            latestArticleHasSurvey: $latestArticleHasSurvey,
            pendingSurveys: $userPendingSurveys,
            pendingDesigns: $userPendingDesigns,
            news: $news,
            carouselItems: $carouselItems,
            homeParagraphsCount: (int) (
                $this->dataHelper->get(
                    'Settings',
                    ['Name' => 'Home_FeaturedArticleParagraphs'],
                    'Value'
                )->Value ?? 1
            ),
            footerArticle: $footerArticle,
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );

        $this->render('Common/views/home.latte', $viewModel->toArray());
    }

    public function help(): void
    {
        $this->renderHelp('Help_Home', fn($u) => $u->isAnybody(), __FILE__, __LINE__);
    }

    public function legalNotice(): void
    {
        $this->renderHelp('LegalNotices', fn($u) => $u->isAnybody(), __FILE__, __LINE__);
    }

    public function signpost(): void
    {
        $user = $this->application->getConnectedUser();

        $this->render('Common/views/signpost.latte', $this->getAllParams([
            'navItems' => $this->getNavItems($user->person),
            'title'   => 'Que souhaitez-vous faire ?',
            'page'    => $user->getPage(),
            'user'    => $user,
            'btn_HistoryBack' => true,
        ]));
    }
}
