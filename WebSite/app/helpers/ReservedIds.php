<?php

declare(strict_types=1);

namespace app\helpers;

/**
 * Records of the template database that the application relies on and that must never be altered.
 */
final class ReservedIds
{
    /** The first member (webmaster@myclub.foo): it can never be inactivated. */
    public const WEBMASTER_PERSON = 1;

    /** The Webmaster group: it can be neither edited nor deleted. */
    public const WEBMASTER_GROUP = 1;

    /** The Webmaster authorization: it can never be given to, or removed from, a group. */
    public const WEBMASTER_AUTHORIZATION = 1;

    private function __construct()
    {
    }
}
