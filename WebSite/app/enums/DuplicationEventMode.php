<?php

declare(strict_types=1);

namespace app\enums;

enum DuplicationEventMode: string
{
    case Today = 'today';
    case NextWeek = 'nextWeek';
}
