<?php

namespace app\controllers;

use app\helpers\DesignDataHelper;

class DesignController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if ($person = $this->personDataHelper->getPerson(['Redactor'])) {
            [$designs, $userVotes] = (new DesignDataHelper())->getUsersVotes($person->Id);

            $this->render('app/views/designs/index.latte', $this->params->getAll([
                'designs' => $designs,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'userVotes' => $userVotes
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function create()
    {
        if ($this->personDataHelper->getPerson(['Redactor'])) {
            $this->render('app/views/designs/create.latte', $this->params->getAll([
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function save()
    {
        if ($person = $this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $values = [
                    'IdPerson' => $person->Id,
                    'Name' => $_POST['name'] ?? '',
                    'Detail' => $_POST['detail'] ?? '',
                    'NavBar' => $_POST['navbar'] ?? '',
                    'Status' => 'UnderReview',
                    'OnlyForMembers' => $_POST['onlyForMembers'] ? 1 : 0,
                    'IdGroup' => $_POST['idGroup'] !== '' ? $_POST['idGroup'] : null
                ];
                $this->dataHelper->set('Design', $values);

                $this->flight->redirect('/designs');
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
