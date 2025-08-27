<?php

namespace app\enums;

enum Authorization: string
{
    case Editor = 'Editor';
    case EventDesigner = 'EventDesigner';
    case EventManager = 'EventManager';
    case HomeDesigner = 'HomeDesigner';
    case NavbarDesigner = 'NavbarDesigner';
    case PersonManager = 'PersonManager';
    case Redactor = 'Redactor';
    case VisitorInsights = 'VisitorInsights';
    case Webmaster = 'Webmaster';
}
