<?php
declare(strict_types=1);

namespace app\enums;

enum Karaoke: string
{
    case Register = 'register';
    case Heartbeat = 'heartbeat';
    case EventManager = 'eventManager';
    case GetStatus = 'getStatus';
    case StartCountdown = 'startCountdown';
    case Disconnect = 'disconnect';
}
