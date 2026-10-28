<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\helpers\WebApp;
use app\models\ContactDataHelper;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\Common\valueObjects\EventRegistrationRow;

/**
 * Sends an event registration link to a contact.
 */
final class RegistrationLinkService
{
    public function __construct(
        private readonly ContactDataHelper $contactDataHelper,
        private readonly EmailService $emailService,
    ) {
    }

    public function send(
        string $adminEmail,
        string $name,
        string $emailContact,
        EventRegistrationRow $event
    ): bool {
        $token = $this->contactDataHelper->issueRegistrationToken($emailContact, $name);

        $emailMessage = new EmailMessage(
            from: $adminEmail,
            to: $emailContact,
            subject: "Lien d'inscription pour " . $event->summary,
            body: WebApp::getBaseUrl() . "event/{$event->id}/{$token}",
            isHtml: false
        );
        return $this->emailService->send($emailMessage);
    }
}
