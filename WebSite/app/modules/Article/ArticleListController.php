<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\modules\Common\TableController;
use app\modules\Common\viewModels\TableIndexViewModel;
use Envms\FluentPDO\Queries\Select;

final class ArticleListController extends TableController
{
    public function __construct(
        Application $application,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly ArticleTableDataHelper $articleTableDataHelper,
    ) {
        parent::__construct($application);
    }

    public function index(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        $isEditor = $connectedUser->isEditor();

        $filterValues = $this->readFilterValues([
            'PersonName' => FilterInputRule::PersonName->value,
            'title'      => FilterInputRule::Content->value,
            'lastUpdate' => FilterInputRule::DateTime->value,
            'published'  => ['oui', 'non'],
            'pool'       => ['oui', 'non'],
            'menu'       => ['oui', 'non'],
            'GroupName'  => FilterInputRule::HtmlSafeName->value,
            'Content'    => FilterInputRule::Content->value,
            'Id'         => FilterInputRule::Int->value,
        ]);

        $filters = [
            $this->filter('PersonName', 'article.label.created_by'),
            $this->filter('title', 'article.label.title'),
            $this->filter('lastUpdate', 'article.label.last_update'),
            $this->filter('pool', 'article.label.pool'),
            $this->filter('GroupName', 'article.label.group'),
            $this->filter('Content', 'article.label.content'),
            ['name' => 'Id', 'label' => 'ID'],
        ];
        $columns = [
            $this->column('PersonName', 'article.label.created_by'),
            $this->column('Title', 'article.label.title'),
            $this->column('LastUpdate', 'article.label.last_update'),
            $this->column('Pool', 'article.label.pool'),
            $this->column('PoolDetail', 'article.label.pool_detail'),
            $this->column('Messages', 'article.label.messages'),
            $this->column('GroupName', 'article.label.group'),
            $this->column('ForMembers', 'article.label.for_members'),
        ];
        if ($isEditor) {
            $filters[] = $this->filter('published', 'article.label.published');
            $filters[] = $this->filter('menu', 'article.label.menu');
            $columns[] = $this->column('Menu', 'article.label.menu');
            $columns[] = $this->column('Published', 'article.label.published');
        }

        $spotlight = $this->articleDataHelper->getSpotlightArticle();
        $spotlightArticleId = isset($spotlight['articleId']) && is_numeric($spotlight['articleId'])
            ? (int)$spotlight['articleId']
            : -1;

        $query = $this->articleTableDataHelper->getQuery($connectedUser, $spotlightArticleId);

        $this->renderIndex('Article/views/articles_index.latte', $query, $filterValues, $filters, $columns);
    }

    public function publicIndex(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEditor(), __FILE__, __LINE__)) {
            return;
        }

        $filterValues = $this->readFilterValues([
            'title'      => FilterInputRule::Content->value,
            'lastUpdate' => FilterInputRule::DateTime->value,
            'Id'         => FilterInputRule::Int->value,
        ]);

        $filters = [
            $this->filter('title', 'article.label.title'),
            $this->filter('lastUpdate', 'article.label.last_update'),
            ['name' => 'Id', 'label' => 'ID'],
        ];
        $columns = [
            $this->column('Title', 'article.label.title'),
            $this->column('LastUpdate', 'article.label.last_update'),
            $this->column('ReferenceSource', 'article.label.reference_source'),
        ];

        $query = $this->articleTableDataHelper->getQueryForPublicArticles();

        $this->renderIndex('Article/views/publicArticles_index.latte', $query, $filterValues, $filters, $columns);
    }

    /**
     * @param array<string, list<string>|string> $schema
     * @return array<string, string>
     */
    private function readFilterValues(array $schema): array
    {
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        /** @var array<string, string> $filterValues */
        return $filterValues;
    }

    /**
     * @return array{name: string, label: string}
     */
    private function filter(string $name, string $labelKey): array
    {
        return ['name' => $name, 'label' => ($this->t)($labelKey)];
    }

    /**
     * @return array{field: string, label: string}
     */
    private function column(string $field, string $labelKey): array
    {
        return ['field' => $field, 'label' => ($this->t)($labelKey)];
    }

    /**
     * @param array<string, string> $filterValues
     * @param list<array{name: string, label: string}> $filters
     * @param list<array{field: string, label: string}> $columns
     */
    private function renderIndex(string $template, Select $query, array $filterValues, array $filters, array $columns): void
    {
        $connectedUser = $this->application->getConnectedUser();
        $data = $this->prepareTableData($query, $filterValues);

        $viewModel = new TableIndexViewModel(
            articles: array_values($data['items']),
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            filters: $filters,
            columns: $columns,
            resetUrl: '/articles',
            userConnected: $connectedUser->person ?? false,
            navItems: $this->getNavItems($connectedUser->person),
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
                'btn_HistoryBack' => true,
                'btn_Parent' => '/',
            ]),
        );
        $this->views->render($template, $viewModel->toArray());
    }
}
