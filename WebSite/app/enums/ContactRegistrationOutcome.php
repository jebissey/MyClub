<?php

declare(strict_types=1);

namespace app\enums;

enum ContactRegistrationOutcome: string
{
    case Registered = 'registered';
    case Unregistered = 'unregistered';
    case UnknownToken = 'unknown_token';
    case ExpiredToken = 'expired_token';
    case MemberAccount = 'member_account';
    case AlreadyRegistered = 'already_registered';

    public function isError(): bool
    {
        return $this !== self::Registered && $this !== self::Unregistered;
    }

    public function translationKey(): string
    {
        return 'event.register.error.' . $this->value;
    }
}
