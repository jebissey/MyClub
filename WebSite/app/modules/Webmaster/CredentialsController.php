<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\WebApp;
use app\modules\Common\TableController;
use app\modules\Common\services\CredentialService;
use app\modules\Webmaster\services\EmailCredentialsSaver;
use app\modules\Webmaster\viewModels\EmailCredentialsViewModel;
use app\modules\Webmaster\viewModels\HelloAssoCredentialsViewModel;
use app\modules\Webmaster\viewModels\TurnstileCredentialsViewModel;

final class CredentialsController extends TableController
{
    public function __construct(
        Application $application,
        private readonly CredentialService $credentials,
        private readonly EmailCredentialsSaver $emailCredentialsSaver
    ) {
        parent::__construct($application);
    }

    public function sendEmailCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $viewModel = new EmailCredentialsViewModel(
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                sendMethod: $this->credentials->get('email', 'method'),
                smtpAccount: $this->credentials->get('smtp', 'username'),
                smtpFrom: $this->credentials->get('smtp', 'from'),
                smtpHost: $this->credentials->get('smtp', 'host'),
                smtpPort: $this->credentials->get('smtp', 'port'),
                smtpEncryption: $this->credentials->get('smtp', 'encryption'),
                dailyLimit: $this->credentials->get('email', 'daily_limit'),
                monthlyLimit: $this->credentials->get('email', 'monthly_limit'),
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->views->render('Webmaster/views/emailCredentials.latte', $viewModel);
        }
    }

    public function sendEmailCredentialsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $schema = [
                'sendMethod'       => FilterInputRule::Text->value,
                'smtpAccount'      => FilterInputRule::Text->value,
                'smtpPassword'     => FilterInputRule::Password->value,
                'smtpFrom'         => FilterInputRule::Email->value,
                'smtpHost'         => FilterInputRule::Uri->value,
                'smtpPort'         => FilterInputRule::Integer->value,
                'smtpEncryption'   => FilterInputRule::Text->value,
                'dailyLimit'       => FilterInputRule::Integer->value,
                'monthlyLimit'     => FilterInputRule::Integer->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $this->emailCredentialsSaver->save($input);

            $this->redirect('/webmaster');
        }
    }

    public function turnstileCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $viewModel = new TurnstileCredentialsViewModel(
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                turnstileSiteKey: $this->credentials->get('turnstile', 'site_key'),
                turnstileConfigured: !empty($this->credentials->get('turnstile', 'secret_key')),
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->views->render('Webmaster/views/turnstileCredentials.latte', $viewModel);
        }
    }

    public function turnstileCredentialsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $schema = [
                'turnstileSiteKey'   => FilterInputRule::Text->value,
                'turnstileSecretKey' => FilterInputRule::Text->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $this->credentials->set('turnstile', 'site_key', To::str($input['turnstileSiteKey'] ?? null, ''));
            if (!empty($input['turnstileSecretKey'])) {
                $this->credentials->set('turnstile', 'secret_key', To::str($input['turnstileSecretKey'], ''));
            }

            $this->redirect('/webmaster');
        }
    }

    public function helloassoCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $viewModel = new HelloAssoCredentialsViewModel(
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                helloassoClientId: $this->credentials->get('helloasso', 'client_id'),
                helloassoConfigured: !empty($this->credentials->get('helloasso', 'client_secret')),
                helloassoOrgSlug: $this->credentials->get('helloasso', 'org_slug'),
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->views->render('Webmaster/views/helloassoCredentials.latte', $viewModel);
        }
    }

    public function helloassoCredentialsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $schema = [
                'helloassoClientId'     => FilterInputRule::Text->value,
                'helloassoClientSecret' => FilterInputRule::Text->value,
                'helloassoOrgSlug'      => FilterInputRule::Text->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $this->credentials->set('helloasso', 'client_id', To::str($input['helloassoClientId'] ?? null, ''));
            if (!empty($input['helloassoClientSecret'])) {
                $this->credentials->set('helloasso', 'client_secret', To::str($input['helloassoClientSecret'], ''));
            }
            if (!empty($input['helloassoOrgSlug'])) {
                $this->credentials->set('helloasso', 'org_slug', To::str($input['helloassoOrgSlug'], ''));
            }

            $this->redirect('/webmaster');
        }
    }
}
