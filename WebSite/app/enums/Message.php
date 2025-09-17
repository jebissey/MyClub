<?php
declare(strict_types=1);

namespace app\enums;

enum Message: string
{
    case UnknownUser = 'UnknownUser';
    case PasswordReset = 'PasswordReset';
    case AutoSignInSucceeded = 'AutoSignInSucceeded';
    case SignInSucceeded = 'SignInSucceeded';
    case SignOutSucceeded = 'SignOutSucceeded';
}
