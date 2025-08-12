<?php

namespace app\services;

use RuntimeException;

use app\exceptions\UnauthorizedAccessException;
use app\interfaces\SupplyServiceInterface;

class SupplyService implements SupplyServiceInterface
{
    private $eventDataHelper;

    public function __construct($eventDataHelper)
    {
        $this->eventDataHelper = $eventDataHelper;
    }

    public function updateSupply(int $eventId, string $userEmail, int $needId, int $supply): array
    {
        if (!$this->eventDataHelper->isUserRegistered($eventId, $userEmail)) {
            throw new UnauthorizedAccessException('Non inscrit à cet événement');
        }
        $success = $this->eventDataHelper->updateUserSupply($eventId, $userEmail, $needId, $supply);        
        if (!$success) throw new RuntimeException('Erreur lors de la mise à jour');

        $eventNeeds = $this->eventDataHelper->getEventNeeds($eventId);
        $updatedNeed = $this->findUpdatedNeed($eventNeeds, $needId);

        return [
            'success' => true,
            'message' => 'Apport mis à jour avec succès',
            'updatedNeed' => $updatedNeed
        ];
    }

    private function findUpdatedNeed(array $eventNeeds, int $needId): ?array
    {
        foreach ($eventNeeds as $need) {
            if ($need->Id == $needId) {
                return [
                    'id' => $need->Id,
                    'providedQuantity' => $need->ProvidedQuantity,
                    'requiredQuantity' => $need->RequiredQuantity,
                    'percentage' => $need->RequiredQuantity > 0 
                        ? min(100, ($need->ProvidedQuantity / $need->RequiredQuantity) * 100) 
                        : 0
                ];
            }
        }
        return null;
    }
}
