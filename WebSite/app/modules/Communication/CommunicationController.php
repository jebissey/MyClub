<?php

declare(strict_types=1);

namespace app\modules\Communication;

use app\helpers\Application;
use app\modules\Common\AbstractController;
use app\modules\Common\services\EmailService;

class CommunicationController extends AbstractController
{
    public function __construct(
        Application $application,
        private EmailService $emailService
    ) {
        parent::__construct($application);
    }

    public function edit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isCommunicationManager())) {
            $connectedUser = $this->application->getConnectedUser();
            $userEmail = $connectedUser->person?->Email ?? '';

            $this->render('Communication/views/communication_edit.latte', $this->getAllParams([
                'groups'          => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'navItems'        => $this->getNavItems($connectedUser->person ?? false),
                'page'            => $connectedUser->getPage(),
                'btn_HistoryBack' => true,
                'smtpFrom'        => $this->emailService->getSmtpConfig()?->getSenderAddress($userEmail),
                'userEmail'       => $userEmail,
                'translations' => [
                    'subjectRequired' => $this->languagesDataHelper->translate('communication.email.subject_required'),
                    'contentRequired' => $this->languagesDataHelper->translate('communication.email.content_required'),
                    'confirmSend' => $this->languagesDataHelper->translate('communication.email.confirm_send'),
                    'sendError' => $this->languagesDataHelper->translate('communication.email.send_error'),
                    'unexpectedError' => $this->languagesDataHelper->translate('communication.email.unexpected_error'),
                    'noMembers' => $this->languagesDataHelper->translate('communication.members.none_found'),
                    'quotaDailyReached' => $this->languagesDataHelper->translate('communication.quota.daily_reached'),
                    'quotaMonthlyReached' => $this->languagesDataHelper->translate('communication.quota.monthly_reached'),
                    'quotaAlmost' => $this->languagesDataHelper->translate('communication.quota.almost_exceeded'),
                ],
            ]));
        }
    }
}
