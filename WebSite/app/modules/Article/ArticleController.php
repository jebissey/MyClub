<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\enums\Period;
use app\exceptions\IntegrityException;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\GravatarHandler;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\LogDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Common\TableController;
use app\modules\Common\services\ArticleService;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\services\EmailService;
use app\modules\Common\services\MessageService;
use app\valueObjects\EmailMessage;

class ArticleController extends TableController
{
    private const TOP = 50;
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
        private LogDataHelper $logDataHelper,
        private ArticleService $articleService
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
                ($this->t)('article.error.not_found')
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
            $this->raiseForbidden(__FILE__, __LINE__);
        }
    }

    public function changeOwner(int $id): void
    {
        if (!($this->application->getConnectedUser()->isEditor() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (!$this->dataHelper->get('Article', ['Id' => $id], 'Id')) {
            $this->raiseBadRequest("Article {$id} doesn't exist", __FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $schema = [
                'newOwnerId' => FilterInputRule::Int->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            $newOwnerId = $input['newOwnerId'] ?? null;
            if (!$newOwnerId) {
                $_SESSION['error'] = ($this->t)('article.error.owner_required');
                $this->redirect('/articles');
                return;
            }
            $result = $this->dataHelper->set('Article', [
                'CreatedBy'  => $newOwnerId,
                'LastUpdate' => date('Y-m-d H:i:s')
            ], ['Id' => $id]);
            if ($result) {
                $_SESSION['success'] = ($this->t)('article.success.updated');
                $this->backup->save();
            } else {
                $_SESSION['error'] = ($this->t)('article.error.update_failed');
            }
            $this->redirect('/article/' . $id);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->render('User/views/changeOwner.latte', $this->getAllParams([
                'article' => $this->articleDataHelper->getWithAuthor($id),
                'redactors' => $this->personDataHelper->getRedactors(),
            ]));
        } else {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
        }
    }

    public function create(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $userId = $this->application->getConnectedUser()->person->Id
            ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);

        $articleId = $this->articleService->createWithMedia($userId);

        $this->redirect('/article/edit/' . $articleId);
    }

    public function delete(int $id): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() || false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
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
                ($this->t)('article.error.not_found')
            );
            $this->raiseBadRequest($msg, __FILE__, __LINE__);
            return;
        }
        if ($this->authorizationService->canDelete($id, $this->application->getConnectedUser())) {
            $this->dataHelper->delete('Article', ['Id' => $id]);
            $this->redirect('/articles');
        } else {
            $this->raiseForbidden(__FILE__, __LINE__);
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
                ($this->t)('article.error.not_found')
            );
            $this->raiseBadRequest($msg, __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        $article = $this->articleDataHelper->getLatestArticle([$id]);

        if ($connectedUser->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        } else if ($connectedUser->person->Id !== $article->CreatedBy) {
            if ($this->authorizationService->canRead($id, $connectedUser)) {
                $this->show($id);
                return;
            } else {
                $this->raiseForbidden(__FILE__, __LINE__);
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
            'translations' => [
                'editorNotReady' => ($this->t)('article.edit.error.editor_not_ready'),
                'titleRequired' => ($this->t)('article.edit.error.title_required'),
                'contentRequired' => ($this->t)('article.edit.error.content_required'),
            ],
        ]));
    }

    public function fetchEmailsForArticle(int $idArticle): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
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
                ($this->t)('article.error.unknown_author')
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
            ($this->t)('article.email.new_title')
        );
        $intro = ($this->t)('article.email.body_intro');
        $unsubscribe = ($this->t)('article.email.unsubscribe');
        $message =
            $intro . "\n\n" .
            $articleLink . "\n\n" .
            $unsubscribe . "\n" .
            $unsubscribeLink;

        $emailMessage = new EmailMessage(
            from: $articleCreatorEmail,
            to: $articleCreatorEmail,
            subject: $title,
            body: $message,
            isHtml: false,
            cc: $filteredEmails
        );
        if ($this->emailService->send($emailMessage)) {
            MessageService::set(($this->t)('article.success.email_sent') . ' (' . count($filteredEmails) . ')');
        } else {
            MessageService::set(($this->t)('article.error.email_failed'), 'danger');
        }

        $this->redirect('/article/' . $idArticle);
    }

    public function help(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $lang = TranslationManager::getCurrentLanguage();
            $this->render('Common/views/info.latte', $this->getAllParams([
                'content' => $this->dataHelper->get('Languages', ['Name' => 'Help_Redactor'], $lang)->$lang ?? '',
                'timer' => 0,
                'btn_HistoryBack' => true,
            ]));
        }
    }

    public function home(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $_SESSION['navbar'] = 'redactor';
            $this->render('Article/views/redactor.latte', $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
                'content' => ($this->t)('Redactor')
            ]));
        }
    }

    public function index(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        $schema = [
            'PersonName' => FilterInputRule::PersonName->value,
            'title'      => FilterInputRule::Content->value,
            'timestamp'  => FilterInputRule::DateTime->value,
            'lastUpdate' => FilterInputRule::DateTime->value,
            'published'  => ['oui', 'non'],
            'pool'       => ['oui', 'non'],
            'menu'       => ['oui', 'non'],
            'GroupName'  => FilterInputRule::HtmlSafeName->value,
            'Content'    => FilterInputRule::Content->value,
            'Id'         => FilterInputRule::Int->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $filterConfig = [
            ['name' => 'PersonName', 'label' => ($this->t)('article.label.created_by')],
            ['name' => 'title', 'label' => ($this->t)('article.label.title')],
            ['name' => 'lastUpdate', 'label' => ($this->t)('article.label.last_update')],
            ['name' => 'pool', 'label' => ($this->t)('article.label.pool')],
            ['name' => 'GroupName', 'label' => ($this->t)('article.label.group')],
            ['name' => 'Content', 'label' => ($this->t)('article.label.content')],
            ['name' => 'Id', 'label' => 'ID'],
        ];
        if ($connectedUser->isEditor() || false) {
            $filterConfig[] = ['name' => 'published', 'label' => 'Publié'];
            $filterConfig[] = ['name' => 'menu', 'label' => 'Menu'];
        }
        $columns = [
            ['field' => 'PersonName', 'label' => ($this->t)('article.label.created_by')],
            ['field' => 'Title', 'label' => ($this->t)('article.label.title')],
            ['field' => 'LastUpdate', 'label' => ($this->t)('article.label.last_update')],
            ['field' => 'Pool', 'label' => ($this->t)('article.label.pool')],
            ['field' => 'PoolDetail', 'label' => ($this->t)('article.label.pool_detail')],
            ['field' => 'Messages', 'label' => ($this->t)('article.label.messages')],
            ['field' => 'GroupName', 'label' => ($this->t)('article.label.group')],
            ['field' => 'ForMembers', 'label' => ($this->t)('article.label.for_members')],
        ];
        if ($connectedUser->isEditor()) {
            $columns[] = ['field' => 'Menu', 'label' => ($this->t)('article.label.menu')];
            $columns[] = ['field' => 'Published', 'label' => ($this->t)('article.label.published')];
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
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'page' => $connectedUser->getPage(),
            'btn_HistoryBack' => true,
            'btn_Parent'      => "/",
        ]));
    }

    public function publicIndex(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEditor(), __FILE__, __LINE__)) {
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        $schema = [
            'title'      => FilterInputRule::Content->value,
            'lastUpdate' => FilterInputRule::DateTime->value,
            'Id'         => FilterInputRule::Int->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $filterConfig = [
            ['name' => 'title', 'label' => ($this->t)('article.label.title')],
            ['name' => 'lastUpdate', 'label' => ($this->t)('article.label.last_update')],
            ['name' => 'Id', 'label' => 'ID'],
        ];
        $columns = [
            ['field' => 'Title', 'label' => ($this->t)('article.label.title')],
            ['field' => 'LastUpdate', 'label' => ($this->t)('article.label.last_update')],
            ['field' => 'ReferenceSource', 'label' => ($this->t)('article.label.reference_source')],
        ];
        $query = $this->articleTableDataHelper->getQueryForPublicArticles();
        $data = $this->prepareTableData($query, $filterValues);
        $this->render('Article/views/publicArticles_index.latte', $this->getAllParams([
            'articles' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'userConnected' => $connectedUser->person ?? false,
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'page' => $connectedUser->getPage(),
            'btn_HistoryBack' => true,
            'btn_Parent'      => "/",
        ]));
    }

    public function publish(int $id): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (!$this->dataHelper->get('Article', ['Id' => $id], 'Id')) {
            $this->raiseBadRequest("Article {$id} doesn't exist", __FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->authorizationService->canPublish($id, $this->application->getConnectedUser())) {
                $this->raiseForbidden(__FILE__, __LINE__);
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
                $_SESSION['success'] = ($this->t)('article.success.updated');
                $this->backup->save();
            } else $_SESSION['error'] = ($this->t)('article.error.update_failed');
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
        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'])) {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $id], 'Id, OnlyForMembers');
        if (!$article) {
            $msg = str_replace(
                '{id}',
                (string)$id,
                ($this->t)('article.error.not_found')
            );
            $this->raiseBadRequest($msg, __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if (!$this->authorizationService->canRead($id, $connectedUser)) {
            if ($connectedUser->person === null) {
                $result = $this->application->getAuthenticationService()->handleRememberMeLogin();
                if ($result && $result->isSuccess()) {
                    $this->application->getConnectedUser()->get();
                    $this->redirect($_SERVER['REQUEST_URI'], ApplicationError::Ok, "Auto sign in succeeded for {$result->getUser()->Email}");
                    return;
                }
                $this->redirect('/user/sign/in?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            } else {
                $this->raiseForbidden(__FILE__, __LINE__, 10000, false);
            }
            return;
        }
        try {
            $article = $this->authorizationDataHelper->getArticle($id, $connectedUser);
            if (!$article) {
                $this->raiseForbidden(__FILE__, __LINE__);
                return;
            }
            $article = $this->articleDataHelper->getLatestArticle([$id]);
            [$message, $messageType] = MessageService::get();

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
                'isCreator' => $connectedUser !== null && $connectedUser?->person?->Id === $article->CreatedBy,
                'message' => $message ?? '',
                'messageType' => $messageType ?? '',
                'btn_HistoryBack' => true,
                'btn_Parent'      => "/articles",
            ]));
        } catch (QueryException $e) {
            $this->raiseBadRequest($e->getMessage(),  $e->getFile(), $e->getLine());
        }
    }

    public function showArticleChat(int $articleId): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
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
            $this->raiseBadRequest(($this->t)('article.error.login_required'), __FILE__, __LINE__);
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
            'btn_HistoryBack' => true,
            'btn_Parent'      => "/article/{$articleId}",
        ]));
    }

    public function showArticleCrosstab(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() || false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $period = Period::from($this->flight->request()->query->period ?? 'month');
        $dateRange = $period->dateRange();
        $crosstabData = $this->articleCrosstabDataHelper->getItems($dateRange);

        $this->render('Common/views/crosstab.latte', $this->getAllParams([
            'crosstabData' => $crosstabData,
            'period' => $period->value,
            'dateRange' => $dateRange,
            'availablePeriods' => Period::gets($this->languagesDataHelper),
            'navbarTemplate' => '../../Webmaster/views/navbar/redactor.latte',
            'title' => 'Rédacteurs vs audience',
            'totalLabels' => ['articles', ''],
            'page' => $this->application->getConnectedUser()->getPage(1),
        ]));
    }

    public function topArticlesByPeriod(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $period        = Period::fromRequest($this->application, $this->flight->request());
        $dateCondition = $period->dateConditions('CreatedAt');
        $topPages      = $this->logDataHelper->getTopArticles($dateCondition, self::TOP);

        $articleIds = array_values(array_filter(
            array_map(fn($p) => $p->articleId ?? null, $topPages)
        ));
        $authors = $this->articleDataHelper->getAuthorsByArticleIds($articleIds);

        foreach ($topPages as $page) {
            $author             = $authors[$page->articleId ?? null] ?? null;
            $page->AuthorName   = $author?->PersonName;
            $page->ArticleTitle = $author?->ArticleTitle;
        }

        $this->render('Article/views/topArticles.latte', $this->getAllParams([
            'title'       => ($this->t)('visitor_insights.top_articles.title'),
            'period'      => $period->value,
            'periodFrom'  => $period->getStart()->format('Y-m-d H:i:s'),
            'periodTo'    => $period->getEnd()->format('Y-m-d H:i:s'),
            'topPages'    => $topPages,
            'page'        => $this->application->getConnectedUser()->getPage(),
            'translations' => TranslationManager::getCreationTimeModalTranslations($this->languagesDataHelper),
        ]));
    }

    public function update(int $id): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() || false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
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
            $_SESSION['error'] = ($this->t)('article.error.title_content_required');
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
            $_SESSION['success'] = ($this->t)('article.success.updated');
            $this->backup->save();
        } else $_SESSION['error'] = ($this->t)('article.error.update_failed');
        $this->redirect('/article/' . $id);
    }
}
