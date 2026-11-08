<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Article\valueObjects\OrderWithCreatorRow;

final readonly class OrderResultsViewModel extends ClosableFormResultsViewModel
{
    /**
     * @param list<string> $options
     * @param array<string, float|int> $results
     * @param list<array{name: string, answers: array<string, float|int>}> $participants
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public OrderWithCreatorRow $order,
        public array $options,
        public array $results,
        public array $participants,
        int $articleId,
        array $layoutParams,
    ) {
        parent::__construct($articleId, $layoutParams);
    }
}
