<?php

namespace app\controllers;

use RuntimeException;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\ArticleCrosstabDataHelper;
use app\helpers\ArticleDataHelper;
use app\helpers\ArticleTableDataHelper;
use app\helpers\AuthorizationDataHelper;
use app\helpers\Backup;
use app\helpers\DataHelper;
use app\helpers\Params;
use app\helpers\PeriodHelper;
use app\helpers\PersonDataHelper;
use app\helpers\WebApp;

class ArticleController extends TableController
{
    private ArticleDataHelper $articleDataHelper;
    private ArticleTableDataHelper $articleTableDataHelper;
    private AuthorizationDataHelper $authorizationDatahelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->articleDataHelper = new ArticleDataHelper($application);
        $this->articleTableDataHelper = new ArticleTableDataHelper($application);
        $this->authorizationDatahelper = new AuthorizationDataHelper($application);
    }

    public function home(): void
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'redactor';

                $this->render('app/views/admin/redactor.latte', Params::getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function index()
    {
        $connectedUser = $this->connectedUser->get();
        $schema = [
            'PersonName' => FilterInputRule::PersonName->value,
            'title' => FilterInputRule::Content->value,
            'timestamp' => FilterInputRule::DateTime->value,
            'lastUpdate' => FilterInputRule::DateTime->value,
            'published' => ['oui', 'non'],
            'pool' => ['oui', 'non'],
            'GroupName' => FilterInputRule::HtmlSafeName->value,
            'Content' => FilterInputRule::Content->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $filterConfig = [
            ['name' => 'PersonName', 'label' => 'Créé par'],
            ['name' => 'title', 'label' => 'Titre'],
            ['name' => 'lastUpdate', 'label' => 'Dernière modification'],
            ['name' => 'pool', 'label' => 'Sondage'],
            ['name' => 'GroupName', 'label' => 'Groupe'],
            ['name' => 'Content', 'label' => 'Contenu'],
        ];
        if ($connectedUser->isEditor() || false) {
            $filterConfig[] = ['name' => 'published', 'label' => 'Publié'];
        }
        $columns = [
            ['field' => 'PersonName', 'label' => 'Créé par'],
            ['field' => 'Title', 'label' => 'Titre'],
            ['field' => 'LastUpdate', 'label' => 'Dernière modification'],
            ['field' => 'GroupName', 'label' => 'Groupe'],
            ['field' => 'ForMembers', 'label' => 'Club'],
            ['field' => 'Pool', 'label' => 'Sondage'],
            ['field' => 'PoolDetail', 'label' => 'Cloture (votes) visibilité'],
        ];
        if ($connectedUser->isEditor()) {
            $columns[] = ['field' => 'Published', 'label' => 'Publié'];
        }
        $query = $this->articleTableDataHelper->getQuery($connectedUser);
        $data = $this->prepareTableData($query, $filterValues, (int)($this->flight->request()->query['tablePage'] ?? 0));
        $this->render('app/views/user/articles.latte', Params::getAll([
            'articles' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'isRedactor' => $connectedUser->isRedactor() ?? false,
            'userConnected' => $connectedUser->person ?? false,
            'layout' => WebApp::getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
        ]));
    }

    public function show(int $id): void
    {
        $connectedUser = $this->connectedUser->get();
        $article = $this->authorizationDatahelper->getArticle($id, $connectedUser);
        if ($article) {
            $articleIds = $this->articleDataHelper->getArticleIdsBasedOnAccess($connectedUser->person?->Email ?? '');
            $chosenArticle = $this->articleDataHelper->getLatestArticle([$id]);
            $canEdit = false;
            if (($connectedUser->person ?? false) && $chosenArticle) {
                $canEdit = $connectedUser->person->Id == $chosenArticle->CreatedBy;
            }
            $messages = [];
            if (isset($_SESSION['error'])) {
                $messages['error'] = $_SESSION['error'];
                $_SESSION['error'] = null;
            }
            if (isset($_SESSION['success'])) {
                $messages['success'] = $_SESSION['success'];
                $_SESSION['success'] = null;
            }

            $this->render('app/views/user/article.latte', Params::getAll([
                'chosenArticle' => $chosenArticle,
                'latestArticles' => $this->articleDataHelper->getLatestArticles_($articleIds),
                'canEdit' => $canEdit,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'hasSurvey' => $this->dataHelper->get('Survey', ['IdArticle' => $id], 'ClosingDate'),
                'id' => $id,
                'userConnected' => $connectedUser->person ?? false,
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'publishedBy' => $chosenArticle->PublishedBy && $chosenArticle->PublishedBy != $chosenArticle->CreatedBy ? (new PersonDataHelper($this->application))->getPublisher($chosenArticle->PublishedBy) : '',
                'canReadPool' => $this->authorizationDatahelper->canPersonReadSurveyResults($chosenArticle, $connectedUser),
                'carouselItems' => (new DataHelper($this->application))->gets('Carousel', ['IdArticle' => $id]),
                'message' => $messages,
            ]));
        } else if (!($connectedUser->person ?? false)) $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Il faut être connecté pour pouvoir consulter cet article', 5000);
        else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function update(int $id): void
    {
        if ($this->connectedUser->get()->isRedactor() || false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || ($this->connectedUser->person?->Id ?? 0) != $article->CreatedBy) {
                    $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $schema = [
                    'title' => FilterInputRule::HtmlSafeName->value,
                    'content' => FilterInputRule::Content->value,
                    'published' => FilterInputRule::Int->value,
                    'idGroup' => FilterInputRule::Int->value,
                    'membersOnly' => FilterInputRule::Int->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $title = $input['title'] ?? '???';
                $content = $input['content'] ?? '???';
                if (empty($title) || empty($content)) {
                    $_SESSION['error'] = "Le titre et le contenu sont obligatoires";
                    $this->flight->redirect('/articles/' . $id);
                    return;
                }
                $result = $this->dataHelper->set('Article', [
                    'Title'          => $title,
                    'Content'        => $content,
                    'PublishedBy'    => $input['published'] == 1 ? $this->connectedUser->person->Id : null,
                    'IdGroup'        => $input['idGroup'],
                    'OnlyForMembers' => $input['membersOnly'] ?? 1,
                    'LastUpdate'     => date('Y-m-d H:i:s')
                ], ['Id' => $id]);
                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                $this->flight->redirect('/articles/' . $id);
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function publish(int $id): void
    {
        if ($this->connectedUser->get()->isEditor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || ($this->connectedUser->person->Id != $article->CreatedBy && !$this->connectedUser->isEditor())) {
                    $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $schema = [
                    'isSpotlightActive' => FilterInputRule::Bool->value,
                    'spotlightedUntil' => FilterInputRule::DateTime->value,
                    'published' => FilterInputRule::Int->value,
                    'idGroup' => FilterInputRule::Content->value,
                    'membersOnly' => FilterInputRule::Int->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $isSpotlightActive = $input['isSpotlightActive'] ?? false;
                if ($isSpotlightActive) {
                    $spotlightedUntil = $input['spotlightedUntil'] ?? date('Y-m-d H:i:s', strtotime('+1 week'));
                    $this->articleDataHelper->setSpotlightArticle($id, $spotlightedUntil);
                }
                $result = $this->dataHelper->set('Article', [
                    'Title'          => '',
                    'Content'        => '',
                    'PublishedBy'    => $input['published'] ?? 0  == 1 ? $this->connectedUser->person->Id : null,
                    'IdGroup'        => $input['idGroup'] === '' ? null : ($input['idGroup'] ?? null),
                    'OnlyForMembers' => $input['membersOnly'],
                    'LastUpdate'     => date('Y-m-d H:i:s')
                ], ['Id' => $id]);
                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                $this->flight->redirect('/articles/' . $id);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/publish.latte', Params::getAll(['article' => $this->articleDataHelper->getWithAuthor($id)]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $id = $this->dataHelper->set('Article', [
                    'Title'     => '',
                    'Content'   => '',
                    'CreatedBy' => $this->connectedUser->person->Id ?? throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__)
                ]);
                $this->flight->redirect('/articles/' . $id);
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function delete(int $id)
    {
        if ($this->connectedUser->get()->isRedactor() || false) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || $this->connectedUser->person->Id != $article->CreatedBy) {
                    $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $this->dataHelper->delete('Article', ['Id' => $id]);
                $this->flight->redirect('/articles');
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showArticleCrosstab()
    {
        if ($this->connectedUser->get(1)->isRedactor() || false) {
            $period = $this->flight->request()->query->period ?? 'month';
            $dateRange = PeriodHelper::getDateRangeFor($period);
            $crosstabData = (new ArticleCrosstabDataHelper($this->application))->getItems($dateRange);

            $this->render('app/views/common/crosstab.latte', Params::getAll([
                'crosstabData' => $crosstabData,
                'period' => $period,
                'dateRange' => $dateRange,
                'availablePeriods' => PeriodHelper::gets(),
                'navbarTemplate' => '../navbar/redactor.latte',
                'title' => 'Rédateurs vs audience',
                'totalLabels' => ['articles', '']
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
