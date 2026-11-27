<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\To;
use app\helpers\TranslationManager;
use app\models\ArticleDataHelper;
use app\models\CarouselDataHelper;
use app\models\PersonDataHelper;
use app\models\SurveyDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\valueObjects\ArticleRow;
use app\modules\Common\viewModels\HomeViewModel;
use app\modules\Common\viewModels\SignpostViewModel;
use app\modules\User\services\FeaturedArticleSelector;
use app\modules\User\services\GuestLayoutParams;
use app\modules\User\services\PendingItemsProvider;
use app\modules\User\services\SidebarMenuBuilder;
use app\modules\User\valueObjects\PendingItems;

final class HomeController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly SurveyDataHelper $surveyDataHelper,
        private readonly PersonDataHelper $personDataHelper,
        private readonly CarouselDataHelper $carouselDataHelper,
        private readonly FeaturedArticleSelector $featuredArticleSelector,
        private readonly SidebarMenuBuilder $sidebarMenuBuilder,
        private readonly PendingItemsProvider $pendingItemsProvider,
        private readonly GuestLayoutParams $guestLayoutParams
    ) {
        parent::__construct($application);
    }

    public function home(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $this->setNavbar('');
        $userEmail = is_string($_SESSION['user'] ?? null) ? $_SESSION['user'] : '';
        $lang = TranslationManager::getCurrentLanguage();
        $connectedUser = $this->application->getConnectedUser();

        if ($userEmail !== '') {
            if ($connectedUser->person === null) {
                unset($_SESSION['user']);
                $this->errors->badRequest("Unknown user with this email address {$userEmail}", __FILE__, __LINE__);
                return;
            }
            $pending = $this->pendingItemsProvider->forUser($userEmail, $connectedUser);
        } else {
            $pending = PendingItems::none();
            $this->guestLayoutParams->apply($lang, $connectedUser);
        }

        $latestArticlesCount = To::int($this->dataHelper->getSetting('Home_LatestArticlesCount', '10'));
        $featuredArticleId   = To::int($this->dataHelper->getSetting('Home_FeaturedArticleId', '0'));
        $footerArticleId     = To::int($this->dataHelper->getSetting('Home_FooterArticleId', '0'));
        $articles = $this->articleDataHelper->getLatestArticles($userEmail, $latestArticlesCount);
        $latestArticle = $this->resolveLatestArticle(
            $userEmail,
            $featuredArticleId,
            isset($articles['latestArticle']) ? $articles['latestArticle']->id : null
        );

        $publishedBy = '';
        if ($latestArticle !== null && $latestArticle->publishedBy !== $latestArticle->createdBy) {
            $publishedBy = $this->personDataHelper->getPublisher($latestArticle->publishedBy) ?? '';
        }

        $viewModel = new HomeViewModel(
            latestArticle: $latestArticle,
            latestArticles: $articles['latestArticles'],
            latestArticlesCount: $latestArticlesCount,
            homeHeader: $this->dataHelper->getLanguageText('Home_Header', $lang),
            homeFooter: $this->dataHelper->getLanguageText('Home_Footer', $lang),
            navItems: $this->getNavItems($connectedUser->person?->email),
            sidebarMenu: $this->sidebarMenuBuilder->build($connectedUser->person),
            publishedBy: $publishedBy,
            latestArticleHasSurvey: $latestArticle !== null
                && (bool) $this->surveyDataHelper->articleHasSurveyNotClosed($latestArticle->id),
            pendingSurveys: $pending->surveys,
            pendingDesigns: $pending->designs,
            news: $pending->news,
            carouselItems: $latestArticle !== null
                ? $this->carouselDataHelper->getCarouselItems($latestArticle->id)
                : [],
            homeParagraphsCount: To::int($this->dataHelper->getSetting('Home_FeaturedArticleParagraphs', '1')),
            footerArticle: $this->articleDataHelper->getFooterArticle($footerArticleId),
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
            ]),
        );

        $this->views->render('Common/views/home.latte', $viewModel);
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

        $viewModel = new SignpostViewModel(
            navItems: $this->getNavItems($user->person?->email),
            title: $this->translate('home.signpost.title'),
            user: $user,
            layoutParams: $this->getAllParams(['page' => $user->getPage()]),
        );

        $this->views->render('Common/views/signpost.latte', $viewModel);
    }

    #region Privates functions
    /**
     * Picks the featured or spotlight article, falling back to the latest published one.
     */
    private function resolveLatestArticle(string $userEmail, int $featuredArticleId, ?int $latestArticleId): ?ArticleRow
    {
        $displayArticleId = $this->featuredArticleSelector->selectId($userEmail, $featuredArticleId);

        if ($displayArticleId > 0) {
            $article = $this->articleDataHelper->getWithAuthor($displayArticleId) ?: null;
            if ($article !== null) {
                return $article;
            }
        }

        return $latestArticleId !== null
            ? ($this->articleDataHelper->getWithAuthor($latestArticleId) ?: null)
            : null;
    }
    #endregion
}
