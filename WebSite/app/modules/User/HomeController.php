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
use app\modules\Common\valueObjects\Person;
use app\modules\Common\viewModels\HomeViewModel;
use app\modules\User\valueObjects\SidebarMenuChild;
use app\modules\User\valueObjects\SidebarMenuItem;

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
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
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
                $this->errors->badRequest("Unknown user with this email address {$userEmail}", __FILE__, __LINE__);
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
            $navbarColors = $this->dataHelper->getNavbarColors();
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
                    'navbarBgColor'      => $navbarColors->bgColor,
                    'navbarInkColor'     => $navbarColors->inkColor,
                    'navbarIconColor'    => $navbarColors->iconColor,
                ],
                $this->metadataDataHelper->isTestSite() && !empty($prodSiteUrl = $this->metadataDataHelper->getProdSiteUrl())
                    ? $prodSiteUrl
                    : null,
                $connectedUser->person?->Alert
            );
        }
        $latestArticlesCount = (int) $this->dataHelper->getSetting('Home_LatestArticlesCount', '10');
        $featuredArticleId   = (int) $this->dataHelper->getSetting('Home_FeaturedArticleId', '0');
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

        $footerArticleId = (int) $this->dataHelper->getSetting('Home_FooterArticleId', '0');

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
            homeParagraphsCount: (int) $this->dataHelper->getSetting('Home_FeaturedArticleParagraphs', '1'),
            footerArticle: $footerArticle,
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
            ]),
        );

        $this->views->render('Common/views/home.latte', $viewModel->toArray());
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

        $this->views->render('Common/views/signpost.latte', $this->getAllParams([
            'navItems'        => $this->getNavItems($user->person),
            'title'           => ($this->t)('home.signpost.title'),
            'page'            => $user->getPage(),
            'user'            => $user,
            'btn_HistoryBack' => true,
        ]));
    }


#region Privates functions
    /**
     * @return list<SidebarMenuItem>
     */
    private function getSidebarMenuItems(?Person $person, bool $all = false): array
    {
        $userGroups = [];
        if ($person !== null) {
            $userGroups = $this->authorizationDataHelper->getUserGroups($person->Email);
        }

        $navItems = $this->dataHelper->gets(
            'MenuItem',
            ['What' => 'sidebar'],
            'Id, ParentId, Type, Label, Icon, Url, IdGroup, ForMembers, ForContacts, ForAnonymous',
            'Position'
        );

        $filteredNavItems = [];
        foreach ($navItems as $navItem) {
            if (
                ($person === null && $navItem->ForAnonymous == 1) ||
                ($person !== null && $navItem->ForMembers == 1 &&
                    (
                        $navItem->IdGroup === null ||
                        (!empty($userGroups) && in_array($navItem->IdGroup, $userGroups, true))
                    )
                ) ||
                $all
            ) {
                $filteredNavItems[$navItem->Id] = $navItem;
            }
        }

        // Build structured menu from flat filtered list
        $sidebarMenu = [];
        foreach ($filteredNavItems as $navItem) {
            if ($navItem->ParentId !== null) {
                continue; // children are attached below
            }

            $sidebarMenu[] = match ($navItem->Type) {
                'heading' => new SidebarMenuItem(
                    type: 'heading',
                    label: (string) $navItem->Label,
                ),
                'divider' => new SidebarMenuItem(type: 'divider'),
                'link' => new SidebarMenuItem(
                    type: 'link',
                    label: (string) $navItem->Label,
                    icon: $navItem->Icon !== null ? (string) $navItem->Icon : null,
                    url: $navItem->Url !== null ? (string) $navItem->Url : null,
                ),
                'submenu' => new SidebarMenuItem(
                    type: 'submenu',
                    label: (string) $navItem->Label,
                    icon: $navItem->Icon !== null ? (string) $navItem->Icon : null,
                    children: array_values(
                        array_map(
                            static fn($child) => new SidebarMenuChild(
                                label: (string) $child->Label,
                                url: $child->Url !== null ? (string) $child->Url : null,
                            ),
                            array_filter(
                                $filteredNavItems,
                                static fn($child) => $child->ParentId === $navItem->Id
                            )
                        )
                    ),
                ),
                default => Application::unreachable("Fatal error in file with navItemType={$navItem->Type}"),
            };
        }

        return $sidebarMenu;
    }
}
