<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Article\valueObjects\ClosableFormRow;
use app\modules\Common\valueObjects\ArticleRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class ClosableFormAddViewModel extends LayoutViewModel
{
    /**
     * @param array<string, string> $visibilityOptions Visibility value => translated label.
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public ArticleRow $article,
        public ?ClosableFormRow $form,
        public array $visibilityOptions,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: null,
            btnPlus: false,
        );
    }
}
