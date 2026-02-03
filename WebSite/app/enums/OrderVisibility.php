<?php

declare(strict_types=1);

namespace app\enums;

enum OrderVisibility: string
{
    case All = 'all';
    case AllAfterClosing = 'allAfterClosing';
    case Redactor = 'redactor';
    case Orderers = 'orderers';
    case OrderersAfterClosing = 'orderersAfterClosing';
}
