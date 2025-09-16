<?php

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class UserNotepadController extends AbstractController
{
    public function __construct(
        Application $application,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function editNotepad(): void
    {
        $person = $this->application->getConnectedUser()->get()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $this->render('User/views/user_notepad.latte', Params::getAll([
            'notepad' => $person->Notepad,
            'navItems' => $this->getNavItems($person),
        ]));
    }

    public function saveNotepad()
    {
        $person = $this->application->getConnectedUser()->get(1)->person;
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
