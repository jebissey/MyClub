<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use stdClass;

final readonly class DbBrowserEditViewModel extends LayoutViewModel
{
    /**
     * @param list<string> $columns
     * @param array<string, array{type: string, notnull: int, dflt_value: mixed, pk: int}> $columnTypes
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $table,
        public array $columns,
        public stdClass $record,
        public string $primaryKey,
        public array $columnTypes,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
        );
    }
}
