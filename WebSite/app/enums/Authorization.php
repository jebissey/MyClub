<?php
declare(strict_types=1);

namespace app\enums;

enum Authorization: string
{
    case Editor = 'Editor';
    case EventDesigner = 'EventDesigner';
    case EventManager = 'EventManager';
    case HomeDesigner = 'HomeDesigner';
    case KanbanDesigner = 'KanbanDesigner';
    case NavbarDesigner = 'NavbarDesigner';
    case PersonManager = 'PersonManager';
    case Redactor = 'Redactor';
    case VisitorInsights = 'VisitorInsights';
    case Webmaster = 'Webmaster';
}
