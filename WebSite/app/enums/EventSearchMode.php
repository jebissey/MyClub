<?php
declare(strict_types=1);

namespace app\enums;

enum EventSearchMode: string
{
    case Next = 'next';
    case Past = 'past';
}
