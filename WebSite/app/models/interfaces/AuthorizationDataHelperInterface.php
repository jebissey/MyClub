<?php

declare(strict_types=1);

namespace app\models\interfaces;

use app\helpers\ConnectedUser;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;

interface AuthorizationDataHelperInterface
{
    /**
     * @return list<string>
     */
    public function getsFor(int $memberId): array;

    public function getArticle(int $id, ConnectedUser $connectedUser): ArticleAuthorizationRow|false;
}
