<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\enums\YesNo;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class UserAccountController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function account(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $person = $this->application->getConnectedUser()->person;
        $this->render('User/views/user_account.latte', Params::getAll([
            'readOnly' => $person->Imported == 1 ? true : false,
            'email' => filter_var($person->Email, FILTER_VALIDATE_EMAIL) ?: '',
            'firstName' => WebApp::sanitizeInput($person->FirstName),
            'lastName' => WebApp::sanitizeInput($person->LastName),
            'nickName' => WebApp::sanitizeInput($person->NickName ?? ''),
            'avatar' => WebApp::sanitizeInput($person->Avatar ?? ''),
            'useGravatar' => WebApp::sanitizeInput($person->UseGravatar, $this->application->enumToValues(YesNo::class), YesNo::No->value),
            'emojis' => Application::EMOJI_LIST,
            'isSelfEdit' => true,
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function accountSave(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'email' => FilterInputRule::Email->value,
            'firstName' => FilterInputRule::PersonName->value,
            'lastName' => FilterInputRule::PersonName->value,
            'nickName' => FilterInputRule::HtmlSafeName->value,
            'useGravatar' => $this->application->enumToValues(YesNo::class),
            'avatar' => FilterInputRule::Avatar->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $this->dataHelper->set('Person', [
            'FirstName' => $input['firstName'] ?? '???',
            'LastName' => $input['lastName'] ?? '???',
            'NickName' => $input['nickName'] ?? '',
            'Avatar' => ($input['useGravatar'] ?? YesNo::No->value) == YesNo::Yes->value ? '' : $input['avatar'] ?? '🤔',
            'useGravatar' => $input['useGravatar'] ?? YesNo::No->value,
        ], ['Id' => $person->Id]);
        if ($person->Imported == 0) {
            $email = urldecode($input['email'] ?? '');
            $this->dataHelper->set('Person', ['Email' => $email], ['Id' => $person->Id]);
            $_SESSION['user'] = $email;
        }
        $this->redirect('/user');
    }
}
