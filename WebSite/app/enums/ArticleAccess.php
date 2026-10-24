<?php

declare(strict_types=1);

namespace app\enums;

enum ArticleAccess
{
    case MethodNotAllowed;
    case NotFound;
    case LoginRequired;
    case Forbidden;
}
