<?php

namespace app\interfaces;

interface EventServiceInterface
{
    public function deleteEvent(int $id, int $userId): array;
    public function duplicateEvent(int $id, int $userId, string $mode): array;
    public function getEvent(int $id): array;
    public function sendEventEmails(object $event, string $title, string $body, string $recipients): array;
}
