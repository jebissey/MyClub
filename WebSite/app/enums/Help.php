<?php

namespace app\enums;

enum Help: string
{
    case Admin = 'admin';
    case Designer = 'designer';
    case EventManager = 'eventManager';
    case Home = 'home';
    case PersonManager = 'personManager';
    case User = 'user';
    case VisitorInsights = 'visitorInsights';
    case Webmaster = 'webmaster';
}
