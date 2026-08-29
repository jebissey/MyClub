<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\ArticleRow;

final readonly class ArticleEditViewModel extends LayoutViewModel
{
    /**
     * @param ArticleRow $article
     * @param list<stdClass> $groups
     * @param object|null $hasSurvey
     * @param object|null $hasOrder
     * @param list<object> $navItems
     * @param list<stdClass> $carouselItems
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public int $id,
        public ArticleRow $article,
        public array $groups,
        public ?object $hasSurvey,
        public ?object $hasOrder,
        public object|bool $userConnected,
        public array $navItems,
        public ?string $publishedBy,
        public array $carouselItems,
        public array $i18n,
        array $layoutParams
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/articles',
        );
    }
}
