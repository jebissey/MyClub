<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MediaUsesInMessagesViewModel extends LayoutViewModel
{
    /**
     * @param list<object{Id: int, Title: string, StartTime: string}> $events
     * @param list<object{Id: int, Title: string}> $articles
     * @param list<object{Id: int, Title: string}> $groups
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
            btnHistoryBack: true,
        );
    }
}
