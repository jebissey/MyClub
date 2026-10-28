<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\enums\YesNo;
use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserAccountViewModel;

final class UserAccountController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function account(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;
        $imported = $this->dataHelper->get('Member', ['Id' => $person->id], 'Imported');
        $readOnly = $imported !== false && (bool)($imported->Imported ?? false);

        $viewModel = new UserAccountViewModel(
            navItems: $this->getNavItems($person->email),
            readOnly: $readOnly,
            email: filter_var($person->email, FILTER_VALIDATE_EMAIL) ?: '',
            firstName: WebApp::sanitizeInput($person->firstName ?? ''),
            lastName: WebApp::sanitizeInput($person->lastName ?? ''),
            nickName: WebApp::sanitizeInput($person->nickName ?? ''),
            avatar: WebApp::sanitizeInput($person->avatar ?? ''),
            useGravatar: WebApp::sanitizeInput(
                $person->useGravatar ? YesNo::Yes->value : YesNo::No->value,
                $this->application->enumToValues(YesNo::class),
                YesNo::No->value
            ),
            emojis: Application::EMOJI_LIST,
            isSelfEdit: true,
            i18n: [
                'account.form.emoji.select_label'     => ($this->t)('account.form.emoji.select_label'),
                'account.form.emoji.missing_elements' => ($this->t)('account.form.emoji.missing_elements'),
                'account.form.emoji.none_detected'    => ($this->t)('account.form.emoji.none_detected'),
                'account.form.emoji.selected'         => ($this->t)('account.form.emoji.selected'),
            ],
            layout: $this->getLayout(),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_account.latte', $viewModel->toArray());
    }

    public function accountSave(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;
        $imported = $this->dataHelper->get('Member', ['Id' => $person->id], 'Imported');
        $isImported = $imported !== false && (bool)($imported->Imported ?? false);

        $schema = [
            'email' => FilterInputRule::Email->value,
            'firstName' => FilterInputRule::PersonName->value,
            'lastName' => FilterInputRule::PersonName->value,
            'nickName' => FilterInputRule::HtmlSafeName->value,
            'useGravatar' => $this->application->enumToValues(YesNo::class),
            'avatar' => FilterInputRule::Avatar->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $this->dataHelper->set('Individual', [
            'FirstName' => $input['firstName'] ?? '???',
            'LastName' => $input['lastName'] ?? '???',
            'NickName' => $input['nickName'] ?? '',
            'Avatar' => ($input['useGravatar'] ?? YesNo::No->value) == YesNo::Yes->value ? '' : $input['avatar'] ?? '🤔',
        ], ['Id' => $person->id]);
        $this->dataHelper->set('Member', [
            'useGravatar' => $input['useGravatar'] ?? YesNo::No->value,
        ], ['Id' => $person->id]);
        if (!$isImported) {
            $emailInput = $input['email'] ?? '';
            $email = is_string($emailInput) ? urldecode($emailInput) : '';
            $this->dataHelper->set('Individual', ['Email' => $email], ['Id' => $person->id]);
            $_SESSION['user'] = $email;
        }
        $this->redirect('/user');
    }
}
