<?php

namespace app\helpers;

class PasswordManager
{
    private const SECRET_KEY = "MyClubSecretKeyForPasswordSignature";
    private const ALGO = 'sha256';

    public static function signPassword(string $password): string
    {
        return hash_hmac(self::ALGO, $password, self::SECRET_KEY);
    }

    public static function verifyPassword(string $password, string $signedPassword): bool
    {
        return hash_hmac(self::ALGO, $password, self::SECRET_KEY) == $signedPassword;
    }
}
