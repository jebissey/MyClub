<?php
declare(strict_types=1);

namespace app\enums;

enum KanbanStatusChange: string
{
    case Created = 'Created';

    case MovedFromBacklogToSelected = 'MovedFromBacklogToSelected';
    case MovedFromBacklogToInProgress = 'MovedFromBacklogToInProgress';
    case MovedFromBacklogToDone = 'MovedFromBacklogToDone';

    case MovedFromSelectedToBacklog = 'MovedFromSelectedToBacklog';
    case MovedFromSelectedToInProgress = 'MovedFromSelectedToInProgress';
    case MovedFromSelectedToDone = 'MovedFromSelectedToDone';

    case MovedFromInProgressToBacklog = 'MovedFromInProgressToBacklog';
    case MovedFromInProgressToSelected = 'MovedFromInProgressToSelected';
    case MovedFromInProgressToDone = 'MovedFromInProgressToDone';

    case MovedFromDoneToBacklog = 'MovedFromDoneToBacklog';
    case MovedFromDoneToSelected = 'MovedFromDoneToSelected';
    case MovedFromDoneToInProgress = 'MovedFromDoneToInProgress';
}
