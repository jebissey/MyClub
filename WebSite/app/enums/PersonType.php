<?php

declare(strict_types=1);

namespace app\enums;

enum PersonType: string
{
    case Contact = 'contact';
    case Member = 'member';
}
