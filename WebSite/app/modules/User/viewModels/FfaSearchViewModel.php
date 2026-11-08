<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class FfaSearchViewModel extends LayoutViewModel
{
    /**
     * @param array<int|string, array<string, string>|string>|null $results Null when the FFA site could not be queried
     * @param list<object> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $question,
        public ?array $results,
        public array $navItems,
        public string $year,
        public string $club,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
