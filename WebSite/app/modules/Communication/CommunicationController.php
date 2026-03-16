<?php

declare(strict_types=1);

namespace app\modules\Communication;

use app\enums\FilterInputRule;
use app\exceptions\EmailException;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\EmailService;
use app\valueObjects\EmailMessage;

class CommunicationController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly EmailService $emailService,
        private readonly PersonDataHelper $personDataHelper,
    ) {
        parent::__construct($application);
    }

    public function edit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isCommunicationManager())) {
            $connectedUser = $this->application->getConnectedUser();

            $this->render('Communication/views/communication_edit.latte', $this->getAllParams([
                'groups'          => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'navItems'        => $this->getNavItems($connectedUser->person ?? false),
                'page'            => $connectedUser->getPage(),
                'btn_HistoryBack' => true,
            ]));
        }
    }

    public function sendCommunication(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isCommunicationManager())) {
            $schema = [
                'recipient_ids' => FilterInputRule::ArrayInt->value,
                'subject'       => FilterInputRule::String->value,
                'content'       => FilterInputRule::Html->value,
            ];
            $input        = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            $recipientIds = array_filter(array_map('intval', $input['recipient_ids'] ?? []));
            $subject      = trim($input['subject'] ?? '');
            $content      = trim($input['content'] ?? '');

            if (empty($recipientIds) || $subject === '' || $content === '') {
                $this->renderInfo('Champs obligatoires manquants.');
                return;
            }

            $allMembers = $this->personDataHelper->getAllPersons();
            $index      = array_column($allMembers, null, 'Id');

            $bcc = [];
            foreach ($recipientIds as $id) {
                $member = $index[$id] ?? null;
                if ($member === null || empty($member->Email)) {
                    continue;
                }
                $bcc[] = $member->Email;
            }

            $from = $this->emailService->getSmtpConfig()->getSenderAddress($this->application->getConnectedUser()->person->Email);
            $emailMessage = new EmailMessage(
                from: $from,
                to: $from,
                bcc: $bcc,
                subject: $subject,
                body: $content,
                isHtml: true,
            );

            try {
                $emailSent = $this->emailService->send($emailMessage);
            } catch (EmailException $e) {
                $this->renderInfo('Envoi impossible : ' . $e->getMessage());
                return;
            }

            $message = $emailSent
                ? 'Message envoyé avec succès à ' . count($bcc) . ' destinataire(s) en copie cachée.'
                : 'L\'envoi a échoué. Veuillez réessayer ou contacter l\'administrateur.';

            $this->render('Common/views/info.latte', [
                'content'          => $message,
                'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization() ?? false,
                'currentVersion'   => Application::VERSION,
                'timer'            => 10000,
                'previousPage'     => true,
                'page'             => $this->application->getConnectedUser()->getPage(),
            ]);
        }
    }

    #private functions
    private function renderInfo(string $message): void
    {
        $this->render('Common/views/info.latte', [
            'content'          => $message,
            'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization() ?? false,
            'currentVersion'   => Application::VERSION,
            'timer'            => 10000,
            'previousPage'     => true,
            'page'             => $this->application->getConnectedUser()->getPage(),
        ]);
    }
}
