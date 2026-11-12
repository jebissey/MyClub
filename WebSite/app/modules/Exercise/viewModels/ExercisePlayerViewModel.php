<?php

declare(strict_types=1);

namespace app\modules\Exercise\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class ExercisePlayerViewModel extends LayoutViewModel
{
    /**
     * @param array<mixed> $exercises Decoded content of the exercise
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public int $articleId,
        public string $title,
        public array $exercises,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: null,
            btnPlus: false,
        );
    }
}
