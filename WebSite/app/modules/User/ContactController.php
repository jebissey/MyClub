<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\CredentialService;
use app\modules\Common\services\EmailService;
use app\valueObjects\EmailMessage;
use app\valueObjects\EventRegistrationRow;

class ContactController extends AbstractController
{
    private const MIN_FILL_SECONDS       = 5;
    private const RATE_LIMIT_MAX         = 3;
    private const RATE_LIMIT_WINDOW      = 600; // 10 minutes
    private const TURNSTILE_VERIFY_URL   = 'https://challenges.cloudflare.com/turnstile/v1/siteverify';

    public function __construct(
        Application $application,
        private EmailService $emailService,
        private PersonDataHelper $personDataHelper,
        private WebApp $webApp,
        private CredentialService $credentials
    ) {
        parent::__construct($application);
    }

    public function contact(?int $eventId = null): void
    {
        if (WebApp::getRequestMethod() === 'GET') {
            $_SESSION['contact_form_loaded'] = time();

            $event = null;
            if ($eventId !== null) {
                $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary, StartTime, Audience');
                /** @var object{Id: int, Summary: string, StartTime: string, Audience: string}|false $event */
                if (!$event || $event->Audience !== EventAudience::ForAll->value) {
                    $eventId = $event = null;
                }
            }
            $this->render('Common/views/contact.latte', $this->getAllParams([
                'navItems'          => $this->getNavItems($this->application->getConnectedUser()->person),
                'event'             => $event,
                'page'              => $this->application->getConnectedUser()->getPage(),
                'turnstileSiteKey'  => $this->credentials->get('turnstile', 'site_key') ?? '',
                'btn_HistoryBack' => true,

            ]));
        } elseif (WebApp::getRequestMethod() === 'POST') {
            $this->handleContactForm();
        } else {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
        }
    }

    #region Private functions
    private function handleContactForm(): void
    {
        $honeypot = To::str($_POST['website'] ?? '');
        if ($honeypot !== '') {
            $this->silentFail("honey pot field filling with {$honeypot}");
            return;
        }

        $formLoadedAt = To::int($_SESSION['contact_form_loaded'] ?? 0);
        $duration = time() - $formLoadedAt;
        if ($duration <= self::MIN_FILL_SECONDS) {
            $this->silentFail("too fast for a human ({$duration}s)");
            return;
        }
        unset($_SESSION['contact_form_loaded']);

        $ip = To::str($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!$this->checkRateLimit($ip)) {
            $this->silentFail('too many attempts');
            return;
        }

        $turnstileToken = To::str($_POST['cf-turnstile-response'] ?? '');
        if (!$this->verifyTurnstile($turnstileToken, $ip)) {
            $this->silentFail('turnstile challenge failed');
            return;
        }

        $schema = [
            'name'    => FilterInputRule::PersonName->value,
            'email'   => FilterInputRule::Email->value,
            'message' => FilterInputRule::HtmlSafeText->value,
            'eventId' => FilterInputRule::Int->value,
        ];

        $input   = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $name    = To::str($input['name']    ?? '');
        $email   = To::str($input['email']   ?? '');
        $message = To::str($input['message'] ?? '');
        $eventIdRaw = $input['eventId'] ?? null;
        $eventId = $eventIdRaw !== null ? To::int($eventIdRaw) : null;

        $errors = [];
        if (empty($name)) {
            $errors[] = 'Nom et prénom sont requis.';
        }
        if (empty($email)) {
            $errors[] = 'Un email valide est requis.';
        }
        if ($eventId === null && empty($message)) {
            $errors[] = 'Le message est requis.';
        }

        if (empty($errors)) {
            $this->sendContactMessage($name, $email, $message, $eventId);
        } else {
            $this->redirectWithErrors($errors, $name, $email, $message);
        }
    }

    private function verifyTurnstile(string $token, string $ip): bool
    {
        $secret = $this->credentials->get('turnstile', 'secret_key') ?? '';
        if ($secret === '') {
            return true;
        }
        if ($token === '') {
            return false;
        }

        $payload = http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);

        $result = @file_get_contents(self::TURNSTILE_VERIFY_URL, false, $ctx);
        if ($result === false) {
            return true;
        }

        $data = json_decode($result, true);
        if (!is_array($data)) {
            return false;
        }
        return ($data['success'] ?? false) === true;
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
        /** @var object{attempts: int, since: int}|false $row */

        if (!$row || $now - $row->since > self::RATE_LIMIT_WINDOW) {
            $this->dataHelper->set('ContactRateLimit', [
                'ip_hash'  => $hash,
                'attempts' => 1,
                'since'    => $now,
            ], $row ? ['ip_hash' => $hash] : []);
            return true;
        }

        if ($row->attempts >= self::RATE_LIMIT_MAX) {
            return false;
        }

        $this->dataHelper->set('ContactRateLimit', ['attempts' => $row->attempts + 1], ['ip_hash' => $hash]);
        return true;
    }

    private function sendContactMessage(string $name, string $email, string $message, ?int $eventId): void
    {
        $contactEmail = $this->dataHelper->get('Settings', ['Name' => 'contactEmail'], 'Value')->Value ?? '';
        if ($contactEmail === '') {
            $contactEmail = $this->personDataHelper->getWebmasterEmail();
        }
        if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $this->application->getErrorManager()->raise(ApplicationError::InvalidSetting, 'Invalid contactEmail', 3000, false);
            return;
        }

        if ($eventId !== null) {
            $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary');
            /** @var object{Id: int, Summary: string}|false $event */
            if (!$event) {
                $this->raiseBadRequest("Unknown event {$eventId}", __FILE__, __LINE__);
                return;
            }
            $emailSent = $this->personDataHelper->sendRegistrationLink($contactEmail, $name, $email, new EventRegistrationRow($event->Id, $event->Summary));
        } else {
            $emailMessage = new EmailMessage(
                from: $email,
                to: $contactEmail,
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

    /**
     * @param array<int, string> $errors
     */
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
