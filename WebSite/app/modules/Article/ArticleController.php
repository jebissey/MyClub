<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\exceptions\IntegrityException;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\Params;
use app\helpers\PeriodHelper;
use app\helpers\WebApp;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\GenericDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\TableController;
use app\services\EmailService;

class ArticleController extends TableController
{
    public function __construct(
        Application $application,
        private ArticleDataHelper $articleDataHelper,
        private ArticleTableDataHelper $articleTableDataHelper,
        private PersonDataHelper $personDataHelper,
        private Backup $backup,
        private ArticleCrosstabDataHelper $articleCrosstabDataHelper,
        GenericDataHelper $genericDataHelper
    ) {
        parent::__construct($application, $genericDataHelper);
    }

    public function create(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $id = $this->dataHelper->set('Article', [
            'Title'     => '',
            'Content'   => '',
            'CreatedBy' => $this->application->getConnectedUser()->person->Id ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__)
        ]);
        $this->redirect('/article/edit/' . $id);
    }

    public function delete(int $id): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() || false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $id], 'CreatedBy');
        if (!$article) {
            $this->raiseBadRequest("Article {$id} doesn't exist", __FILE__, __LINE__);
            return;
        }
        if ($this->application->getConnectedUser()->person->Id != $article->CreatedBy) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        $this->dataHelper->delete('Article', ['Id' => $id]);
        $this->redirect('/articles');
    }

    public function edit(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $id], 'CreatedBy');
        if (!$article) {
            $this->raiseBadRequest("Article {$id} doesn't exist", __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        $article = $this->articleDataHelper->getLatestArticle([$id]);

        $this->render('Article/views/article_edit.latte', Params::getAll([
            'article' => $article,
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'hasSurvey' => $this->dataHelper->get('Survey', ['IdArticle' => $id], 'ClosingDate'),
            'id' => $id,
            'userConnected' => $connectedUser->person ?? false,
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'publishedBy' => $article->PublishedBy != $article->CreatedBy ? $this->personDataHelper->getPublisher($article->PublishedBy) : '',
            'carouselItems' => $this->dataHelper->gets('Carousel', ['IdArticle' => $id]),
            'page' => $connectedUser->getPage(),
            'isEditor' => $connectedUser->isEditor(),
            'isCreator' => $connectedUser->person->Id == $article->CreatedBy
        ]));
    }

    public function fetchEmailsForArticle(int $idArticle): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $idArticle], 'CreatedBy');
        if (!$article) {
            $this->raiseBadRequest("Article {$idArticle} doesn't exist", __FILE__, __LINE__);
            return;
        }
        $articleCreatorEmail = $this->dataHelper->get('Person', ['Id' => $article->CreatedBy], 'Email')->Email;
        if (!$articleCreatorEmail) {
            $this->raiseBadRequest("Unknown author of article {$idArticle}", __FILE__, __LINE__);
            return;
        }
        $filteredEmails = $this->personDataHelper->getPersonWantedToBeAlerted($idArticle);
        $root = Application::$root;
        $articleLink = $root . '/article/' . $idArticle;
        $unsubscribeLink = $root . '/user/preferences';
        $emailTitle = 'BNW - Un nouvel article est disponible';
        $message = "Conformément à vos souhaits, ce message vous signale la présence d'un nouvel article" . "\n\n" . $articleLink
            . "\n\n Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences" . $unsubscribeLink;
        EmailService::send(
            $articleCreatorEmail,
            $articleCreatorEmail,
            $emailTitle,
            $message,
            null,
            $filteredEmails,
            false
        );
        $_SESSION['success'] = "Un courriel a été envoyé aux abonnés";
        $this->redirect('/article/' . $idArticle);
    }

    public function help(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        $this->render('Common/views/info.latte', [
            'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_redactor'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->application->getConnectedUser()->isRedactor() ?? false,
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true,
            'page' => $this->application->getConnectedUser()->getPage(),
        ]);
    }

    public function home(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = 'redactor';
        $this->render('Webmaster/views/redactor.latte', Params::getAll([
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function index(): void
    {
        $connectedUser = $this->application->getConnectedUser();
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
        $query = $this->articleTableDataHelper->getQuery($connectedUser, (int)($this->articleDataHelper->getSpotlightArticle()['articleId'] ?? -1));
        $data = $this->prepareTableData($query, $filterValues, (int)($this->flight->request()->query['tablePage'] ?? 0));
        $this->render('Article/views/articles_index.latte', Params::getAll([
            'articles' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'isRedactor' => $connectedUser->isRedactor() ?? false,
            'userConnected' => $connectedUser->person ?? false,
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'page' => $connectedUser->getPage(),
        ]));
    }

    public function publish(int $id): void
    {
        if (!($this->application->getConnectedUser()->isEditor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $article = $this->articleDataHelper->getLatestArticle([$id]);
            if (!$article || ($this->application->getConnectedUser()->person->Id != $article->CreatedBy && !$this->application->getConnectedUser()->isEditor())) {
                $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
                return;
            }
            $schema = [
                'isSpotlightActive' => FilterInputRule::Bool->value,
                'spotlightedUntil' => FilterInputRule::DateTime->value,
                'published' => FilterInputRule::Int->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            $isSpotlightActive = $input['isSpotlightActive'] ?? false;
            if ($isSpotlightActive) {
                $spotlightedUntil = $input['spotlightedUntil'] ?? date('Y-m-d H:i:s', strtotime('+1 week'));
                $this->articleDataHelper->setSpotlightArticle($id, $spotlightedUntil);
            }
            $result = $this->dataHelper->set('Article', [
                'PublishedBy'    => $input['published'] ?? 0  == 1 ? $this->application->getConnectedUser()->person->Id : null,
                'LastUpdate'     => date('Y-m-d H:i:s')
            ], ['Id' => $id]);
            if ($result) {
                $_SESSION['success'] = "L'article a été mis à jour avec succès";
                $this->backup->save();
            } else $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
            $this->redirect('/article/' . $id);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->render('User/views/publish.latte', Params::getAll([
                'article' => $this->articleDataHelper->getWithAuthor($id),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function show(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $id], 'Id, OnlyForMembers');
        if (!$article) {
            $this->raiseBadRequest("Article {$id} doesn't exist", __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if ($article->OnlyForMembers == 1 && $connectedUser->person == null) {
            $this->raiseBadRequest("Il faut être connecté pour pouvoir consulter cet article", __FILE__, __LINE__);
            return;
        }
        try {
            $article = $this->authorizationDataHelper->getArticle($id, $connectedUser);
            if (!$article) {
                $this->raiseforbidden(__FILE__, __LINE__);
                return;
            }
            $article = $this->articleDataHelper->getLatestArticle([$id]);

            $this->render('Article/views/article_show.latte', Params::getAll([
                'article' => $article,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'hasSurvey' => $this->dataHelper->get('Survey', ['IdArticle' => $id], 'ClosingDate'),
                'id' => $id,
                'userConnected' => $connectedUser->person ?? false,
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'publishedBy' => $article->PublishedBy && $article->PublishedBy != $article->CreatedBy ? $this->personDataHelper->getPublisher($article->PublishedBy) : '',
                'canReadPool' => $this->authorizationDataHelper->canPersonReadSurveyResults($article, $connectedUser),
                'carouselItems' => $this->dataHelper->gets('Carousel', ['IdArticle' => $id]),
                'page' => $connectedUser->getPage(),
            ]));
        } catch (QueryException $e) {
            $this->raiseBadRequest($e->getMessage(),  __FILE__, __LINE__);
        }
    }

    public function showArticleCrosstab(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() || false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $period = $this->flight->request()->query->period ?? 'month';
        $dateRange = PeriodHelper::getDateRangeFor($period);
        $crosstabData = $this->articleCrosstabDataHelper->getItems($dateRange);

        $this->render('Common/views/crosstab.latte', Params::getAll([
            'crosstabData' => $crosstabData,
            'period' => $period,
            'dateRange' => $dateRange,
            'availablePeriods' => PeriodHelper::gets(),
            'navbarTemplate' => '../../Webmaster/views/navbar/redactor.latte',
            'title' => 'Rédateurs vs audience',
            'totalLabels' => ['articles', ''],
            'page' => $this->application->getConnectedUser()->getPage(1),
        ]));
    }

    public function update(int $id): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() || false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->articleDataHelper->getLatestArticle([$id]);
        if (!$article || ($this->application->getConnectedUser()->person?->Id ?? 0) != $article->CreatedBy) {
            $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
            return;
        }
        $schema = [
            'title' => FilterInputRule::HtmlSafeName->value,
            'content' => FilterInputRule::Html->value,
            'published' => FilterInputRule::Int->value,
            'idGroup' => FilterInputRule::Int->value,
            'membersOnly' => FilterInputRule::Int->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $title = $input['title'] ?? '???';
        $content = $input['content'] ?? '???';
        if (empty($title) || empty($content)) {
            $_SESSION['error'] = "Le titre et le contenu sont obligatoires";
            $this->redirect('/article/' . $id);
            return;
        }
        $result = $this->dataHelper->set('Article', [
            'Title'          => $title,
            'Content'        => $content,
            'PublishedBy'    => $input['published'] == 1 ? $this->application->getConnectedUser()->person->Id : null,
            'IdGroup'        => $input['idGroup'],
            'OnlyForMembers' => $input['membersOnly'] ?? 0,
            'LastUpdate'     => date('Y-m-d H:i:s')
        ], ['Id' => $id]);
        if ($result) {
            $_SESSION['success'] = "L'article a été mis à jour avec succès";
            $this->backup->save();
        } else $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
        $this->redirect('/article/' . $id);
    }
}
