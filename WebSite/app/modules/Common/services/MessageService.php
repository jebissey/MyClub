<?php

declare(strict_types=1);

namespace app\modules\Common\services;

final class MessageService
{
    public static function set(string $message, string $type = 'success'): void
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    public static function get(): array
    {
        $message = $_SESSION['flash_message'] ?? '';
        $type = $_SESSION['flash_type'] ?? '';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return [$message, $type];
    }
}
