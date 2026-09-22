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
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $row = $this->dataHelper->get('Member', ['Id' => $person->Id], 'Notepad');
        /** @var object{Notepad: string|null}|false $row */
        $notepad = $row !== false ? ($row->Notepad ?? '') : '';

        $viewModel = new UserNotepadViewModel(
            notepad: $notepad,
            navItems: $this->getNavItems($person),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/user_notepad.latte', $viewModel->toArray());
    }

    public function saveNotepad(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = ['content' => FilterInputRule::Html->value];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $notepad = $input['content'] ?? '???';

        $this->dataHelper->set('Member', [
            'Notepad' => $notepad,
        ], ['Id' => $person->Id]);
        $this->redirect('/user');
    }
}
