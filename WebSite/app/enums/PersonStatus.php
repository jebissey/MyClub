<?php

declare(strict_types=1);

namespace app\enums;

enum PersonStatus: string
{
    case Active = 'active';
    case Desactivated = 'desactivated';
}
