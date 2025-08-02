<?php

namespace app\controllers;

use RuntimeException;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ArticleCrosstabDataHelper;
use app\helpers\ArticleDataHelper;
use app\helpers\ArticleTableDataHelper;
use app\helpers\AuthorizationDataHelper;
use app\helpers\Backup;
use app\helpers\DataHelper;
use app\helpers\Params;
use app\helpers\Period;
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
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function index()
    {
        $this->connectedUser->get();
        $filterValues = [
            'PersonName' => $_GET['PersonName'] ?? '',
            'title' => $_GET['title'] ?? '',
            'timestamp' => $_GET['timestamp'] ?? '',
            'lastUpdate' => $_GET['lastUpdate'] ?? '',
            'published' => $_GET['published'] ?? '',
            'pool' => $_GET['pool'] ?? '',
            'GroupName' => $_GET['GroupName'] ?? '',
            'Content' => $_GET['Content'] ?? '',
        ];
        $filterConfig = [
            ['name' => 'PersonName', 'label' => 'Créé par'],
            ['name' => 'title', 'label' => 'Titre'],
            ['name' => 'lastUpdate', 'label' => 'Dernière modification'],
            ['name' => 'pool', 'label' => 'Sondage'],
            ['name' => 'GroupName', 'label' => 'Groupe'],
            ['name' => 'Content', 'label' => 'Contenu'],
        ];
        if ($this->connectedUser->isEditor() || false) {
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
        if ($this->connectedUser->isEditor()) {
            $columns[] = ['field' => 'Published', 'label' => 'Publié'];
        }
        $query = $this->articleTableDataHelper->getQuery($this->connectedUser);
        $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
        $this->render('app/views/user/articles.latte', Params::getAll([
            'articles' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'isRedactor' => $this->connectedUser->isRedactor() ?? false,
            'userConnected' => $this->connectedUser->person ?? false,
            'layout' => WebApp::getLayout(),
            'navItems' => $this->getNavItems($this->connectedUser->person ?? false),
        ]));
    }

    public function show($id): void
    {
        $this->connectedUser->get();
        $article = $this->authorizationDatahelper->getArticle($id, $this->connectedUser);
        if ($article) {
            $articleIds = $this->articleDataHelper->getArticleIdsBasedOnAccess($this->connectedUser->person->Email ?? '');
            $chosenArticle = $this->articleDataHelper->getLatestArticle([$id]);
            $canEdit = false;
            if (($this->connectedUser->person ?? false) && $chosenArticle) {
                $canEdit = ($this->connectedUser->person && $this->connectedUser->person->Id == $chosenArticle->CreatedBy);
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
                'hasSurvey' => $this->dataHelper->get('Survey', ['IdArticle' => $id]),
                'id' => $id,
                'userConnected' => $this->connectedUser->person,
                'navItems' => $this->getNavItems($this->connectedUser->person),
                'publishedBy' => $chosenArticle->PublishedBy && $chosenArticle->PublishedBy != $chosenArticle->CreatedBy ? (new PersonDataHelper($this->application))->getPublisher($chosenArticle->PublishedBy) : '',
                'canReadPool' => $this->authorizationDatahelper->canPersonReadSurveyResults($chosenArticle, $this->connectedUser->person),
                'carouselItems' => (new DataHelper($this->application))->gets('Carousel', ['IdArticle' => $id]),
                'message' => $messages,
            ]));
        } else if ($this->connectedUser->person == '') $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Il faut être connecté pour pouvoir consulter cet article', 5000);
        else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function update($id): void
    {
        if ($this->connectedUser->get()->isRedactor() || false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || ($this->connectedUser->person->Id ?? 0) != $article->CreatedBy) {
                    $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $title = $_POST['title'] ?? '';
                $content = $_POST['content'] ?? '';
                if (empty($title) || empty($content)) {
                    $_SESSION['error'] = "Le titre et le contenu sont obligatoires";
                    $this->flight->redirect('/articles/' . $id);
                    return;
                }
                $result = $this->dataHelper->set('Article', [
                    'Title'          => $title,
                    'Content'        => $content,
                    'PublishedBy'    => (($_POST['published'] ?? 0) == 1 ? $this->connectedUser->person->Id : null),
                    'IdGroup'        => $_POST['idGroup'] === '' ? null : ($_POST['idGroup'] ?? null),
                    'OnlyForMembers' => $_POST['membersOnly'] ?? 0,
                    'LastUpdate'     => date('Y-m-d H:i:s')
                ], ['Id' => $id]);
                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                $this->flight->redirect('/articles/' . $id);
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function publish($id): void
    {
        if ($this->connectedUser->get()->isEditor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || ($this->connectedUser->person->Id != $article->CreatedBy && !$this->connectedUser->isEditor())) {
                    $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $isSpotlightActive = $_POST['isSpotlightActive'] ?? 0;
                if ($isSpotlightActive) {
                    $spotlightedUntil = $_POST['spotlightedUntil'] ?? 0;
                    $this->articleDataHelper->setSpotlightArticle($id, $spotlightedUntil);
                }
                $result = $this->dataHelper->set('Article', [
                    'Title'          => '',
                    'Content'        => '',
                    'PublishedBy'    => $_POST['published'] ?? 0  == 1 ? $this->connectedUser->person->Id : null,
                    'IdGroup'        => $_POST['idGroup'] === '' ? null : ($_POST['idGroup'] ?? null),
                    'OnlyForMembers' => $_POST['membersOnly'] ?? 0,
                    'LastUpdate'     => date('Y-m-d H:i:s')
                ], ['Id' => $id]);
                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                $this->flight->redirect('/articles/' . $id);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/publish.latte', Params::getAll(['article' => $this->articleDataHelper->getWithAuthor($id)]));
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
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
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function delete($id)
    {
        if ($this->connectedUser->get()->isRedactor() || false) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || $this->connectedUser->person->Id != $article->CreatedBy) {
                    $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $this->dataHelper->delete('Article', ['Id' => $id]);
                $this->flight->redirect('/articles');
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showArticleCrosstab()
    {
        if ($this->connectedUser->get(1)->isRedactor() || false) {
            $period = $this->flight->request()->query->period ?? 'month';
            $dateRange = Period::getDateRangeFor($period);
            $crosstabData = (new ArticleCrosstabDataHelper($this->application))->getItems($dateRange);

            $this->render('app/views/common/crosstab.latte', Params::getAll([
                'crosstabData' => $crosstabData,
                'period' => $period,
                'dateRange' => $dateRange,
                'availablePeriods' => Period::gets(),
                'navbarTemplate' => '../navbar/redactor.latte',
                'title' => 'Rédateurs vs audience',
                'totalLabels' => ['articles', '']
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
