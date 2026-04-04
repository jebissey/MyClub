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
use app\valueObjects\EmailMessage;

class ContactController extends AbstractController
{
    private const MIN_FILL_SECONDS  = 5;
    private const RATE_LIMIT_MAX    = 3;
    private const RATE_LIMIT_WINDOW = 600; // 10 minutes

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
            $_SESSION['contact_form_loaded'] = time();

            $event = null;
            if ($eventId !== null) {
                $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary, StartTime, Audience');
                if (!$event || $event->Audience != EventAudience::ForAll->value) $eventId = $event = null;
            }
            $this->render('Common/views/contact.latte', $this->getAllParams([
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person ?? false),
                'event'    => $event,
                'page'     => $this->application->getConnectedUser()->getPage(),
            ]));
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $this->handleContactForm();
        else $this->raiseMethodNotAllowed(__FILE__, __LINE__);
    }

    #region Private functions
    private function handleContactForm(): void
    {
        $honeypot = $_POST['website'] ?? '';
        if ($honeypot !== '') {
            $this->silentFail("honey pot field filling with {$honeypot}");
            return;
        }
        $formLoadedAt = $_SESSION['contact_form_loaded'] ?? 0;
        if (time() - $formLoadedAt < self::MIN_FILL_SECONDS) {
            $this->silentFail('too fast for a human');
            return;
        }
        unset($_SESSION['contact_form_loaded']);

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!$this->checkRateLimit($ip)) {
            $this->silentFail('too many attemps');
            return;
        }

        $schema = [
            'name'    => FilterInputRule::PersonName->value,
            'email'   => FilterInputRule::Email->value,
            'message' => FilterInputRule::HtmlSafeText->value,
            'eventId' => FilterInputRule::Int->value,
        ];

        $input   = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $name    = $input['name']    ?? '';
        $email   = $input['email']   ?? '';
        $message = $input['message'] ?? '';
        $eventId = $input['eventId'] ?? null;

        $errors = [];
        if (empty($name))                          $errors[] = 'Nom et prénom sont requis.';
        if (empty($email))                         $errors[] = 'Un email valide est requis.';
        if ($eventId === null && empty($message))  $errors[] = 'Le message est requis.';

        if (empty($errors)) $this->sendContactMessage($input, $eventId);
        else $this->redirectWithErrors($errors, $name, $email, $message);
    }

    private function silentFail(string $message): void
    {
        $url = '/contact' . $this->webApp->buildUrl([]);
        $this->redirect($url, ApplicationError::Ok, $message);
    }

    private function checkRateLimit(string $ip): bool
    {
        $hash = md5($ip);
        $now  = time();
        $row  = $this->dataHelper->get('ContactRateLimit', ['ip_hash' => $hash], 'attempts, since');

        if (!$row || $now - $row->since > self::RATE_LIMIT_WINDOW) {
            $this->dataHelper->set('ContactRateLimit', [
                'ip_hash'  => $hash,
                'attempts' => 1,
                'since'    => $now,
            ], $row ? ['ip_hash' => $hash] : []);
            return true;
        }

        if ($row->attempts >= self::RATE_LIMIT_MAX) return false;

        $this->dataHelper->set('ContactRateLimit', ['attempts' => $row->attempts + 1], ['ip_hash' => $hash]);
        return true;
    }

    private function sendContactMessage(array $input, $eventId): void
    {
        $adminEmail = $this->dataHelper->get('Settings', ['Name' => 'contactEmail'], 'Value')->Value ?? '';
        if ($adminEmail === '') $adminEmail = $this->personDataHelper->getWebmasterEmail();
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->application->getErrorManager()->raise(ApplicationError::InvalidSetting, 'Invalid contactEmmail', 3000, false, __FILE__, __LINE__);
            return;
        }

        $name    = $input['name'];
        $email   = $input['email'];
        $message = $input['message'];

        if ($eventId != null) {
            $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary');
            if (!$event) {
                $this->raiseBadRequest("Unknown event {$eventId}", __FILE__, __LINE__);
                return;
            }
            $emailSent = $this->personDataHelper->sendRegistrationLink($adminEmail, $name, $email, $event);
        } else {
            $emailMessage = new EmailMessage(
                from: $email,
                to: $adminEmail,
                subject: "Message de contact de {$name} ({$email})",
                body: $message,
                isHtml: false
            );
            $emailSent = $this->emailService->send($emailMessage);
        }

        if ($emailSent) {
            $url = '/contact' . $this->webApp->buildUrl([
                'success' => 'Message envoyé avec succès.',
                'who'     => $email,
            ]);
            $this->redirect($url);
        } else {
            $params = [
                'error'       => "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.",
                'old_name'    => $name,
                'old_email'   => $email,
                'old_message' => $message,
            ];
            $this->redirect('/contact?' . http_build_query($params));
        }
    }

    private function redirectWithErrors(array $errors, string $name, string $email, string $message): void
    {
        $params = [
            'errors'      => implode('|', $errors),
            'old_name'    => $name,
            'old_email'   => $email,
            'old_message' => $message,
        ];
        $this->redirect('/contact?' . http_build_query($params));
    }
}
