<?php

declare(strict_types=1);

namespace app\enums;

enum Authorization: string
{
    case CommunicationManager = 'CommunicationManager';
    case Editor = 'Editor';
    case EventDesigner = 'EventDesigner';
    case EventManager = 'EventManager';
    case HomeDesigner = 'HomeDesigner';
    case KanbanDesigner = 'KanbanDesigner';
    case LoanDesigner = 'LoanDesigner';
    case LoanManager  = 'LoanManager';
    case MenuDesigner = 'MenuDesigner';
    case PersonManager = 'PersonManager';
    case Redactor = 'Redactor';
    case Translator = 'Translator';
    case VisitorInsights = 'VisitorInsights';
    case Webmaster = 'Webmaster';
}
