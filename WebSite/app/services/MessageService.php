<?php

namespace app\services;

use app\interfaces\MessageServiceInterface;

class MessageService implements MessageServiceInterface
{
    private $messageDataHelper;
    private $eventDataHelper;

    public function __construct($messageDataHelper, $eventDataHelper)
    {
        $this->messageDataHelper = $messageDataHelper;
        $this->eventDataHelper = $eventDataHelper;
    }

    public function addMessage(int $eventId, int $personId, string $text): array
    {
        $messageId = $this->messageDataHelper->addMessage($eventId, $personId, $text);
        $messages = $this->messageDataHelper->getEventMessages($eventId);
        
        foreach ($messages as $message) {
            if ($message->Id == $messageId) {
                return ['success' => true, 'message' => 'Message ajouté', 'data' => $message];
            }
        }
        
        throw new \RuntimeException('Message not found after creation');
    }

    public function updateMessage(int $messageId, int $personId, string $text): array
    {
        $this->messageDataHelper->updateMessage($messageId, $personId, $text);
        
        return [
            'success' => true,
            'message' => 'Message mis à jour',
            'data' => ['messageId' => $messageId, 'text' => $text]
        ];
    }

    public function deleteMessage(int $messageId, int $personId): array
    {
        $this->messageDataHelper->deleteMessage($messageId, $personId);
        
        return [
            'success' => true,
            'message' => 'Message supprimé',
            'data' => ['messageId' => $messageId]
        ];
    }
}
