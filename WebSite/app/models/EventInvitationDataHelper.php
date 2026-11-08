<?php

declare(strict_types=1);

namespace app\models;

use DateTime;
use app\helpers\Application;
use app\modules\Event\valueObjects\IndividualRow;
use app\modules\Event\valueObjects\InvitationRow;
use app\modules\Event\valueObjects\PendingInvitationRow;

final class EventInvitationDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    public function findPendingInvitation(
        string $email,
        int $eventId
    ): ?PendingInvitationRow {
        $row = $this->get(
            'Invitation',
            ['Email' => $email, 'IdEvent' => $eventId],
            'Id, NickName, Token, InvitedAt'
        );

        return $row === false ? null : PendingInvitationRow::fromStdClass($row);
    }

    /**
     * Creates a pending invitation for an external email address.
     * The Individual record is created only when the invitation is confirmed.
     */
    public function createInvitation(
        string $email,
        string $nickname,
        int $eventId,
        string $token,
        int $invitedBy,
        string $invitedAt
    ): void {
        $this->set('Invitation', [
            'Email'     => $email,
            'NickName'  => $nickname,
            'IdEvent'   => $eventId,
            'Token'     => $token,
            'InvitedBy' => $invitedBy,
            'InvitedAt' => $invitedAt,
        ]);
    }

    public function findInvitationByToken(string $token): ?InvitationRow
    {
        $row = $this->get(
            'Invitation',
            ['Token' => $token],
            'Id, Email, NickName, IdEvent, InvitedBy, InvitedAt'
        );

        return $row === false ? null : InvitationRow::fromStdClass($row);
    }

    public function findIndividualByEmail(string $email): ?IndividualRow
    {
        $row = $this->get(
            'Individual',
            ['Email' => $email],
            'Id, Type'
        );

        return $row === false ? null : IndividualRow::fromStdClass($row);
    }

    /**
     * Creates a Contact Individual and its associated Contact record.
     * The Contact record receives its own general access token.
     *
     * @return int The newly created Individual ID.
     */
    public function createContactIndividual(string $email, string $nickname): int
    {
        $id = (int) $this->set('Individual', [
            'Type' => 'Contact',
            'Email' => $email,
            'FirstName' => $nickname !== '' ? $nickname : $email,
            'LastName' => '',
            'NickName' => $nickname,
        ]);

        $this->set('Contact', [
            'Id' => $id,
            'Token' => bin2hex(random_bytes(32)),
            'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        return $id;
    }
}
