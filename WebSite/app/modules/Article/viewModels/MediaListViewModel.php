<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MediaListViewModel extends LayoutViewModel
{
    /**
     * @param list<array{
     *     name: string,
     *     path: string,
     *     url: string,
     *     urlRedactor: string,
     *     size: int,
     *     date: string,
     *     month: string,
     *     inGalery: bool,
     *     inArticle: bool,
     *     shared: bool,
     *     inMessage: bool
     * }> $files
     * @param list<string> $years
     * @param list<string> $months
     * @param list<string> $fileExtensions
     * @param list<object> $groups
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $files,
        public int $filteredCount,
        public int $totalCount,
        public array $years,
        public int $currentYear,
        public array $months,
        public string $currentMonth,
        public array $fileExtensions,
        public string $currentFileExtension,
        public string $search,
        public bool $unusedOnly,
        public string $baseUrl,
        public array $groups,
        public array $i18n,
        array $layoutParams = []
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
