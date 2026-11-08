<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * A single navbar entry.
 */
final readonly class MenuItem
{
    public function __construct(
        public string $href,
        public string $label,
        public ?string $page = null,
        public string $icon = '',
        public ?string $iconHtml = null,
        public string $text = '',
        public bool $visible = true,
    ) {
    }
}
