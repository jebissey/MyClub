<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\ArticleAccess;
use app\enums\FilterInputRule;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Article\valueObjects\Result;
use app\modules\Common\TableController;
use app\modules\Common\services\AnonymousAccessService;

/**
 * Shared access pipeline for every controller working on a single article.
 *
 * Each step returns a Result; the single exit point (unwrapOrRespond) turns a denial
 * into the matching HTTP response.
 */
abstract class ArticleAccessController extends TableController
{
    public function __construct(
        Application $application,
        protected readonly ArticleDataHelper $articleDataHelper,
        protected readonly ArticleAuthorizationService $authorizationService,
        private readonly AnonymousAccessService $anonymousAccessService,
    ) {
        parent::__construct($application);
    }

    /**
     * Access gate for the read-only article pages (article view, article chat).
     *
     * HTTP method -> latest version -> read policy, as one Result chain.
     * Any failure has already been answered (error page or redirect) when null is returned:
     * the caller must simply stop.
     */
    protected function readArticle(int $id): ?ArticleRow
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
    protected function requireMethod(array $allowedMethods): Result
    {
        return in_array(WebApp::getRequestMethod(), $allowedMethods, true)
            ? Result::ok(null)
            : Result::fail(ArticleAccess::MethodNotAllowed);
    }

    /**
     * @return Result<ArticleRow>
     */
    protected function loadLatest(int $id): Result
    {
        $article = $this->articleDataHelper->getLatestArticle([$id]);

        return $article === null
            ? Result::fail(ArticleAccess::NotFound)
            : Result::ok($article);
    }

    /**
     * Loads the latest version of an article and checks one permission on it.
     *
     * @param callable(ArticleRow, ConnectedUser): bool $isAllowed
     * @return Result<ArticleRow>
     */
    protected function loadAuthorized(int $id, callable $isAllowed): Result
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
     * @param bool $readAccess read denials keep their historical forbidden() arguments
     * @return T|null
     */
    protected function unwrapOrRespond(Result $result, int $articleId, bool $readAccess = false): mixed
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
    protected function respondToDenial(ArticleAccess $reason, int $articleId, bool $readAccess = false): void
    {
        // switch rather than match: the error methods return void.
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

    /**
     * Anonymous visitor on a protected page: see AnonymousAccessService.
     */
    protected function handleAnonymousAccess(): void
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
    protected function resolveParentUrl(string $default): ?string
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
}
