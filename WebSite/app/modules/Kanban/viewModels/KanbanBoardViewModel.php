<?php

declare(strict_types=1);

namespace app\modules\Kanban\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class KanbanBoardViewModel extends LayoutViewModel
{
    /**
     * @param int $personId
     * @param list<object{Id: int, Title: string, Detail: string}> $projects
     * @param list<array{icon: string, label: string}> $columns
     * @param int|null $selectedProjectId
     * @param list<object{Id: int, Label: string, Detail: string, Color: string}> $cardTypes
     * @param array<string, mixed> $filters
     * @param bool|null $isOwner
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public int $personId,
        public array $projects,
        public array $columns,
        public ?int $selectedProjectId,
        public array $cardTypes,
        public array $filters,
        public ?bool $isOwner,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/designer',
        );
    }
}
