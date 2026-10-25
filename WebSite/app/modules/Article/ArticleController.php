<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\ArticleAccess;
use app\enums\FilterInputRule;
use app\enums\Period;
use app\exceptions\IntegrityException;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\GravatarHandler;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\AlertRecipientDataHelper;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\CarouselDataHelper;
use app\models\LogDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Article\valueObjects\Result;
use app\modules\Article\viewModels\ArticleCarouselViewModel;
use app\modules\Article\viewModels\ArticleEditViewModel;
use app\modules\Article\viewModels\ArticleShowViewModel;
use app\modules\Article\viewModels\ChangeOwnerViewModel;
use app\modules\Article\viewModels\PublishViewModel;
use app\modules\Article\viewModels\RedactorHomeViewModel;
use app\modules\Common\TableController;
use app\modules\Common\services\AnonymousAccessService;
use app\modules\Common\services\ArticleService;
use app\modules\Common\services\EmailService;
use app\modules\Common\services\MessageService;
use app\modules\Common\valueObjects\ClosingVisibilityRow;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\Common\viewModels\ChatViewModel;
use app\modules\Common\viewModels\CrosstabViewModel;
use app\modules\Common\viewModels\TableIndexViewModel;
use app\modules\Common\viewModels\TopItemsByPeriodViewModel;

/**
 * @phpstan-import-type ClosingVisibilityRowShape from ClosingVisibilityRow
 */
final class ArticleController extends TableController
{
    private const TOP = 50;

    public function __construct(
        Application $application,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly ArticleTableDataHelper $articleTableDataHelper,
        private readonly PersonDataHelper $personDataHelper,
        private readonly Backup $backup,
        private readonly ArticleCrosstabDataHelper $articleCrosstabDataHelper,
        private readonly MessageDataHelper $messageDataHelper,
        private readonly EmailService $emailService,
        private readonly LogDataHelper $logDataHelper,
        private readonly ArticleService $articleService,
        private readonly AlertRecipientDataHelper $alertRecipientDataHelper,
        private readonly CarouselDataHelper $carouselDataHelper,
        private readonly ArticleAuthorizationService $authorizationService,
        private readonly AnonymousAccessService $anonymousAccessService,
    ) {
        parent::__construct($application);
    }

    public function carousel(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $article = $this->unwrapOrRespond(
            $this->loadAuthorized($id, $this->authorizationService->canEdit(...)),
            $id
        );
        if ($article === null) {
            return;
        }

        $viewModel = new ArticleCarouselViewModel(
            article: $article,
            carouselItems: array_values($this->dataHelper->gets('Carousel', ['IdArticle' => $id], 'Id, IdArticle, Item')),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Article/views/article_carousel.latte', $viewModel->toArray());
    }

    public function changeOwner(int $id): void
    {
        if (!$this->authorizationService->canChangeOwner($this->application->getConnectedUser())) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        if (!$this->dataHelper->get('Article', ['Id' => $id], 'Id')) {
            $this->respondToDenial(ArticleAccess::NotFound, $id);
            return;
        }
        if (WebApp::getRequestMethod() === 'POST') {
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
        } elseif (WebApp::getRequestMethod() === 'GET') {
            $article = $this->articleDataHelper->getWithAuthor($id);
            if ($article === false) {
                $this->respondToDenial(ArticleAccess::NotFound, $id);
                return;
            }
            $viewModel = new ChangeOwnerViewModel(
                article: $article,
                redactors: array_values($this->personDataHelper->getRedactors()),
                layoutParams: $this->getAllParams([]),
            );
            $this->views->render('User/views/changeOwner.latte', $viewModel->toArray());
        } else {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
        }
    }

    public function create(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $userId = $this->application->getConnectedUser()->person->Id
            ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);

        $articleId = $this->articleService->createWithMedia($userId);

        $this->redirect('/article/edit/' . $articleId);
    }

    public function delete(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $article = $this->unwrapOrRespond(
            $this->loadAuthorized($id, $this->authorizationService->canDelete(...)),
            $id
        );
        if ($article === null) {
            return;
        }

        $this->dataHelper->delete('Article', ['Id' => $id]);
        $this->redirect('/articles');
    }

    public function edit(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person === null || !$connectedUser->isRedactor()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        $article = $this->unwrapOrRespond(
            $this->loadAuthorized($id, $this->authorizationService->canEdit(...)),
            $id
        );
        if ($article === null) {
            return;
        }

        $viewModel = new ArticleEditViewModel(
            id: $id,
            article: $article,
            groups: array_values(
                $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name')
            ),
            hasSurvey: $this->dataHelper->get('Survey', ['IdArticle' => $id], 'ClosingDate') ?: null,
            hasOrder: $this->dataHelper->get('Order', ['IdArticle' => $id], 'ClosingDate') ?: null,
            userConnected: $connectedUser->person ?? false,
            navItems: $this->getNavItems($connectedUser->person),
            publishedBy: $this->publisherName($article),
            carouselItems: array_values(
                $this->dataHelper->gets('Carousel', ['IdArticle' => $id], 'Id, IdArticle, Item')
            ),
            i18n: [
                'editorNotReady' => ($this->t)('article.edit.error.editor_not_ready'),
                'titleRequired' => ($this->t)('article.edit.error.title_required'),
                'contentRequired' => ($this->t)('article.edit.error.content_required'),
            ],
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Article/views/article_edit.latte', $viewModel->toArray());
    }

    public function fetchEmailsForArticle(int $idArticle): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $article = $this->dataHelper->get('Article', ['Id' => $idArticle], 'CreatedBy');
        if (!$article) {
            $this->respondToDenial(ArticleAccess::NotFound, $idArticle);
            return;
        }
        /** @var object{CreatedBy: int} $article */
        $articleCreator = $this->dataHelper->get('Individual', ['Id' => $article->CreatedBy], 'Email');
        /** @var object{Email: string}|false $articleCreator */
        if ($articleCreator === false) {
            $msg = str_replace(
                '{id}',
                (string)$idArticle,
                ($this->t)('article.error.unknown_author')
            );
            $this->errors->badRequest($msg, __FILE__, __LINE__);
            return;
        }
        $filteredEmails = $this->alertRecipientDataHelper->getPersonWantedToBeAlerted($idArticle);
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
            from: $articleCreator->Email,
            to: $articleCreator->Email,
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
        $this->renderHelp('Help_Redactor', fn($u) => $u->isRedactor(), __FILE__, __LINE__);
    }

    public function home(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $_SESSION['navbar'] = 'redactor';
            $viewModel = new RedactorHomeViewModel(
                content: '',
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );
            $this->views->render('Article/views/redactor.latte', $viewModel->toArray());
        }
    }

    public function index(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        $schema = [
            'PersonName' => FilterInputRule::PersonName->value,
            'title'      => FilterInputRule::Content->value,
            'lastUpdate' => FilterInputRule::DateTime->value,
            'published'  => ['oui', 'non'],
            'pool'       => ['oui', 'non'],
            'menu'       => ['oui', 'non'],
            'GroupName'  => FilterInputRule::HtmlSafeName->value,
            'Content'    => FilterInputRule::Content->value,
            'Id'         => FilterInputRule::Int->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        /** @var array<string, string> $filterValues */
        $filterConfig = [
            ['name' => 'PersonName', 'label' => ($this->t)('article.label.created_by')],
            ['name' => 'title', 'label' => ($this->t)('article.label.title')],
            ['name' => 'lastUpdate', 'label' => ($this->t)('article.label.last_update')],
            ['name' => 'pool', 'label' => ($this->t)('article.label.pool')],
            ['name' => 'GroupName', 'label' => ($this->t)('article.label.group')],
            ['name' => 'Content', 'label' => ($this->t)('article.label.content')],
            ['name' => 'Id', 'label' => 'ID'],
        ];
        if ($connectedUser->isEditor()) {
            $filterConfig[] = ['name' => 'published', 'label' => ($this->t)('article.label.published')];
            $filterConfig[] = ['name' => 'menu', 'label' => ($this->t)('article.label.menu')];
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
        $spotlight = $this->articleDataHelper->getSpotlightArticle();
        $spotlightArticleId = isset($spotlight['articleId']) && is_numeric($spotlight['articleId'])
            ? (int)$spotlight['articleId']
            : -1;
        $query = $this->articleTableDataHelper->getQuery(
            $connectedUser,
            $spotlightArticleId
        );
        $data = $this->prepareTableData($query, $filterValues);

        $viewModel = new TableIndexViewModel(
            articles: array_values($data['items']),
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            filters: $filterConfig,
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
        $this->views->render('Article/views/articles_index.latte', $viewModel->toArray());
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
        /** @var array<string, string> $filterValues */
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

        $viewModel = new TableIndexViewModel(
            articles: array_values($data['items']),
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            filters: $filterConfig,
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
        $this->views->render('Article/views/publicArticles_index.latte', $viewModel->toArray());
    }

    public function publish(int $id): void
    {
        if (!$this->application->getConnectedUser()->isRedactor()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        // Applies to GET and POST: the form is only shown to someone who can submit it
        $article = $this->unwrapOrRespond(
            $this->loadAuthorized($id, $this->authorizationService->canPublish(...)),
            $id
        );
        if ($article === null) {
            return;
        }

        if (WebApp::getRequestMethod() === 'POST') {
            $schema = [
                'isSpotlightActive' => FilterInputRule::Bool->value,
                'spotlightedUntil' => FilterInputRule::DateTime->value,
                'published' => FilterInputRule::Int->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            $isSpotlightActive = $input['isSpotlightActive'] ?? false;
            if ($isSpotlightActive) {
                $spotlightedUntil = $input['spotlightedUntil'] ?? date('Y-m-d H:i:s', strtotime('+1 week'));
                if (!is_string($spotlightedUntil)) {
                    $spotlightedUntil = date('Y-m-d H:i:s', strtotime('+1 week'));
                }
                $this->articleDataHelper->setSpotlightArticle($id, $spotlightedUntil);
            }
            $isPublished = ($input['published'] ?? 0) === 1;
            $result = $this->dataHelper->set('Article', [
                'PublishedBy' => $isPublished ? ($this->application->getConnectedUser()->person->Id ?? null) : null,
                'LastUpdate'  => date('Y-m-d H:i:s')
            ], ['Id' => $id]);
            if ($result) {
                $_SESSION['success'] = ($this->t)('article.success.updated');
                $this->backup->save();
            } else {
                $_SESSION['error'] = ($this->t)('article.error.update_failed');
            }
            $this->redirect('/article/' . $id);
        } elseif (WebApp::getRequestMethod() === 'GET') {
            $articleWithAuthor = $this->articleDataHelper->getWithAuthor($id);
            if ($articleWithAuthor === false) {
                $this->respondToDenial(ArticleAccess::NotFound, $id);
                return;
            }
            $viewModel = new PublishViewModel(
                article: $articleWithAuthor,
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );
            $this->views->render('User/views/publish.latte', $viewModel->toArray());
        } else {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
        }
    }

    public function show(int $id): void
    {
        $article = $this->readArticle($id);
        if ($article === null) {
            return;
        }
        $connectedUser = $this->application->getConnectedUser();

        try {
            [$message, $messageType] = MessageService::get();

            // Survey and Order are read once: the same rows feed the view and the visibility rules
            $survey = $this->dataHelper->get('Survey', ['IdArticle' => $id], 'Id, ClosingDate, Visibility');
            $order = $this->dataHelper->get('Order', ['IdArticle' => $id], 'Id, ClosingDate, Visibility');

            $viewModel = new ArticleShowViewModel(
                id: $id,
                article: $article,
                groups: array_values(
                    $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name')
                ),
                hasSurvey: $survey ?: null,
                hasOrder: $order ?: null,
                userConnected: $connectedUser->person !== null,
                navItems: $this->getNavItems($connectedUser->person),
                publishedBy: $this->publisherName($article),
                canReadPool: $this->authorizationService->canReadSurveyResults(
                    $this->toClosingVisibility($survey),
                    $article,
                    $connectedUser
                ),
                canReadOrder: $this->authorizationService->canReadOrderResults(
                    $this->toClosingVisibility($order),
                    $article,
                    $connectedUser
                ),
                carouselItems: $this->carouselDataHelper->getCarouselItems($id),
                countOfMessages: $this->messageDataHelper->countUserMessagesForArticle($id),
                isCreator: $this->authorizationService->isOwner($article, $connectedUser),
                message: $message,
                messageType: $messageType,
                layoutParams: $this->getAllParams([]),
                btnParent: $this->resolveParentUrl('/articles'),
            );

            $this->views->render('Article/views/article_show.latte', $viewModel->toArray());
        } catch (QueryException $e) {
            $this->errors->badRequest($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function showArticleChat(int $articleId): void
    {
        $article = $this->readArticle($articleId);
        if ($article === null) {
            return;
        }

        $connectedUser = $this->application->getConnectedUser();
        $person = $connectedUser->person;
        if ($person === null) {
            // Public article, but the chat requires a signed-in member:
            // same flow as an anonymous visitor on a protected article.
            $this->handleAnonymousAccess();
            return;
        }

        $userImg = WebApp::getUserImg($person, new GravatarHandler());
        $lastLogId = isset($_SESSION['last_log_id']) && is_numeric($_SESSION['last_log_id'])
            ? (int)$_SESSION['last_log_id']
            : 0;

        $viewModel = new ChatViewModel(
            article: $article,
            event: null,
            group: null,
            messages: array_values($this->messageDataHelper->getArticleMessages($articleId)),
            person: $person,
            navItems: $this->getNavItems($person),
            newMessages: $this->messageDataHelper->hasNewMessages($person->Id, $lastLogId),
            btnParent: "/article/{$articleId}",
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
                'userImg' => $userImg,
            ]),
        );
        $this->views->render('Common/views/chat.latte', $viewModel->toArray());
    }

    public function showArticleCrosstab(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $period = Period::from($this->flight->request()->query->period ?? 'month');
        $dateRange = $period->dateRange();
        $crosstabData = $this->articleCrosstabDataHelper->getItems($dateRange);

        $viewModel = new CrosstabViewModel(
            crosstabData: $crosstabData,
            period: $period->value,
            dateRange: $dateRange,
            availablePeriods: Period::gets($this->languagesDataHelper),
            title: ($this->t)('article.crosstab.title'),
            totalLabels: [($this->t)('article.crosstab.total_articles'), ''],
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(1),
            ]),
        );
        $this->views->render('Article/views/crosstab.latte', $viewModel->toArray());
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

        $viewModel = new TopItemsByPeriodViewModel(
            title: ($this->t)('visitor_insights.top_articles.title'),
            period: $period->value,
            periodFrom: $period->getStart()->format('Y-m-d H:i:s'),
            periodTo: $period->getEnd()->format('Y-m-d H:i:s'),
            topPages: array_values($topPages),
            translations: TranslationManager::getCreationTimeModalTranslations($this->languagesDataHelper),
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );
        $this->views->render('Article/views/topArticles.latte', $viewModel->toArray());
    }

    public function update(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $article = $this->unwrapOrRespond(
            $this->loadAuthorized($id, $this->authorizationService->canEdit(...)),
            $id
        );
        if ($article === null) {
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
        $title = $input['title'] ?? '';
        $content = $input['content'] ?? '';
        if (empty($title) || empty($content)) {
            $_SESSION['error'] = ($this->t)('article.error.title_content_required');
            $this->redirect('/article/' . $id);
            return;
        }

        $publishedBy = null;
        if (($input['published'] ?? 0) === 1) {
            $publishedBy = $this->application->getConnectedUser()->person->Id
                ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
        }

        $result = $this->dataHelper->set(
            'Article',
            [
                'Title'          => $title,
                'Content'        => $content,
                'PublishedBy'    => $publishedBy,
                'IdGroup'        => $input['idGroup'] ?? null,
                'OnlyForMembers' => $input['membersOnly'] ?? 0,
                'LastUpdate'     => date('Y-m-d H:i:s')
            ],
            ['Id' => $id]
        );
        if ($result) {
            $_SESSION['success'] = ($this->t)('article.success.updated');
            $this->backup->save();
        } else {
            $_SESSION['error'] = ($this->t)('article.error.update_failed');
        }
        $this->redirect('/article/' . $id);
    }

    #region Authorization pipeline
    /**
     * Access gate for the read-only article pages (article view, article chat).
     *
     * HTTP method -> latest version -> read policy, as one Result chain.
     * Any failure has already been answered (error page or redirect) when null is returned:
     * the caller must simply stop.
     */
    private function readArticle(int $id): ?ArticleRow
    {
        $user = $this->application->getConnectedUser();

        try {
            return $this->unwrapOrRespond(
                $this->requireMethod(['GET', 'HEAD'])
                    ->andThen(fn() => $this->loadLatest($id))
                    ->andThen(fn(ArticleRow $article) => $this->authorizationService->authorizeRead($article, $user)),
                $id,
                readAccess: true,
            );
        } catch (QueryException $e) {
            $this->errors->badRequest($e->getMessage(), $e->getFile(), $e->getLine());
            return null;
        }
    }

    /**
     * @param list<string> $allowedMethods
     * @return Result<null>
     */
    private function requireMethod(array $allowedMethods): Result
    {
        return in_array(WebApp::getRequestMethod(), $allowedMethods, true)
            ? Result::ok(null)
            : Result::fail(ArticleAccess::MethodNotAllowed);
    }

    /**
     * @return Result<ArticleRow>
     */
    private function loadLatest(int $id): Result
    {
        $article = $this->articleDataHelper->getLatestArticle([$id]);

        return $article === null
            ? Result::fail(ArticleAccess::NotFound)
            : Result::ok($article);
    }

    /**
     * Loads the latest version of an article and checks one permission on it.
     *
     * @param callable(ArticleRow, \app\helpers\ConnectedUser): bool $isAllowed
     * @return Result<ArticleRow>
     */
    private function loadAuthorized(int $id, callable $isAllowed): Result
    {
        $user = $this->application->getConnectedUser();

        return $this->loadLatest($id)->andThen(
            static fn(ArticleRow $article): Result => $isAllowed($article, $user)
                ? Result::ok($article)
                : Result::fail(ArticleAccess::Forbidden)
        );
    }

    /**
     * Single exit point of the pipelines: returns the success value, or emits the
     * HTTP response matching the denial and returns null.
     *
     * @template T
     * @param Result<T> $result
     * @param bool $readAccess read denials keep their historical raiseForbidden() arguments
     * @return T|null
     */
    private function unwrapOrRespond(Result $result, int $articleId, bool $readAccess = false): mixed
    {
        return $result->match(
            onOk: static fn(mixed $value): mixed => $value,
            onFail: function (ArticleAccess $reason) use ($articleId, $readAccess): mixed {
                $this->respondToDenial($reason, $articleId, $readAccess);
                return null;
            },
        );
    }

    /**
     * The only place that turns an ArticleAccess denial into an HTTP response.
     */
    private function respondToDenial(ArticleAccess $reason, int $articleId, bool $readAccess = false): void
    {
        // switch rather than match: the raise*() methods return void.
        // Keep one case per ArticleAccess case, a missing one would leave the request unanswered.
        switch ($reason) {
            case ArticleAccess::MethodNotAllowed:
                $this->errors->methodNotAllowed(__FILE__, __LINE__);
                break;
            case ArticleAccess::NotFound:
                $this->errors->badRequest(
                    str_replace('{id}', (string)$articleId, ($this->t)('article.error.not_found')),
                    __FILE__,
                    __LINE__
                );
                break;
            case ArticleAccess::LoginRequired:
                $this->handleAnonymousAccess();
                break;
            case ArticleAccess::Forbidden:
                if ($readAccess) {
                    $this->errors->forbidden(__FILE__, __LINE__, 10000, false);
                } else {
                    $this->errors->forbidden(__FILE__, __LINE__);
                }
                break;
        }
    }
    #endregion

    #region Private functions
    private function publisherName(ArticleRow $article): string
    {
        if ($article->PublishedBy === null || $article->PublishedBy === $article->CreatedBy) {
            return '';
        }

        return $this->personDataHelper->getPublisher($article->PublishedBy) ?? '';
    }

    private function toClosingVisibility(object|false $row): ?ClosingVisibilityRow
    {
        if ($row === false) {
            return null;
        }
        /** @var ClosingVisibilityRowShape $row */
        return ClosingVisibilityRow::fromStdClass($row);
    }

    /**
     * Anonymous visitor on a protected page: see AnonymousAccessService.
     */
    private function handleAnonymousAccess(): void
    {
        $requestUri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        $target = $this->anonymousAccessService->resolve($requestUri);

        $this->redirect($target->url, $target->status, $target->message);
    }

    /**
     * Resolves the target of the "parent" navbar button from the ?parent= query parameter.
     *
     * - parameter absent or invalid -> $default (current behavior)
     * - parent=false                -> null (button hidden)
     * - parent=/some/internal/path  -> that path
     *
     * Only local absolute paths are accepted to prevent open redirects
     * and "javascript:" / external URLs.
     */
    private function resolveParentUrl(string $default): ?string
    {
        $raw = $this->flight->request()->query->getData()['parent'] ?? null;

        if ($raw === 'false') {
            return null;
        }

        $filtered = WebApp::filterInput(
            ['parent' => FilterInputRule::LocalPath->value],
            ['parent' => $raw]
        );

        $parent = $filtered['parent'] ?? $default;

        return is_string($parent) ? $parent : $default;
    }
    #endregion
}
