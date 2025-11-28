<?php

declare(strict_types=1);

namespace app\modules\Event;

use DateTime;
use Throwable;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\EventDataHelper;
use app\modules\Common\AbstractController;
use app\services\EmailService;

class EventGuestController extends AbstractController
{
    public function __construct(
        Application $application,
        private EventDataHelper $eventDataHelper,
        private EmailService $emailService,
    ) {
        parent::__construct($application);
    }


    public function guest(string $message = '', string $type = ''): void
    {
        if (!($this->application->getConnectedUser()->isEventManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        $events = $this->eventDataHelper->getEventsForAllOrGuest();

        $this->render('Event/views/guest.latte', $this->getAllParams([
            'events' => $events,
            'navbarTemplate' => '../navbar/eventManager.latte',
            'layout' => $this->getLayout(),
            'message' => $message,
            'messageType' => $type,
            'page' => $this->application->getConnectedUser()->getPage(1),
        ]));
    }

    public function guestInvite()
    {
        if (!($this->application->getConnectedUser()->isEventManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $schema = [
                'email' => FilterInputRule::Email->value,
                'nickname' => FilterInputRule::PersonName->value,
                'eventId' => FilterInputRule::Int->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            $email = $input['email'] ?? '';
            if (empty($email)) {
                $this->guest('Adresse e-mail invalide', 'error');
                return;
            }
            $eventId = $input['eventId'] ?? 0;
            if ($eventId <= 0) {
                $this->guest('Veuillez sélectionner un événement', 'error');
                return;
            }
            $event = $this->eventDataHelper->getEventExternal($eventId);
            if (!$event) {
                $this->guest('Événement non trouvé ou non accessible', 'error');
                return;
            }
            $nickname = $input['nickname'] ?? '???';
            try {
                $contact = $this->dataHelper->get('Contact', ['Email' => $email], 'Id, Token, NickName, TokenCreatedAt');
                if (!$contact) {
                    $token = bin2hex(random_bytes(32));
                    $contactId = $this->dataHelper->set('Contact', [
                        'Email' => $email,
                        'NickName' => $nickname,
                        'Token' => $token,
                        'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s')
                    ]);
                } else {
                    $contactId = $contact->Id;
                    $token = $contact->Token;
                    if (!empty($nickname) && $nickname !== $contact->NickName) {
                        $this->dataHelper->set('Contact', ['NickName' => $nickname], ['Id', $contactId]);
                    }
                    if (
                        empty($token) ||
                        (new DateTime($contact->TokenCreatedAt))->diff(new DateTime())->days > 0
                    ) {
                        $token = bin2hex(random_bytes(32));
                        $this->dataHelper->set(
                            'Contact',
                            [
                                'Token' => $token,
                                'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s')
                            ],
                            ['Id', $contactId]
                        );
                    }
                }
                $existingGuest = $this->dataHelper->get('Guest', [
                    'IdContact' => $contactId,
                    'IdEvent' => $eventId
                ], 'Id');
                if ($existingGuest) {
                    $this->guest('Cette personne est déjà invitée à cet événement', 'error');
                    return;
                }
                $this->dataHelper->set(
                    'Guest',
                    [
                        'IdContact' => $contactId,
                        'IdEvent' => $eventId,
                        'InvitedBy' => $this->application->getConnectedUser()->person->Id
                    ]
                );

                $root = Application::$root;
                $invitationLink = $root . "/event/$eventId?t=$token";
                $subject = "Invitation à l'événement : " . $event->Summary;
                $body = "Bonjour" . (!empty($nickname) ? " {$nickname}" : "") . ",\n\n";
                $body .= "Vous êtes invité(e) à participer à l'événement suivant :\n\n";
                $body .= "Titre : {$event->Summary}\n";
                $body .= "Description : {$event->Description}\n";
                $body .= "Lieu : {$event->Location}\n";
                $body .= "Date : " . (new DateTime($event->StartTime))->format('d/m/Y à H:i') . "\n\n";
                $body .= "Pour confirmer votre participation, cliquez sur le lien suivant :\n";
                $body .= $invitationLink . "\n\n";
                $body .= "Cordialement,\nL'équipe BNW Dijon";
                $emailFrom = $this->application->getConnectedUser()->person->Email;
                $this->emailService->send($emailFrom, $email, $subject, $body);
                $this->guest('Invitation envoyée avec succès à ' . $email, 'success');
            } catch (Throwable $e) {
                $this->guest('Erreur lors de l\'envoi de l\'invitation. ' . $e->getMessage(), 'error');
            }
        } else $this->guest();
    }
}
