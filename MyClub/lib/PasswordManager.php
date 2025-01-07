<?php

class PasswordManager {
    private const HASH_ALGORITHM = PASSWORD_DEFAULT;
    private const HASH_OPTIONS = ['cost' => 12 ];

    public static function hashPassword(string $password): string {
        return password_hash(
            $password,
            self::HASH_ALGORITHM,
            self::HASH_OPTIONS
        );
    }

    public static function verifyPassword(string $password, string $hashedPassword): bool {
        return password_verify($password, $hashedPassword);
    }
}