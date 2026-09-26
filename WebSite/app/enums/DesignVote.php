<?php

declare(strict_types=1);

namespace app\enums;

enum DesignVote: string
{
    case Up = 'voteUp';
    case Down = 'voteDown';
    case Neutral = 'voteNeutral';
}
