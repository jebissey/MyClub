<?php

namespace app\enums;

enum InputPattern: string
{
    case Content = '/^.{1,500}$/s';
    case DateTime = '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/';
    case Email = '/^[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$/i';
    case PersonName = '/^(?![ \-])[A-Za-zÀ-ÖØ-öø-ÿ\- ]{1,100}(?<![ \-])$/';
    case Uri = '/^[a-zA-Z0-9\-._~\/]{1,255}$/';
    case Token = '/^[a-fA-F0-9]{32}$/';
}

