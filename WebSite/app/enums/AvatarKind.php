<?php

declare(strict_types=1);

namespace app\enums;

enum AvatarKind: string
{
    case Image = 'image';
    case Emoji = 'emoji';
    case Placeholder = 'placeholder';
}
