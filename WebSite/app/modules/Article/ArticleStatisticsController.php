<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\Period;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\TranslationManager;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\LogDataHelper;
use app\modules\Common\TableController;
use app\modules\Common\valueObjects\TopPageRow;
use app\modules\Common\viewModels\CrosstabViewModel;
use app\modules\Common\viewModels\TopItemsByPeriodViewModel;

final class ArticleStatisticsController extends TableController
{
    private const TOP = 50;

    public function __construct(
        Application $application,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly ArticleCrosstabDataHelper $articleCrosstabDataHelper,
        private readonly LogDataHelper $logDataHelper,
    ) {
        parent::__construct($application);
    }

    public function showArticleCrosstab(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $period = Period::tryFrom(To::str($this->flight->request()->query->period ?? 'month')) ?? Period::Month;
        $dateRange = $period->dateRange();
        $crosstabData = $this->articleCrosstabDataHelper->getItems($dateRange);
        $viewModel = new CrosstabViewModel(
            crosstabData: $crosstabData,
            period: $period->value,
            dateRange: $dateRange,
            availablePeriods: Period::gets($this->languagesDataHelper),
            title: ($this->t)('article.crosstab.title'),
            totalLabels: [($this->t)('article.crosstab.total_articles'), ''],
            historyBack: true,
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(1),
            ]),
        );
        $this->views->render('Article/views/crosstab.latte', $viewModel);
    }

    public function topArticlesByPeriod(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $period        = Period::fromRequest($this->application, $this->flight->request());
        $dateCondition = $period->dateConditions('CreatedAt');
        $topPages      = $this->logDataHelper->getTopArticles($dateCondition, self::TOP);

        $articleIds = [];
        foreach ($topPages as $page) {
            if ($page->articleId !== null && $page->articleId > 0) {
                $articleIds[] = $page->articleId;
            }
        }
        $authors = $this->articleDataHelper->getAuthorsByArticleIds($articleIds);

        $rows = [];
        foreach ($topPages as $page) {
            $author = $page->articleId !== null ? ($authors[$page->articleId] ?? null) : null;
            $rows[] = TopPageRow::fromStdClass($page, $author?->articleTitle, $author?->personName);
        }

        $viewModel = new TopItemsByPeriodViewModel(
            title: ($this->t)('visitor_insights.top_articles.title'),
            period: $period->value,
            periodFrom: $period->getStart()->format('Y-m-d H:i:s'),
            periodTo: $period->getEnd()->format('Y-m-d H:i:s'),
            topPages: $rows,
            translations: TranslationManager::getCreationTimeModalTranslations($this->languagesDataHelper),
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );
        $this->views->render('Article/views/topArticles.latte', $viewModel);
    }
}
