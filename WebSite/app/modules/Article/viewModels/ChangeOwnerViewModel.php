<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\valueObjects\ArticleRow;
use app\modules\Common\valueObjects\RedactorRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class ChangeOwnerViewModel extends LayoutViewModel
{
    /**
     * @param list<RedactorRow> $redactors
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public ArticleRow $article,
        public array $redactors,
        array $layoutParams = []
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
