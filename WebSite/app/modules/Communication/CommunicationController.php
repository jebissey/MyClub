<?php

declare(strict_types=1);

namespace app\modules\Communication;

use app\helpers\Application;
use app\helpers\TranslationManager;
use app\modules\Common\AbstractController;
use app\modules\Common\services\EmailService;
use app\modules\Communication\viewModels\CommunicationEditViewModel;
use app\modules\Common\viewModels\InfoViewModel;

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
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isCommunicationManager(), __FILE__, __LINE__)) {
            $connectedUser = $this->application->getConnectedUser();
            $userEmail = $connectedUser->person->Email ?? '';

            $contactEmailRow = $this->dataHelper->get('Settings', ['Name' => 'contactEmail'], 'Value');
            $contactEmail = ($contactEmailRow !== false && isset($contactEmailRow->Value)) ? $contactEmailRow->Value : '';

            $viewModel = new CommunicationEditViewModel(
                groups: array_values($this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name')),
                navItems: $this->getNavItems($connectedUser->person),
                smtpFrom: $this->emailService->getSmtpConfig()?->getSenderAddress($userEmail),
                i18n: [
                    'subjectRequired'     => ($this->t)('communication.email.subject_required'),
                    'contentRequired'     => ($this->t)('communication.email.content_required'),
                    'confirmSend'         => ($this->t)('communication.email.confirm_send'),
                    'sendError'           => ($this->t)('communication.email.send_error'),
                    'unexpectedError'     => ($this->t)('communication.email.unexpected_error'),
                    'noMembers'           => ($this->t)('communication.members.none_found'),
                    'quotaDailyReached'   => ($this->t)('communication.quota.daily_reached'),
                    'quotaMonthlyReached' => ($this->t)('communication.quota.monthly_reached'),
                    'quotaAlmost'         => ($this->t)('communication.quota.almost_exceeded'),
                ],
                connectedPersonId: $connectedUser->person->Id ?? null,
                contactEmail: $contactEmail,
                layoutParams: $this->getAllParams([
                    'page' => $connectedUser->getPage(),
                    'userEmail' => $userEmail,
                ]),
            );
            $this->render('Communication/views/communication_edit.latte', $viewModel->toArray());
        }
    }

    public function helpCommunication(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isCommunicationManager(), __FILE__, __LINE__)) {
            $lang = TranslationManager::getCurrentLanguage();
            $helpRow = $this->dataHelper->get('Languages', ['Name' => 'Help_Communication'], $lang);
            $content = ($helpRow !== false && isset($helpRow->$lang)) ? $helpRow->$lang : '';

            $viewModel = new InfoViewModel(
                content: $content,
                timer: 0,
                layoutParams: $this->getAllParams([]),
            );
            $this->render('Common/views/info.latte', $viewModel->toArray());
        }
    }
}
