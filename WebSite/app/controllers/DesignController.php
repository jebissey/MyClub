<?php

namespace app\controllers;

use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\DesignDataHelper;

class DesignController extends BaseController
{

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function index()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isRedactor()) {
            [$designs, $userVotes] = (new DesignDataHelper($this->application))->getUsersVotes($this->connectedUser->person->Id);

            $this->render('app/views/designs/index.latte', $this->params->getAll([
                'designs' => $designs,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'userVotes' => $userVotes
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isRedactor()) {
            $this->render('app/views/designs/create.latte', $this->params->getAll([
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function save()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isRedactor()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $values = [
                    'IdPerson' => $this->connectedUser->person->Id,
                    'Name' => $_POST['name'] ?? '',
                    'Detail' => $_POST['detail'] ?? '',
                    'NavBar' => $_POST['navbar'] ?? '',
                    'Status' => 'UnderReview',
                    'OnlyForMembers' => $_POST['onlyForMembers'] ? 1 : 0,
                    'IdGroup' => $_POST['idGroup'] !== '' ? $_POST['idGroup'] : null
                ];
                $this->dataHelper->set('Design', $values);

                $this->flight->redirect('/designs');
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
