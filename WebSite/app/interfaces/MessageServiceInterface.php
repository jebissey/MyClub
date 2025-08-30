<?php

namespace app\interfaces;

use app\valueObjects\ApiResponse;

interface MessageServiceInterface
{
    public function addMessage(int $eventId, int $personId, string $text): ApiResponse;
    public function updateMessage(int $messageId, int $personId, string $text): ApiResponse;
    public function deleteMessage(int $messageId, int $personId): ApiResponse;
}
