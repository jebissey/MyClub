<?php
declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;

class EventNeedDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function needsForEvent(int $eventId): array
    {
        $sql = "
            SELECT 
                EventNeed.*,
                Need.Label,
                Need.Name,
                Need.ParticipantDependent,
                NeedType.Name AS TypeName
            FROM EventNeed
            INNER JOIN Need ON EventNeed.IdNeed = Need.Id
            INNER JOIN NeedType ON Need.IdNeedType = NeedType.Id
            WHERE EventNeed.IdEvent = :eventId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
