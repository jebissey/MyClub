<?php

declare(strict_types=1);

namespace app\modules\Event\services;

use DateTime;
use app\enums\ContactRegistrationOutcome;
use app\models\EventInvitationDataHelper;
use app\models\EventParticipationDataHelper;
use app\modules\Event\valueObjects\ContactRegistrationResult;

final class ExternalContactRegistrationService
{
    public function __construct(
        private readonly EventInvitationDataHelper $eventInvitationDataHelper,
        private readonly EventParticipationDataHelper $eventParticipationDataHelper,
    ) {
    }

    /**
     * Registers or unregisters an external contact identified by an invitation token.
     */
    public function handle(int $eventId, string $token, bool $set): ContactRegistrationResult
    {
        $invitation = $this->eventInvitationDataHelper->findInvitationByToken($token);
        if ($invitation === null || $invitation->idEvent !== $eventId) {
            return new ContactRegistrationResult(ContactRegistrationOutcome::UnknownToken);
        }

        // An invitation token is only valid for one day
        $interval = (new DateTime())->diff(new DateTime($invitation->invitedAt));
        if ($interval->days >= 1) {
            return new ContactRegistrationResult(ContactRegistrationOutcome::ExpiredToken);
        }

        $individual = $this->eventInvitationDataHelper->findIndividualByEmail($invitation->email);
        if ($individual !== null && $individual->type === 'Member') {
            return new ContactRegistrationResult(ContactRegistrationOutcome::MemberAccount);
        }

        $contactId = $individual !== null
            ? $individual->id
            : $this->eventInvitationDataHelper->createContactIndividual(
                $invitation->email,
                $invitation->nickName ?? ''
            );

        if (!$set) {
            $this->eventParticipationDataHelper->removeParticipantByIndividual($contactId, $eventId);
            return new ContactRegistrationResult(ContactRegistrationOutcome::Unregistered);
        }

        if ($this->eventParticipationDataHelper->isAlreadyRegistered($contactId, $eventId)) {
            return new ContactRegistrationResult(ContactRegistrationOutcome::AlreadyRegistered);
        }

        $this->eventParticipationDataHelper->addConfirmedParticipant(
            $contactId,
            $eventId,
            $invitation->invitedBy,
            $invitation->invitedAt
        );

        return new ContactRegistrationResult(
            ContactRegistrationOutcome::Registered,
            $contactId,
            $invitation->email,
            $invitation->nickName,
        );
    }
}
