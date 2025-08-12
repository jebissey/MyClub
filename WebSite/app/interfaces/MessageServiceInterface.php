<?php

namespace app\interfaces;

interface MessageServiceInterface
{
    public function addMessage(int $eventId, int $personId, string $text): array;
    public function updateMessage(int $messageId, int $personId, string $text): array;
    public function deleteMessage(int $messageId, int $personId): array;
}
