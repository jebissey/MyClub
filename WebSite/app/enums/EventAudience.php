<?php

namespace app\enums;

enum EventAudience: string
{
    case ForClubMembersOnly = 'ClubMembersOnly';
    case ForGuest = 'Guest';
    case ForAll = 'All';
}
