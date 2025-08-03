<?php

namespace app\enums;

enum InputPattern: string
{
    case Name = '/^[a-zA-Z\s\-]+$/';
    case Nickname = '/^[\w\s\-]{1,30}$/';
    case Title = '/^[\w\s\-]{1,100}$/';
    case GroupName = '/^[\w\s\-]{1,50}$/';
    case Content = '/^.{0,500}$/';
    case Email = '/^[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$/i';
    case DateTime = '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/';
}

