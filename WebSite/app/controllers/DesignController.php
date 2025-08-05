<?php

namespace app\controllers;

use app\enums\InputPattern;
use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\DesignDataHelper;
use app\helpers\Params;
use app\helpers\WebApp;

class DesignController extends AbstractController
{

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function index()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            [$designs, $userVotes] = (new DesignDataHelper($this->application))->getUsersVotes($this->connectedUser->person->Id);

            $this->render('app/views/designs/index.latte', Params::getAll([
                'designs' => $designs,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'userVotes' => $userVotes
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            $this->render('app/views/designs/create.latte', Params::getAll([
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function save()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'name' => InputPattern::Content->value,
                    'detail' => InputPattern::Content->value,
                    'navbar' => InputPattern::Content->value,
                    'onlyForMembers' => 'int',
                    'idGroup' => InputPattern::Content->value,
                ];
                $filterValues = WebApp::filterInput($schema, $_POST);
                $values = [
                    'IdPerson' => $this->connectedUser->person->Id,
                    'Name' => $filterValues['name'] ?? '',
                    'Detail' => $filterValues['detail'] ?? '',
                    'NavBar' => $filterValues['navbar'] ?? '',
                    'Status' => 'UnderReview',
                    'OnlyForMembers' => $filterValues['onlyForMembers'] ? 1 : 0,
                    'IdGroup' => $filterValues['idGroup'] !== '' ? $filterValues['idGroup'] : null
                ];
                $this->dataHelper->set('Design', $values);

                $this->flight->redirect('/designs');
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
