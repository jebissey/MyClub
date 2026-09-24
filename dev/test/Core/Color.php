<?php

declare(strict_types=1);

namespace test\Core;

enum Color: string
{
    case Reset   = "\033[0m";
    case Red     = "\033[31m";
    case Green   = "\033[32m";
    case Yellow  = "\033[33m";
    case Magenta = "\033[35m";
    case White   = "\033[37m";
}
