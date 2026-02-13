<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\exceptions\IntegrityException;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\GravatarHandler;
use app\helpers\PeriodHelper;
use app\helpers\WebApp;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Common\TableController;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\services\EmailService;

class ArticleController extends TableController
{
    private ArticleAuthorizationService $authorizationService;

    public function __construct(
        Application $application,
        private ArticleDataHelper $articleDataHelper,
        private ArticleTableDataHelper $articleTableDataHelper,
        private PersonDataHelper $personDataHelper,
        private Backup $backup,
        private ArticleCrosstabDataHelper $articleCrosstabDataHelper,
        private MessageDataHelper $messageDataHelper,
        private EmailService $emailService,
    ) {
        parent::__construct($application);
        $this->authorizationService = new ArticleAuthorizationService(
            $this->dataHelper,
            new AuthenticationService($this->dataHelper, $this->emailService),
            new AuthorizationDataHelper($this->application)
        );
    }

    public function carousel(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $id], 'CreatedBy');
        if (!$article) {
            $msg = str_replace(
                '{id}',
                (string)$id,
                $this->languagesDataHelper->translate('article.error.not_found')
            );
            $this->raiseBadRequest($msg, __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        $article = $this->articleDataHelper->getLatestArticle([$id]);
        if ($this->authorizationService->canEdit($id, $connectedUser)) {
            $this->render('Article/views/article_carousel.latte', $this->getAllParams([
                'article' => $article,
                'carouselItems' => $this->dataHelper->gets('Carousel', ['IdArticle' => $id]),
                'page' => $connectedUser->getPage(),
            ]));
        } else {
            $this->raiseforbidden(__FILE__, __LINE__);
        }
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
            $msg = str_replace(
                '{id}',
                (string)$id,
                $this->languagesDataHelper->translate('article.error.not_found')
            );
            $this->raiseBadRequest($msg, __FILE__, __LINE__);
            return;
        }
        if ($this->authorizationService->canDelete($id, $this->application->getConnectedUser())) {
            $this->dataHelper->delete('Article', ['Id' => $id]);
            $this->redirect('/articles');
        } else {
            $this->raiseforbidden(__FILE__, __LINE__);
        }
    }

    public function edit(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $id], 'CreatedBy');
        if (!$article) {
            $msg = str_replace(
                '{id}',
                (string)$id,
                $this->languagesDataHelper->translate('article.error.not_found')
            );
            $this->raiseBadRequest($msg, __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        $article = $this->articleDataHelper->getLatestArticle([$id]);

        if ($connectedUser->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        } else if ($connectedUser->person->Id !== $article->CreatedBy) {
            if ($this->authorizationService->canRead($id, $connectedUser)) {
                $this->show($id);
                return;
            } else {
                $this->raiseforbidden(__FILE__, __LINE__);
                return;
            }
        }

        $this->render('Article/views/article_edit.latte', $this->getAllParams([
            'article' => $article,
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'hasSurvey' => $this->dataHelper->get('Survey', ['IdArticle' => $id], 'ClosingDate'),
            'hasOrder' => $this->dataHelper->get('Order', ['IdArticle' => $id], 'ClosingDate'),
            'id' => $id,
            'userConnected' => $connectedUser->person ?? false,
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'publishedBy' => $article->PublishedBy != $article->CreatedBy ? $this->personDataHelper->getPublisher($article->PublishedBy) : '',
            'carouselItems' => $this->dataHelper->gets('Carousel', ['IdArticle' => $id]),
            'page' => $connectedUser->getPage(),
            'isEditor' => $connectedUser->isEditor(),
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
            $msg = str_replace(
                '{id}',
                (string)$idArticle,
                $this->languagesDataHelper->translate('article.error.unknown_author')
            );
            $this->raiseBadRequest($msg, __FILE__, __LINE__);
            return;
        }
        $filteredEmails = $this->personDataHelper->getPersonWantedToBeAlerted($idArticle);
        $root = Application::$root;
        $articleLink = $root . '/article/' . $idArticle;
        $unsubscribeLink = $root . '/user/preferences';
        $title = str_replace(
            '{root}',
            $root,
            $this->languagesDataHelper->translate('article.email.new_title')
        );
        $intro = $this->languagesDataHelper->translate('article.email.body_intro');
        $unsubscribe = $this->languagesDataHelper->translate('article.email.unsubscribe');
        $message =
            $intro . "\n\n" .
            $articleLink . "\n\n" .
            $unsubscribe . "\n" .
            $unsubscribeLink;
        $this->emailService->send(
            $articleCreatorEmail,
            $articleCreatorEmail,
            $title,
            $message,
            null,
            $filteredEmails,
            false
        );
        $_SESSION['success'] = $this->languagesDataHelper->translate('article.success.email_sent');
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
        $this->render('Webmaster/views/redactor.latte', $this->getAllParams([
            'page' => $this->application->getConnectedUser()->getPage(),
            'content' => $this->languagesDataHelper->translate('Redactor')
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
            ['name' => 'PersonName', 'label' => $this->languagesDataHelper->translate('article.label.created_by')],
            ['name' => 'title', 'label' => $this->languagesDataHelper->translate('article.label.title')],
            ['name' => 'lastUpdate', 'label' => $this->languagesDataHelper->translate('article.label.last_update')],
            ['name' => 'pool', 'label' => $this->languagesDataHelper->translate('article.label.pool')],
            ['name' => 'GroupName', 'label' => $this->languagesDataHelper->translate('article.label.group')],
            ['name' => 'Content', 'label' => $this->languagesDataHelper->translate('article.label.content')],
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
            ['field' => 'Messages', 'label' => 'Messages'],
        ];
        if ($connectedUser->isEditor()) {
            $columns[] = ['field' => 'Published', 'label' => 'Publié'];
        }
        $query = $this->articleTableDataHelper->getQuery($connectedUser, (int)($this->articleDataHelper->getSpotlightArticle()['articleId'] ?? -1));
        $data = $this->prepareTableData($query, $filterValues);
        $this->render('Article/views/articles_index.latte', $this->getAllParams([
            'articles' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'userConnected' => $connectedUser->person ?? false,
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'page' => $connectedUser->getPage(),
        ]));
    }

    public function publish(int $id): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if (!$this->dataHelper->get('Article', ['Id' => $id], 'Id')) {
            $this->raiseBadRequest("Article {$id} doesn't exist", __FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->authorizationService->canPublish($id, $this->application->getConnectedUser())) {
                $this->raiseforbidden(__FILE__, __LINE__);
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
                $_SESSION['success'] = $this->languagesDataHelper->translate('article.success.updated');
                $this->backup->save();
            } else $_SESSION['error'] = $this->languagesDataHelper->translate('article.error.update_failed');
            $this->redirect('/article/' . $id);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->render('User/views/publish.latte', $this->getAllParams([
                'article' => $this->articleDataHelper->getWithAuthor($id),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        } else $this->raiseMethodNotAllowed(__FILE__, __LINE__);
    }

    public function show(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $id], 'Id, OnlyForMembers');
        if (!$article) {
            $msg = str_replace(
                '{id}',
                (string)$id,
                $this->languagesDataHelper->translate('article.error.not_found')
            );
            $this->raiseBadRequest($msg, __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if (!$this->authorizationService->canRead($id, $connectedUser)) {
            $this->raiseBadRequest($this->languagesDataHelper->translate('article.error.login_required'), __FILE__, __LINE__);
            return;
        }
        try {
            $article = $this->authorizationDataHelper->getArticle($id, $connectedUser);
            if (!$article) {
                $this->raiseforbidden(__FILE__, __LINE__);
                return;
            }
            $article = $this->articleDataHelper->getLatestArticle([$id]);

            $this->render('Article/views/article_show.latte', $this->getAllParams([
                'article' => $article,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'hasSurvey' => $this->dataHelper->get('Survey', ['IdArticle' => $id], 'ClosingDate'),
                'hasOrder' => $this->dataHelper->get('Order', ['IdArticle' => $id], 'ClosingDate'),
                'id' => $id,
                'userConnected' => $connectedUser->person ?? false,
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'publishedBy' => $article->PublishedBy && $article->PublishedBy != $article->CreatedBy ? $this->personDataHelper->getPublisher($article->PublishedBy) : '',
                'canReadPool' => $this->authorizationDataHelper->canPersonReadSurveyResults($article, $connectedUser),
                'canReadOrder' => $this->authorizationDataHelper->canPersonReadOrderResults($article, $connectedUser),
                'carouselItems' => $this->dataHelper->gets('Carousel', ['IdArticle' => $id]),
                'page' => $connectedUser->getPage(),
                'countOfMessages' => count($this->dataHelper->gets('Message', [
                    '"From"' => 'User',
                    'ArticleId' => $id
                ])),
                'isCreator' => $connectedUser !== null && $connectedUser?->person?->Id === $article->CreatedBy
            ]));
        } catch (QueryException $e) {
            $this->raiseBadRequest($e->getMessage(),  __FILE__, __LINE__);
        }
    }

    public function showArticleChat($articleId): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $articleId], 'Id, OnlyForMembers, Title, CreatedBy, IdGroup');
        if (!$article) {
            $this->raiseBadRequest("L'article {$articleId} n'existe pas", __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if (!$this->authorizationService->canRead($articleId, $connectedUser)) {
            $this->raiseBadRequest($this->languagesDataHelper->translate('article.error.login_required'), __FILE__, __LINE__);
            return;
        }
        $person = $connectedUser->person;
        $person->UserImg = WebApp::getUserImg($person, new GravatarHandler());
        $this->render('Common/views/chat.latte', $this->getAllParams([
            'article' => $article,
            'event' => null,
            'group' => null,
            'messages' => $this->messageDataHelper->getArticleMessages($articleId),
            'person' => $person,
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
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

        $this->render('Common/views/crosstab.latte', $this->getAllParams([
            'crosstabData' => $crosstabData,
            'period' => $period,
            'dateRange' => $dateRange,
            'availablePeriods' => PeriodHelper::gets(),
            'navbarTemplate' => '../../Webmaster/views/navbar/redactor.latte',
            'title' => 'Rédacteurs vs audience',
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
            $_SESSION['error'] = $this->languagesDataHelper->translate('article.error.title_content_required');
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
            $_SESSION['success'] = $this->languagesDataHelper->translate('article.success.updated');
            $this->backup->save();
        } else $_SESSION['error'] = $this->languagesDataHelper->translate('article.error.update_failed');
        $this->redirect('/article/' . $id);
    }
}
