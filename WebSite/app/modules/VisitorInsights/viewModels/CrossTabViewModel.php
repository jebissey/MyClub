<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class CrossTabViewModel extends LayoutViewModel
{
    /**
     * @param array<string, array{visits: array<string, int>, total: int}> $uris
     * @param list<object{Email: string, FirstName: string, LastName: string}> $persons
     * @param array<string, int> $columnTotals
     * @param list<object{Id: int|string, Name: string}> $groups
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $title,
        public string $period,
        public array $uris,
        public array $persons,
        public array $columnTotals,
        public int|float $grandTotal,
        public array $groups,
        public string $uriFilter,
        public string $emailFilter,
        public string $groupFilter,
        public array $i18n,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/admin',
            btnPlus: false,
        );
    }
}
