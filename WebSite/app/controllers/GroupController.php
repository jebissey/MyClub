<?php

namespace app\controllers;

use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\GroupDataHelper;
use app\helpers\Params;
use app\helpers\Webapp;
use app\interfaces\CrudControllerInterface;

class GroupController extends BaseController implements CrudControllerInterface
{
    private GroupDataHelper $groupDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->groupDataHelper = new GroupDataHelper($application);
    }

    public function index()
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $this->render('app/views/groups/index.latte', Params::getAll([
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'layout' => Webapp::getLayout()
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {

            $availableAuthorizations = $this->dataHelper->gets('Authorization');
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = isset($_POST['name']) ? Webapp::sanitizeInput($_POST['name']) : '';
                $selfRegistration = isset($_POST['selfRegistration']) ? 1 : 0;
                $selectedAuthorizations = isset($_POST['authorizations']) ? $_POST['authorizations'] : [];

                if (empty($name)) {
                    $this->render('app/views/groups/create.latte', Params::getAll([
                        'availableAuthorizations' => $availableAuthorizations,
                        'error' => 'Le nom du groupe est requis',
                        'layout' => Webapp::getLayout()
                    ]));
                }
                $this->groupDataHelper->insert($name, $selfRegistration, $selectedAuthorizations);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/groups/create.latte', Params::getAll([
                    'availableAuthorizations' => $availableAuthorizations,
                    'layout' => Webapp::getLayout()
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function edit($id)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $availableAuthorizations = $this->dataHelper->gets('Authorization', ['Id <> 1' => null]);
            $group = $this->dataHelper->get('Group', ['Id' => $id]);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = isset($_POST['name']) ? Webapp::sanitizeInput($_POST['name']) : '';
                $selfRegistration = isset($_POST['selfRegistration']) ? 1 : 0;
                $selectedAuthorizations = isset($_POST['authorizations']) ? $_POST['authorizations'] : [];

                if (empty($name)) {
                    $this->render('app/views/groups/edit.latte', Params::getAll([
                        'group' => $group,
                        'availableAuthorizations' => $availableAuthorizations,
                        'error' => 'Le nom du groupe est requis',
                        'layout' => Webapp::getLayout()
                    ]));
                } else $this->groupDataHelper->update($id, $name, $selfRegistration, $selectedAuthorizations);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if (!$group) $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknwon group $id in file " . __FILE__ . ' at line ' . __LINE__);
                else {
                    $this->render('app/views/groups/edit.latte', Params::getAll([
                        'group' => $group,
                        'availableAuthorizations' => $availableAuthorizations,
                        'currentAuthorizations' => array_column($this->dataHelper->gets('GroupAuthorization', ['IdGroup' => $id], 'IdAuthorization'), 'IdAuthorization'),
                        'layout' => Webapp::getLayout()
                    ]));
                }
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function delete($id)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $this->dataHelper->set('Group', ['Inactivated' => 0], ['Id' => $id]);
            $this->flight->redirect('/groups');
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
