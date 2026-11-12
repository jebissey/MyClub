<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\Application;
use app\helpers\To;

final class EventParticipationDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * Adds an Individual as a confirmed participant.
     * Invitation details remain null for direct registrations.
     */
    public function addConfirmedParticipant(
        int $individualId,
        int $eventId,
        ?int $invitedBy = null,
        ?string $invitedAt = null
    ): void {
        $this->set('Participant', [
            'IdEvent' => $eventId,
            'IdIndividual' => $individualId,
            'InvitedBy' => $invitedBy,
            'InvitedAt' => $invitedAt,
        ]);
    }

    public function isAlreadyRegistered(int $individualId, int $eventId): bool
    {
        return $this->get('Participant', [
            'IdIndividual' => $individualId,
            'IdEvent' => $eventId,
        ], 'Id') !== false;
    }

    public function isUserRegistered(int $eventId, string $userEmail): bool
    {
        $sql = "
            SELECT i.Email
            FROM Participant pa
            JOIN Individual i ON pa.IdIndividual = i.Id
            WHERE pa.IdEvent = :eventId
              AND i.Email = :userEmail COLLATE NOCASE
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':eventId' => $eventId,
            ':userEmail' => $userEmail,
        ]);

        return $stmt->fetch(PDO::FETCH_OBJ) !== false;
    }

    public function removeParticipantByIndividual(int $individualId, int $eventId): void
    {
        $participant = $this->get('Participant', [
            'IdIndividual' => $individualId,
            'IdEvent' => $eventId,
        ], 'Id');

        if ($participant === false) {
            return;
        }

        $participantId = To::requireInt($participant->Id ?? null);

        $this->delete('ParticipantSupply', ['IdParticipant' => $participantId]);
        $this->delete('Participant', ['Id' => $participantId]);
    }
}
