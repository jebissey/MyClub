<?php

declare(strict_types=1);

namespace app\modules\Article\services;

use DateTimeImmutable;
use app\enums\ArticleAccess;
use app\helpers\ConnectedUser;
use app\models\AuthorizationDataHelper;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Article\valueObjects\Result;
use app\modules\Common\valueObjects\ClosingVisibilityRow;

/**
 * Single source of truth for article authorization.
 *
 * The decisions are computed from rows that are already loaded: this class never
 * reads an article itself. The only data it asks for are the groups of the user
 * and the participation in a survey / an order.
 */
final readonly class ArticleAuthorizationService
{
    public function __construct(
        private AuthorizationDataHelper $authorizationDataHelper
    ) {
    }

    public function isOwner(ArticleRow|ArticleAuthorizationRow $article, ConnectedUser $user): bool
    {
        return $user->person !== null && $article->CreatedBy === $user->person->Id;
    }

    public function canEdit(ArticleRow $article, ConnectedUser $user): bool
    {
        return $this->isOwner($article, $user);
    }

    public function canDelete(ArticleRow $article, ConnectedUser $user): bool
    {
        return $this->canEdit($article, $user);
    }

    public function canPublish(ArticleRow $article, ConnectedUser $user): bool
    {
        return $this->canEdit($article, $user) || $user->isEditor();
    }

    public function canChangeOwner(ConnectedUser $user): bool
    {
        return $user->isEditor();
    }

    public function canRead(ArticleAuthorizationRow $article, ConnectedUser $user): bool
    {
        return $this->readDenial($article, $user) === null;
    }

    /**
     * @return Result<ArticleRow>
     */
    public function authorizeRead(ArticleRow $article, ConnectedUser $user): Result
    {
        $denial = $this->readDenial(ArticleAuthorizationRow::fromArticleRow($article), $user);

        return $denial === null ? Result::ok($article) : Result::fail($denial);
    }

    public function canReadSurveyResults(
        ?ClosingVisibilityRow $survey,
        ArticleRow|ArticleAuthorizationRow $article,
        ConnectedUser $user
    ): bool {
        $person = $user->person;
        if ($survey === null || $person === null) {
            return false;
        }
        $personId = $person->Id;

        return $this->canReadClosableResults(
            $survey,
            $this->isOwner($article, $user),
            fn(): bool => $this->authorizationDataHelper->hasRepliedToSurvey($survey->Id, $personId),
            participantsOnly: 'voters',
            participantsOnlyAfterClosing: 'votersAfterClosing',
        );
    }

    public function canReadOrderResults(
        ?ClosingVisibilityRow $order,
        ArticleRow|ArticleAuthorizationRow $article,
        ConnectedUser $user
    ): bool {
        $person = $user->person;
        if ($order === null || $person === null) {
            return false;
        }
        $personId = $person->Id;

        return $this->canReadClosableResults(
            $order,
            $this->isOwner($article, $user),
            fn(): bool => $this->authorizationDataHelper->hasRepliedToOrder($order->Id, $personId),
            participantsOnly: 'orderers',
            participantsOnlyAfterClosing: 'orderersAfterClosing',
        );
    }

    #region Private functions
    /**
     * Why the user cannot read the article, or null when reading is allowed.
     *
     * - author and editors can always read, drafts included
     * - a draft is invisible to everybody else
     * - a published article without group and not reserved to members is public
     * - anything else requires a signed-in user, and the right group when there is one
     */
    private function readDenial(ArticleAuthorizationRow $article, ConnectedUser $user): ?ArticleAccess
    {
        $person = $user->person;

        if ($person !== null && ($article->CreatedBy === $person->Id || $user->isEditor())) {
            return null;
        }
        if ($article->PublishedBy === null) {
            return $person === null ? ArticleAccess::LoginRequired : ArticleAccess::Forbidden;
        }
        if (!$article->OnlyForMembers && $article->IdGroup === null) {
            return null;
        }
        if ($person === null) {
            return ArticleAccess::LoginRequired;
        }
        if ($article->IdGroup === null) {
            return null;
        }

        $userGroups = $this->authorizationDataHelper->getUserGroups($person->Email);

        return in_array((int) $article->IdGroup, $userGroups, true) ? null : ArticleAccess::Forbidden;
    }

    /**
     * Visibility rules shared by surveys and orders.
     *
     * @param callable(): bool $hasParticipated evaluated lazily, only when the visibility needs it
     */
    private function canReadClosableResults(
        ClosingVisibilityRow $item,
        bool $isOwner,
        callable $hasParticipated,
        string $participantsOnly,
        string $participantsOnlyAfterClosing,
    ): bool {
        if ($isOwner || $item->Visibility === 'all') {
            return true;
        }

        $isClosed = $item->ClosingDate < (new DateTimeImmutable())->format('Y-m-d');
        if ($item->Visibility === 'allAfterClosing') {
            return $isClosed;
        }
        if ($item->Visibility === $participantsOnly) {
            return $hasParticipated();
        }
        if ($item->Visibility === $participantsOnlyAfterClosing) {
            return $isClosed && $hasParticipated();
        }

        return false;
    }
    #endregion
}
