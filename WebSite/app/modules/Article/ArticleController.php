<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\ArticleAccess;
use app\enums\FilterInputRule;
use app\exceptions\IntegrityException;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\GravatarHandler;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\CarouselDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\modules\Article\services\ArticleAlertService;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Article\viewModels\ArticleCarouselViewModel;
use app\modules\Article\viewModels\ArticleEditViewModel;
use app\modules\Article\viewModels\ArticleShowViewModel;
use app\modules\Article\viewModels\ChangeOwnerViewModel;
use app\modules\Article\viewModels\PublishViewModel;
use app\modules\Article\viewModels\RedactorHomeViewModel;
use app\modules\Common\services\AnonymousAccessService;
use app\modules\Common\services\ArticleService;
use app\modules\Common\services\EmailService;
use app\modules\Common\services\MessageService;
use app\modules\Common\valueObjects\ClosingVisibilityRow;
use app\modules\Common\viewModels\ChatViewModel;

/**
 * @phpstan-import-type ClosingVisibilityRowShape from ClosingVisibilityRow
 */
final class ArticleController extends ArticleAccessController
{
    public function __construct(
        Application $application,
        ArticleDataHelper $articleDataHelper,
        ArticleAuthorizationService $authorizationService,
        AnonymousAccessService $anonymousAccessService,
        private readonly PersonDataHelper $personDataHelper,
        private readonly Backup $backup,
        private readonly MessageDataHelper $messageDataHelper,
        private readonly EmailService $emailService,
        private readonly ArticleService $articleService,
        private readonly CarouselDataHelper $carouselDataHelper,
        private readonly ArticleAlertService $articleAlertService,
    ) {
        parent::__construct(
            $application,
            $articleDataHelper,
            $authorizationService,
            $anonymousAccessService,
        );
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

        $userId = $this->application->getConnectedUser()->person->id
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
            navItems: $this->getNavItems($connectedUser->person->email),
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

        $alert = $this->articleAlertService->prepare($idArticle, $articleCreator->Email, $this->t);

        if ($this->emailService->send($alert->message)) {
            MessageService::set(($this->t)('article.success.email_sent') . ' (' . $alert->recipientCount . ')');
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
                'PublishedBy' => $isPublished ? ($this->application->getConnectedUser()->person->id ?? null) : null,
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
                navItems: $this->getNavItems($connectedUser->person?->email),
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

        $userImg = WebApp::getUserImg(
            $person->useGravatar,
            $person->email,
            $person->avatar,
            new GravatarHandler()
        );
        $lastLogId = isset($_SESSION['last_log_id']) && is_numeric($_SESSION['last_log_id'])
            ? (int)$_SESSION['last_log_id']
            : 0;

        $viewModel = new ChatViewModel(
            article: $article,
            event: null,
            group: null,
            messages: array_values($this->messageDataHelper->getArticleMessages($articleId)),
            person: $person,
            navItems: $this->getNavItems($person->email),
            newMessages: $this->messageDataHelper->hasNewMessages($person->id, $lastLogId),
            btnParent: "/article/{$articleId}",
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
                'userImg' => $userImg,
            ]),
        );
        $this->views->render('Common/views/chat.latte', $viewModel->toArray());
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
            $publishedBy = $this->application->getConnectedUser()->person->id
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

    #region Private functions
    private function publisherName(ArticleRow $article): string
    {
        if ($article->publishedBy === null || $article->publishedBy === $article->createdBy) {
            return '';
        }

        return $this->personDataHelper->getPublisher($article->publishedBy) ?? '';
    }

    private function toClosingVisibility(object|false $row): ?ClosingVisibilityRow
    {
        if ($row === false) {
            return null;
        }
        /** @var ClosingVisibilityRowShape $row */
        return ClosingVisibilityRow::fromStdClass($row);
    }
    #endregion
}
