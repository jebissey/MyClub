<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class NavbarColors
{
    public function __construct(
        public string $bgColor,
        public string $inkColor,
        public string $iconColor,
    ) {
    }
}
