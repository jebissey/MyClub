<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\EmailService;

class ContactController extends AbstractController
{
    public function __construct(
        Application $application,
        private EmailService $emailService,
        private PersonDataHelper $personDataHelper,
        private WebApp $webApp,
    ) {
        parent::__construct($application);
    }

    public function contact($eventId = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $event = null;
            if ($eventId !== null) {
                $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary, StartTime, Audience');
                if (!$event || $event->Audience != EventAudience::ForAll->value) $eventId = $event = null;
            }
            $this->render('Common/views/contact.latte', $this->getAllParams([
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person ?? false),
                'event' => $event,
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $this->handleContactForm();
        else $this->raiseMethodNotAllowed(__FILE__, __LINE__);
    }

    #region Private functions
    private function handleContactForm(): void
    {
        $schema = [
            'name' => FilterInputRule::PersonName->value,
            'email' => FilterInputRule::Email->value,
            'message' => FilterInputRule::HtmlSafeText->value,
            'eventId' => FilterInputRule::Int->value,
        ];

        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $message = $input['message'] ?? '';
        $eventId = $input['eventId'] ?? null;

        $errors = [];
        if (empty($name)) $errors[] = 'Nom et prénom sont requis.';
        if (empty($email)) $errors[] = 'Un email valide est requis.';
        if ($eventId === null && empty($message)) $errors[] = 'Le message est requis.';

        if (empty($errors)) $this->sendContactMessage($input, $eventId);
        else $this->redirectWithErrors($errors, $name, $email, $message);
    }

    private function sendContactMessage(array $input, $eventId): void
    {
        $adminEmail = $this->dataHelper->get('Settings', ['Name' => 'contactEmail'], 'Value')->Value ?? '';
        if ($adminEmail === '') $adminEmail = $this->personDataHelper->getWebmasterEmail();
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->application->getErrorManager()->raise(ApplicationError::InvalidSetting, 'Invalid contactEmmail', 3000, false, __FILE__, __LINE__);
            return;
        }

        $name = $input['name'];
        $email = $input['email'];
        $message = $input['message'];

        if ($eventId != null) {
            $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary');
            if (!$event) {
                $this->raiseBadRequest("Unknown event {$eventId}", __FILE__, __LINE__);
                return;
            }
            $emailSent = $this->personDataHelper->sendRegistrationLink($adminEmail, $name, $email, $event);
        } else $emailSent = $this->emailService->sendContactEmail($adminEmail, $name, $email, $message);

        if ($emailSent) {
            $url = '/contact' . $this->webApp->buildUrl([
                'success' => 'Message envoyé avec succès.',
                'who' => $email
            ]);
            $this->redirect($url);
        } else {
            $params = [
                'error' => 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.',
                'old_name' => $name,
                'old_email' => $email,
                'old_message' => $message
            ];
            $queryString = http_build_query($params);
            $this->redirect('/contact?' . $queryString);
        }
    }

    private function redirectWithErrors(array $errors, string $name, string $email, string $message): void
    {
        $params = [
            'errors' => implode('|', $errors),
            'old_name' => $name,
            'old_email' => $email,
            'old_message' => $message
        ];
        $queryString = http_build_query($params);
        $this->redirect('/contact?' . $queryString);
    }
}
