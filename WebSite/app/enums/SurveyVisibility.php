<?php
declare(strict_types=1);

namespace app\enums;

enum SurveyVisibility: string
{
    case All = 'all';
    case AllAfterClosing = 'allAfterClosing';
    case Redactor = 'redactor';
    case Voters = 'voters';
    case votersAfterClosing = 'votersAfterClosing';
}
