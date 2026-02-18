<?php

declare(strict_types=1);

namespace app\enums;

enum Period: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';

    case Today = 'today';
    case Signin = 'signin';
    case Signout = 'signout';
}
