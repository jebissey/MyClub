<?php

namespace app\enums;

enum Authorization: string
{
    case Editor = 'Editor';
    case EventManager = 'EventManager';
    case PersonManager = 'PersonManager';
    case Redactor = 'Redactor';
    case Webmaster = 'Webmaster';
}
