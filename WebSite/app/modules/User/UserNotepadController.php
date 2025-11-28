<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class UserNotepadController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function editNotepad(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $this->render('User/views/user_notepad.latte', $this->getAllParams([
            'notepad' => $person->Notepad,
            'navItems' => $this->getNavItems($person),
            'page' => $this->application->getConnectedUser()->getPage(1),
        ]));
    }

    public function saveNotepad()
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
        $schema = ['content' => FilterInputRule::Html->value];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $notepad = $input['content'] ?? '???';

        $this->dataHelper->set('Person', [
            'Notepad' => $notepad,
        ], ['Id' => $person->Id]);
        $this->redirect('/user');
    }
}
