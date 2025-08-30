<?php

namespace app\interfaces;

use app\valueObjects\ApiResponse;

interface EventServiceInterface
{
    public function deleteEvent(int $id, int $userId): ApiResponse;
    public function duplicateEvent(int $id, int $userId, string $mode): ApiResponse;
    public function getEvent(int $id): ApiResponse;
    public function sendEventEmails(object $event, string $title, string $body, string $recipients): ApiResponse;
}
