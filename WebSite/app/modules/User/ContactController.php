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
use app\modules\Common\services\RegistrationLinkService;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\Common\viewModels\ContactViewModel;
use app\modules\Common\valueObjects\EventRegistrationRow;

final class ContactController extends AbstractController
{
    private const MIN_FILL_SECONDS       = 5;
    private const RATE_LIMIT_MAX         = 3;
    private const RATE_LIMIT_WINDOW      = 600; // 10 minutes
    private const TURNSTILE_VERIFY_URL   = 'https://challenges.cloudflare.com/turnstile/v1/siteverify';

    public function __construct(
        Application $application,
        private readonly EmailService $emailService,
        private readonly PersonDataHelper $personDataHelper,
        private readonly RegistrationLinkService $registrationLinkService,
        private readonly WebApp $webApp,
        private readonly CredentialService $credentials
    ) {
        parent::__construct($application);
    }

    public function contact(?int $eventId = null): void
    {
        $method = WebApp::getRequestMethod();

        if ($method === 'POST') {
            $this->handleContactForm();
            return;
        }
        if ($method !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $_SESSION['contact_form_loaded'] = time();

        $event = null;
        if ($eventId !== null) {
            $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary, StartTime, Audience');
            /** @var object{Id: int, Summary: string, StartTime: string, Audience: string}|false $event */
            if (!$event || $event->Audience !== EventAudience::ForAll->value) {
                $event = null;
            }
        }

        $connectedUser = $this->application->getConnectedUser();
        $identity = $this->getConnectedIdentity();

        // A connected user cannot change identity: prefill and lock the fields.
        $viewModel = new ContactViewModel(
            navItems: $this->getNavItems($connectedUser->person),
            event: $event,
            turnstileSiteKey: (string)($this->credentials->get('turnstile', 'site_key') ?? ''),
            name: $identity['name'] ?? '',
            email: $identity['email'] ?? '',
            identityReadOnly: $identity !== null,
            layout: $this->getLayout(),
            page: $connectedUser->getPage(),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Common/views/contact.latte', $viewModel->toArray());
    }

    #region Private functions
    /**
     * Identity of the connected user, or null for a visitor.
     *
     * @return array{name: string, email: string}|null
     */
    private function getConnectedIdentity(): ?array
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            return null;
        }
        return [
            'name'  => trim(
                WebApp::sanitizeInput($person->LastName ?? '') . ' ' .
                    WebApp::sanitizeInput($person->FirstName ?? '')
            ),
            'email' => filter_var($person->Email, FILTER_VALIDATE_EMAIL) ?: '',
        ];
    }

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

        // A connected user cannot choose an identity: ignore what the client sent.
        $identity = $this->getConnectedIdentity();
        if ($identity !== null) {
            $name  = $identity['name'];
            $email = $identity['email'];
        }

        $errors = [];
        if ($name === '') {
            $errors[] = ($this->t)('contact.error.name_required');
        }
        if ($email === '') {
            $errors[] = ($this->t)('contact.error.email_required');
        }
        if ($eventId === null && $message === '') {
            $errors[] = ($this->t)('contact.error.message_required');
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
        $contactEmail = $this->dataHelper->getSetting('contactEmail', '');
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
            $emailSent = $this->registrationLinkService->send(
                $contactEmail,
                $name,
                $email,
                new EventRegistrationRow($event->Id, $event->Summary)
            );
        } else {
            $emailMessage = new EmailMessage(
                from: $email,
                to: $contactEmail,
                subject: sprintf(($this->t)('contact.email.subject'), $name, $email),
                body: $message,
                isHtml: false
            );
            $emailSent = $this->emailService->send($emailMessage);
        }

        if ($emailSent) {
            $url = '/contact' . $this->webApp->buildUrl([
                'success' => ($this->t)('contact.success.sent'),
                'who'     => $email,
            ]);
            $this->redirect($url);
        } else {
            $params = [
                'error'       => ($this->t)('contact.error.send_failed'),
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
    #endregion
}
