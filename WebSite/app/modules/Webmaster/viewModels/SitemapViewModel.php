<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class SitemapViewModel extends LayoutViewModel
{
    /**
     * @param list<array{loc: string, lastmod: string}> $urls
     */
    public function __construct(
        public array $urls,
    ) {
        parent::__construct(
            ...self::baseArgsFrom([]),
            btnHistoryBack: true,
            btnParent: null,
            btnPlus: false,
        );
    }
}
