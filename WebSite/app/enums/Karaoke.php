<?php

declare(strict_types=1);

namespace app\enums;

enum Karaoke: string
{
    case Cleanup = 'cleanup';
    case Disconnect = 'disconnect';
    case GetStatus = 'getStatus';
    case Heartbeat = 'heartbeat';
    case Register = 'register';
    case StartCountdown = 'startCountdown';
}
