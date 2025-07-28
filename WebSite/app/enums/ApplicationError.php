<?php

namespace app\enums;

enum ApplicationError: int
{
    case Ok = 200;
    case BadRequest = 400;
    case NotAllowed = 403;
    case PageNotFound = 404;
    case InvalidSetting = 444;
    case InvalidRequestMethod = 470;
    case InvalidParameter = 471;
    case RecordNotFound = 499;
    case Error = 500;
}
