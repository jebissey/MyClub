<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-import-type FfaRankRow from \app\helpers\FFAScraper
 * @phpstan-import-type FfaResultRow from \app\helpers\FFAScraper
 */
final readonly class FfaSearchViewModel extends LayoutViewModel
{
    /**
     * @param FfaRankRow|null $rank Ranking line, null when nothing was found
     * @param list<FfaResultRow> $results Results lines (empty for a ranking search)
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $question,
        public ?array $rank,
        public array $results,
        public ?string $error,
        public array $navItems,
        public string $year,
        public string $club,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
