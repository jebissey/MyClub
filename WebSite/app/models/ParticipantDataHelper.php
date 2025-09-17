<?php
declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class ParticipantDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getEventParticipants($eventId)
    {
        $sql = "
            SELECT
                COALESCE(pe.Email, c.Email) AS Email,
                COALESCE(pe.NickName, c.NickName) AS NickName,
                pe.FirstName,
                pe.LastName,
                pe.Id AS PersonId,
                pe.InPresentationDirectory,
                c.Id AS ContactId
            FROM Participant pa
            LEFT JOIN Person pe ON pa.IdPerson = pe.Id
            LEFT JOIN Contact c ON pa.IdContact = c.Id
            WHERE pa.IdEvent = :eventId
            ORDER BY pe.FirstName, pe.LastName, c.NickName
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        return $stmt->fetchAll();
    }
}
