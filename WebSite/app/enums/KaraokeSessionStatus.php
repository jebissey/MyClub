<?php

declare(strict_types=1);

namespace app\enums;

enum KaraokeSessionStatus: string
{
    case Waiting = 'waiting';
    case Countdown = 'countdown';
    case Idle = 'idle';
}
