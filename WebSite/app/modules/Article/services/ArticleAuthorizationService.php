<?php

declare(strict_types=1);

namespace app\modules\Article\services;

use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\modules\Common\services\AuthenticationService;


class ArticleAuthorizationService
{
    public function __construct(
        private DataHelper $dataHelper,
        private AuthenticationService $authService,
        private AuthorizationDataHelper $authorizationDataHelper
    ) {}

    public function canDelete(int $articleId, object $user): bool
    {
        return $this->canEdit($articleId, $user);
    }

    public function canEdit(int $articleId, object $user): bool
    {
        if ($user->person === null) {
            return false;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $articleId], 'CreatedBy');
        if (!$article) {
            return false;
        }
        if ($article->CreatedBy === $user->person->Id) {
            return true;
        }
        return false;
    }

    public function canPublish(int $articleId, object $user): bool
    {
        return $this->canEdit($articleId, $user) || $user->isEditor();
    }

    public function canRead(int $articleId, object $user): bool
    {
        $article = $this->dataHelper->get('Article', ['Id' => $articleId], 'OnlyForMembers, IdGroup');
        if (!$article) {
            return false;
        }
        if ($article->OnlyForMembers == 0) {
            return true;
        }
        if ($user->person === null) {
            $rememberMeResult = $this->authService->handleRememberMeLogin();
            if ($rememberMeResult && $rememberMeResult->isSuccess()) {
                return true;
            } else {
                return false;
            }
        }
        return $this->authorizationDataHelper->getArticle($articleId, $user) != false;
    }
}
