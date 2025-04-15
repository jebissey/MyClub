<?php

namespace app\helpers;

enum EventAudience: string
{
    case ForClubMembersOnly = 'ClubMembersOnly';
    case ForGuest = 'Guest';
    case ForAll = 'All';
}
