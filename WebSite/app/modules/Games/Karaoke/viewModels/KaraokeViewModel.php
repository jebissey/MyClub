<?php

declare(strict_types=1);

namespace app\modules\Games\Karaoke\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class KaraokeViewModel extends LayoutViewModel
{
    /**
     * @param list<object> $navItems
     * @param array<string, string> $metadata
     * @param list<array{time: float, text: string, words: list<array{time: float, text: string}>}> $lines
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $navItems,
        public array $metadata,
        public array $lines,
        public string $audioFile,
        public string $sessionId,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
