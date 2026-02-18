<?php

declare(strict_types=1);

namespace app\enums;

enum TimeOfDay: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
}
