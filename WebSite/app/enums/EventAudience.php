<?php
declare(strict_types=1);

namespace app\enums;

enum EventAudience: string
{
    case ForClubMembersOnly = 'ClubMembersOnly';
    case ForGuest = 'Guest';
    case ForAll = 'All';
}
