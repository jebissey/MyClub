<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Article\valueObjects\ArticleRow;

final readonly class ArticleShowViewModel extends LayoutViewModel
{
    /**
     * @param ArticleRow $article
     * @param list<stdClass> $groups
     * @param object|null $hasSurvey
     * @param object|null $hasOrder
     * @param list<stdClass> $carouselItems
     * @param list<object> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public int $id,
        public ArticleRow $article,
        public array $groups,
        public ?object $hasSurvey,
        public ?object $hasOrder,
        public bool $userConnected,
        public array $navItems,
        public string $publishedBy,
        public bool $canReadPool,
        public bool $canReadOrder,
        public array $carouselItems,
        public int $countOfMessages,
        public bool $isCreator,
        public ?string $message,
        public ?string $messageType,
        array $layoutParams
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/articles',
        );
    }
}
