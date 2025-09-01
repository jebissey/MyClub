<?php

namespace app\enums;

enum ApplicationError: int
{
    case Ok = 200;
    case BadRequest = 400;
    case Unauthorized = 401;
    case Forbidden = 403;
    case PageNotFound = 404;
    case MethodNotAllowed = 405;
    case InvalidSetting = 444;
    case Error = 500;
    case ServiceUnavailable = 503;
}
