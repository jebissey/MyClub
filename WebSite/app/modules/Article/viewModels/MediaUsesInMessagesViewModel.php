<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MediaUsesInMessagesViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $events
     * @param list<mixed> $articles
     * @param list<mixed> $groups
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $path,
        public array $events,
        public array $articles,
        public array $groups,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
        );
    }
}
