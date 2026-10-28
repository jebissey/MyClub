<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserNotepadViewModel;

final class UserNotepadController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function editNotepad(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;
        $row = $this->dataHelper->get('Member', ['Id' => $person->id], 'Notepad');
        /** @var object{Notepad: string|null}|false $row */
        $notepad = $row !== false ? ($row->Notepad ?? '') : '';

        $viewModel = new UserNotepadViewModel(
            notepad: $notepad,
            navItems: $this->getNavItems($person->email),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_notepad.latte', $viewModel->toArray());
    }

    public function saveNotepad(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $schema = ['content' => FilterInputRule::Html->value];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $notepad = $input['content'] ?? '???';
        $person = $this->application->getConnectedUser(true)->person;
        $this->dataHelper->set('Member', [
            'Notepad' => $notepad,
        ], ['Id' => $person->id]);
        $this->redirect('/user');
    }
}
