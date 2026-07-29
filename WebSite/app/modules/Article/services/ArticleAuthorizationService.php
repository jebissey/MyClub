<?php

declare(strict_types=1);

namespace app\modules\Article\services;

use app\helpers\ConnectedUser;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\valueObjects\ArticleAccessRow;
use app\valueObjects\ArticleOwnershipRow;

class ArticleAuthorizationService
{
    public function __construct(
        private DataHelper $dataHelper,
        private AuthorizationDataHelper $authorizationDataHelper
    ) {
    }

    public function canDelete(int $articleId, ConnectedUser $user): bool
    {
        return $this->canEdit($articleId, $user);
    }

    public function canEdit(int $articleId, ConnectedUser $user): bool
    {
        if ($user->person === null) {
            return false;
        }
        $row = $this->dataHelper->get('Article', ['Id' => $articleId], 'CreatedBy');
        if (!$row) {
            return false;
        }
        /** @var object{CreatedBy: int|string} $row */
        $article = ArticleOwnershipRow::fromStdClass($row);
        return $article->CreatedBy === $user->person->Id;
    }

    public function canPublish(int $articleId, ConnectedUser $user): bool
    {
        return $this->canEdit($articleId, $user) || $user->isEditor();
    }

    public function canRead(int $articleId, ConnectedUser $user): bool
    {
        $row = $this->dataHelper->get('Article', ['Id' => $articleId], 'OnlyForMembers, IdGroup');
        if (!$row) {
            return false;
        }
        /** @var object{OnlyForMembers: bool|int|string, IdGroup?: int|string|null} $row */
        $article = ArticleAccessRow::fromStdClass($row);
        if (!$article->OnlyForMembers) {
            return true;
        }
        if ($user->person === null) {
            return false;
        }
        return $this->authorizationDataHelper->getArticle($articleId, $user) != false;
    }
}
