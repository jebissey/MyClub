<?php

namespace app\controllers;

use app\helpers\ArticleCrosstab;
use app\helpers\ArticleDataHelper;
use app\helpers\ArticleTableData;
use app\helpers\CarouselHelper;
use app\helpers\SurveyDataHelper;
use app\utils\Backup;
use app\utils\Period;
use app\utils\Webapp;

class ArticleController extends TableController
{
    private ArticleCrosstab $articleCrosstab;
    private ArticleDataHelper $articleDataHelper;
    private ArticleTableData $articleTableData;
    private CarouselHelper $carouselHelper;

    public function __construct()
    {
        $this->articleCrosstab = new ArticleCrosstab();
        $this->articleDataHelper = new ArticleDataHelper();
        $this->articleTableData = new ArticleTableData();
        $this->carouselHelper = new CarouselHelper();
    }

    public function home(): void
    {
        if ($this->personDataHelper->getPerson(['Redactor'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'redactor';

                $this->render('app/views/admin/redactor.latte', $this->params->getAll([]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function index()
    {
        $person = $this->personDataHelper->getPerson([]);
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
        if ($this->application->getAuthorizations()->isEditor()) {
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
        if ($this->application->getAuthorizations()->isEditor()) {
            $columns[] = ['field' => 'Published', 'label' => 'Publié'];
        }
        $query = $this->articleTableData->getQuery($person);
        $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
        $this->render('app/views/user/articles.latte', $this->params->getAll([
            'articles' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'isRedactor' => $person ? $this->application->getAuthorizations()->isRedactor() : false,
            'userConnected' => $person,
            'layout' => WebApp::getLayout(),
            'navItems' => $this->getNavItems($person),
        ]));
    }

    public function show($id): void
    {
        $person = $this->personDataHelper->getPerson();
        $article = $this->application->getAuthorizations()->getArticle($id, $person);
        if ($article) {
            $articleIds = $this->articleDataHelper->getArticleIdsBasedOnAccess($person->Email ?? null);
            $chosenArticle = $this->articleDataHelper->getLatestArticle([$id]);
            $canEdit = false;
            if ($person && $chosenArticle) {
                $canEdit = ($person && $person->Id == $chosenArticle->CreatedBy);
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

            $this->render('app/views/user/article.latte', $this->params->getAll([
                'chosenArticle' => $chosenArticle,
                'latestArticles' => $this->articleDataHelper->getLatestArticles_($articleIds),
                'canEdit' => $canEdit,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'hasSurvey' =>  (new SurveyDataHelper())->articleHasSurvey($id),
                'id' => $id,
                'userConnected' => $person,
                'navItems' => $this->getNavItems($person),
                'publishedBy' => $chosenArticle->PublishedBy && $chosenArticle->PublishedBy != $chosenArticle->CreatedBy ? $this->personDataHelper->getPublisher($chosenArticle->PublishedBy) : '',
                'canReadPool' => $this->application->getAuthorizations()->canPersonReadSurveyResults($chosenArticle, $person),
                'carouselItems' => $this->carouselHelper->getsForArticle($id),
                'message' => $messages,
            ]));
        } else if ($person == '') $this->application->message('Il faut être connecté pour pouvoir consulter cet article', 5000, 403);
        else $this->application->error403(__FILE__, __LINE__);
    }

    public function update($id): void
    {
        if ($person = $this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || $person->Id != $article->CreatedBy) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $title = $_POST['title'] ?? '';
                $content = $_POST['content'] ?? '';
                if (empty($title) || empty($content)) {
                    $_SESSION['error'] = "Le titre et le contenu sont obligatoires";
                    $this->flight->redirect('/articles/' . $id);
                    return;
                }
                $result = $this->articleDataHelper->update(
                    $id,
                    $person->Id,
                    $_POST['published'] ?? 0,
                    $title,
                    $content,
                    $_POST['idGroup'] === '' ? null : ($_POST['idGroup'] ?? null),
                    $_POST['membersOnly'] ?? 0
                );
                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                $this->flight->redirect('/articles/' . $id);
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function publish($id): void
    {
        if ($person = $this->personDataHelper->getPerson(['Editor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || ($person->Id != $article->CreatedBy && !$this->application->getAuthorizations()->isEditor())) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $isSpotlightActive = $_POST['isSpotlightActive'] ?? 0;
                if ($isSpotlightActive) {
                    $spotlightedUntil = $_POST['spotlightedUntil'] ?? 0;
                    $this->articleDataHelper->setSpotlightArticle($id, $spotlightedUntil);
                }
                $result = $this->articleDataHelper->update($id, $person->Id, $_POST['published'] ?? 0);
                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                $this->flight->redirect('/articles/' . $id);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/publish.latte', $this->params->getAll(['article' => $this->articleDataHelper->getWithAuthor($id)]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function create()
    {
        if ($person = $this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $id = $this->articleDataHelper->insert($person->Id);
                $this->flight->redirect('/articles/' . $id);
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function delete($id)
    {
        if ($person = $this->personDataHelper->getPerson(['Redactor'])) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $article = $this->articleDataHelper->getLatestArticle([$id]);
                if (!$article || $person->Id != $article->CreatedBy) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $this->articleDataHelper->delete_($id);
                $this->flight->redirect('/articles');
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showArticleCrosstab()
    {
        if ($this->personDataHelper->getPerson(['Redactor'], 1)) {
            $period = $this->flight->request()->query->period ?? 'month';
            $dateRange = Period::getDateRangeFor($period);
            $crosstabData = $this->articleCrosstab->getItems($dateRange);

            $this->render('app/views/common/crosstab.latte', $this->params->getAll([
                'crosstabData' => $crosstabData,
                'period' => $period,
                'dateRange' => $dateRange,
                'availablePeriods' => Period::gets(),
                'navbarTemplate' => '../navbar/redactor.latte',
                'title' => 'Rédateurs vs audience',
                'totalLabels' => ['articles', '']
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
