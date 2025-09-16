<?php

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;
use app\services\EmailService;

class ContactController extends AbstractController
{
    public function __construct(
        Application $application,
        private EmailService $emailService,
        private PersonDataHelper $personDataHelper,
        private WebApp $webApp,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function contact($eventId = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $event = null;
            if ($eventId !== null) {
                $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary, StartTime, Audience');
                if (!$event || $event->Audience != EventAudience::ForAll->value) $eventId = $event = null;
            }
            $this->render('Common/views/contact.latte', Params::getAll([
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->get()->person ?? false),
                'event' => $event,
            ]));
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $this->handleContactForm($eventId);
        else $this->raiseMethodNotAllowed(__FILE__, __LINE__);
    }

    #region Private functions
    private function handleContactForm($eventId): void
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

        $errors = [];
        if (empty($name)) $errors[] = 'Nom et prénom sont requis.';
        if (empty($email)) $errors[] = 'Un email valide est requis.';
        if (empty($message)) $errors[] = 'Le message est requis.';

        if (empty($errors)) $this->sendContactMessage($input, $eventId);
        else $this->redirectWithErrors($errors, $name, $email, $message);
    }

    private function sendContactMessage(array $input, $eventId): void
    {
        $adminEmail = $this->dataHelper->get('Settings', ['Name' => 'contactEmail'], 'Value')->Value ?? '';
        if ($adminEmail === '') $adminEmail = $this->personDataHelper->getWebmasterEmail();
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->application->getErrorManager()->raise(ApplicationError::InvalidSetting, 'Invalid contactEmmail', __FILE__, __LINE__);
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
