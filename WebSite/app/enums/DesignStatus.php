<?php

declare(strict_types=1);

namespace app\enums;

enum DesignStatus: string
{
    case UnderReview = 'UnderReview';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
}
